<?php

namespace BeechIt\Bynder\Resource;

/*
 * This source file is proprietary property of Beech.it
 * Date: 21-2-18
 * All code (c) Beech.it all rights reserved
 */

use BeechIt\Bynder\Service\BynderService;
use GuzzleHttp\Exception\ClientException;
use TYPO3\CMS\Core\Resource\Driver\DriverInterface;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Resource\ProcessedFileRepository;
use TYPO3\CMS\Core\Resource\Service\FileProcessingService;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Create/fetch CDN urls for scaled/cropped Bynder assets
 */
class AssetProcessing implements SingletonInterface
{

    /** @var \BeechIt\Bynder\Service\BynderService */
    protected $bynderService;

    /**
     * @param  \TYPO3\CMS\Core\Resource\ProcessedFile  $processedFile
     * @return bool
     */
    protected function needsReprocessing($processedFile): bool
    {
        return $processedFile->isNew()
            || (!$processedFile->usesOriginalFile() && !$processedFile->exists())
            || $processedFile->isOutdated();
    }

    /**
     * Create url for scalled/cropped versions of Bynder assets
     *
     * @param  \TYPO3\CMS\Core\Resource\Service\FileProcessingService  $fileProcessingService
     * @param  \TYPO3\CMS\Core\Resource\Driver\DriverInterface  $driver
     * @param  \TYPO3\CMS\Core\Resource\ProcessedFile  $processedFile
     * @param  \TYPO3\CMS\Core\Resource\File  $file
     * @param $taskType
     * @param  array  $configuration
     * @throws \TYPO3\CMS\Core\Cache\Exception\NoSuchCacheException
     */
    public function processFile(
        FileProcessingService $fileProcessingService,
        DriverInterface $driver,
        ProcessedFile $processedFile,
        File $file,
        $taskType,
        array $configuration
    ) {
        if ($file->getStorage()->getDriverType() !== BynderDriver::KEY) {
            return;
        }

        if (!$this->needsReprocessing($processedFile)) {
            return;
        }

        try {
            $mediaInfo = $this->getBynderService()->getMediaInfo($processedFile->getOriginalFile()->getIdentifier());
        } catch (ClientException $e) {
            $mediaInfo = [
                'isPublic' => false,
                'thumbnails' => [
                    'thul' => '',
                    'webimage' => '',
                    'mini' => '',
                ],
            ];
        }

        $processingConfiguration = $configuration;
        // The CONTEXT_IMAGEPREVIEW task only gives max dimensions
        if ($taskType === ProcessedFile::CONTEXT_IMAGEPREVIEW) {
            if (!empty($processingConfiguration['width'])) {
                $processingConfiguration['width'] .= 'm';
            }
            if (!empty($processingConfiguration['height'])) {
                $processingConfiguration['height'] .= 'm';
            }
        }

        $fileInfo = $this->getThumbnailInfo(
            $processingConfiguration,
            (int)$processedFile->getOriginalFile()->getProperty('width'),
            (int)$processedFile->getOriginalFile()->getProperty('height'),
            $mediaInfo['thumbnails']
        );

        $processedFile->setUsesOriginalFile();
        $checksum = $processedFile->getTask()->getConfigurationChecksum();
        $processedFile->setIdentifier('processed_' . $file->getIdentifier() . '_' . $fileInfo['type'] . '_' . $checksum);

        // Update existing processed file
        $processedFile->updateProperties([
            'bynder' => true,
            'width' => $fileInfo['width'],
            'height' => $fileInfo['height'],
            'checksum' => $checksum,
            'bynder_url' => $fileInfo['url'],
        ]);

        // Persist processed file like done in FileProcessingService::process()
        $processedFileRepository = GeneralUtility::makeInstance(ProcessedFileRepository::class);
        $processedFileRepository->add($processedFile);
    }

    /**
     * Calculate the best suitable/available dimensions for the requested file configuration
     *
     * @param  array  $configuration
     * @param  int  $orgWidth
     * @param  int  $orgHeight
     * @param  array  $derivatives
     * @return array
     */
    protected function getThumbnailInfo(array $configuration, int $orgWidth, int $orgHeight, array $derivatives): array
    {
        $rawWidth = $configuration['width'] ?? $configuration['maxWidth'] ?? 0;
        $rawHeight = $configuration['height'] ?? $configuration['maxHeight'] ?? 0;

        $keepRatio = true;

        // When width and height are set and non of them have a 'm' suffix we don't keep existing ratio
        if ($rawWidth && $rawHeight && strpos($rawWidth . $rawWidth, 'm') < 0) {
            $keepRatio = false;
        }

        // When width and height are set and one of then have a 'c' suffix we don't keep existing ratio and allow cropping
        if ($rawWidth && $rawHeight && strpos($rawWidth . $rawWidth, 'c') >= 0) {
            $keepRatio = false;
        }

        $width = (int)$rawWidth;
        $height = (int)$rawHeight;

        if (!$keepRatio && $width > $orgWidth) {
            $height = $this->calculateRelativeDimension($width, $height, $orgWidth);
            $width = $orgWidth;
        } elseif (!$keepRatio && $height > $orgHeight) {
            $width = $this->calculateRelativeDimension($height, $width, $orgHeight);
            $height = $orgHeight;
        } elseif ($keepRatio && $width > $orgWidth) {
            $height = $orgWidth / $width * $height;
        } elseif ($keepRatio && $height > $orgHeight) {
            $height = $orgHeight / $height * $width;
        } elseif ($width === 0 && $height > 0) {
            $height = $this->calculateRelativeDimension($orgWidth, $orgHeight, $width);
        } elseif ($width === 0 && $height > 0) {
            $width = $this->calculateRelativeDimension($orgHeight, $orgWidth, $height);
        }

        $default = [
            'type' => 'webimage',
            'width' => $width,
            'height' => $height,
            'url' => $derivatives['webimage'],
        ];
        if ($height === 0 && $width === 0) {
            return $default;
        }
        if ($width <= 80) {
            return [
                'type' => 'mini',
                'width' => $width,
                'height' => $width, // derivative/image is square
                'url' => $derivatives['mini'],
            ];
        } elseif ($width <= 250) {
            return [
                'type' => 'thul',
                'width' => $width,
                'height' => $height,
                'url' => $derivatives['thul'],
            ];
        }

        return $default;
    }

    /**
     * Calculate relative dimension
     *
     * For instance you have the original width, height and new width.
     * And want to calculate the new height with the same ratio as the original dimensions
     *
     * @param  int  $orgA
     * @param  int  $orgB
     * @param  int  $newA
     * @return int
     */
    protected function calculateRelativeDimension(int $orgA, int $orgB, int $newA): int
    {
        if ($newA === 0) {
            return $orgB;
        }

        return (int)($orgB / ($orgA / $newA));
    }

    /**
     * @return \BeechIt\Bynder\Service\BynderService
     * @throws \InvalidArgumentException
     */
    protected function getBynderService(): BynderService
    {
        if ($this->bynderService === null) {
            $this->bynderService = GeneralUtility::makeInstance(BynderService::class);
        }

        return $this->bynderService;
    }
}
