<?php

namespace BeechIt\Bynder\EventListener;

use BeechIt\Bynder\Resource\BynderDriver;
use BeechIt\Bynder\Service\BynderService;
use GuzzleHttp\Exception\ClientException;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Resource\ProcessedFileRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ProcessBynderAsset
{
    /** @var \BeechIt\Bynder\Service\BynderService  */
    private $bynderService;

    /**
     * @param  \BeechIt\Bynder\Service\BynderService  $bynderService
     */
    public function __construct(BynderService $bynderService)
    {
        $this->bynderService = $bynderService;
    }

    /**
     * @param  \TYPO3\CMS\Core\Resource\Event\BeforeFileProcessingEvent  $event
     * @return void
     */
    public function __invoke(\TYPO3\CMS\Core\Resource\Event\BeforeFileProcessingEvent $event)
    {
        $file = $event->getFile();
        if ($file->getStorage()->getDriverType() !== BynderDriver::KEY) {
            return;
        }

        $processedFile = $event->getProcessedFile();
        if (!$this->needsReprocessing($processedFile)) {
            return;
        }
        try {
            $mediaInfo = $this->bynderService->getMediaInfo($file->getIdentifier());
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

        $processingConfiguration = $event->getConfiguration();
        // The CONTEXT_IMAGEPREVIEW task only gives max dimensions
        if ($event->getTaskType() === ProcessedFile::CONTEXT_IMAGEPREVIEW) {
            if (!empty($processingConfiguration['width'])) {
                $processingConfiguration['width'] .= 'm';
            }
            if (!empty($processingConfiguration['height'])) {
                $processingConfiguration['height'] .= 'm';
            }
        }

        $fileInfo = $this->getThumbnailInfo(
            $processingConfiguration,
            (int)$file->getProperty('width'),
            (int)$file->getProperty('height'),
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

        $event->setProcessedFile($processedFile);
    }

    /**
     * @param  \TYPO3\CMS\Core\Resource\ProcessedFile  $processedFile
     * @return bool
     */
    protected function needsReprocessing(ProcessedFile $processedFile): bool
    {
        return $processedFile->isNew()
            || (!$processedFile->usesOriginalFile() && !$processedFile->exists())
            || $processedFile->isOutdated();
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
}
