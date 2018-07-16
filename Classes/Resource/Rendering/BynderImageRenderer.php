<?php

namespace BeechIt\Bynder\Resource\Rendering;

use BeechIt\Bynder\Exception\BynderException;
use BeechIt\Bynder\Resource\Helper\BynderHelper;
use BeechIt\Bynder\Service\TagBuilderService;
use BeechIt\Bynder\Traits\BynderHelper as BynderHelperTrait;
use BeechIt\Bynder\Utility\ConfigurationUtility;
use TYPO3\CMS\Core\Imaging\ImageManipulation\CropVariantCollection;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Resource\Rendering\FileRendererInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Service\ImageService;

/**
 * Class BynderRenderer
 */
class BynderImageRenderer implements FileRendererInterface
{
    use BynderHelperTrait;

    /**
     * Returns the priority of the renderer
     * This way it is possible to define/overrule a renderer
     * for a specific file type/context.
     *
     * For example create a video renderer for a certain storage/driver type.
     *
     * Should be between 1 and 100, 100 is more important than 1
     *
     * @return int
     */
    public function getPriority(): int
    {
        return 15;
    }

    /**
     * Check if given File(Reference) can be rendered
     *
     * @param FileInterface $file File or FileReference to render
     * @return bool
     */
    public function canRender(FileInterface $file): bool
    {
        return ($file->getExtension() === 'bynder' && ($file->getMimeType() === 'bynder/image'));
    }

    /**
     * Render for given File(Reference) HTML output
     *
     * @param FileInterface $file
     * @param int|string $width TYPO3 known format; examples: 220, 200m or 200c
     * @param int|string $height TYPO3 known format; examples: 220, 200m or 200c
     * @param array $options
     * @param bool $usedPathsRelativeToCurrentScript See $file->getPublicUrl()
     * @return string
     * @throws \BeechIt\Bynder\Exception\InvalidExtensionConfigurationException
     * @throws \TYPO3\CMS\Core\Cache\Exception\NoSuchCacheException
     * @throws \TYPO3\CMS\Extbase\Object\InvalidObjectException
     */
    public function render(FileInterface $file, $width, $height, array $options = [], $usedPathsRelativeToCurrentScript = false): string
    {
        return $this->renderImageTag(
            $file,
            $this->getProcessedPublicImageLocation($file, $width, $height, $options),
            $width,
            $height,
            $options
        );
    }

    /**
     * @param FileInterface $file
     * @param $width
     * @param $height
     * @param $info
     * @return string
     * @throws \BeechIt\Bynder\Exception\InvalidExtensionConfigurationException
     * @throws \TYPO3\CMS\Core\Cache\Exception\NoSuchCacheException
     * @throws \TYPO3\CMS\Extbase\Object\InvalidObjectException
     */
    protected function getProcessedPublicImageLocation(FileInterface $file, $width, $height, $info)
    {
        if (!($file instanceof File) && is_callable([$file, 'getOriginalFile'])) {
            $originalFile = $file->getOriginalFile();
        } else {
            $originalFile = $file;
        }

        list($width, $height) = $this->getCalculatedSizes($originalFile->getProperty('width'), $originalFile->getProperty('height'), $width, $height);

        try {
            if (ConfigurationUtility::isOnTheFlyConfigured() && $url = $this->getBynderHelper($originalFile)->getOnTheFlyPublicUrl($originalFile, $width, $height)) {
                return $url;
            } else {
                return $this->processPublicImageLocationLocally($originalFile, $width, $height, $info);
            }
        } catch (BynderException $e) {
            // Never throw on own exceptions, just return unavailable image
            return ConfigurationUtility::getUnavailableImage();
        }
    }

    /**
     * @param File $file
     * @param integer $width
     * @param integer $height
     * @param array $info
     * @return string
     * @throws \TYPO3\CMS\Extbase\Object\InvalidObjectException
     * @throws \BeechIt\Bynder\Exception\NotImplementedException
     */
    protected function processPublicImageLocationLocally(File $file, $width, $height, $info)
    {
        // When width and height are set and non of them have a 'm' suffix we don't keep existing ratio
        $derivative = BynderHelper::DERIVATIVES_WEB_IMAGE;
        if ($width && $height) {
            if ($width <= 80) {
                $derivative = BynderHelper::DERIVATIVES_MINI;
            } elseif ($width <= 250) {
                $derivative = BynderHelper::DERIVATIVES_THUMBNAIL;
            }
        }
        $bynderHelper = $this->getBynderHelper($file);
        // Set required derivative
        $bynderHelper->setDerivative($derivative);

        /**
         * Now do the same logic as MediaViewHelper.
         */
        $cropVariant = $info['cropVariant'] ?: 'default';
        $cropString = $file instanceof FileReference ? $file->getProperty('crop') : '';
        $cropVariantCollection = CropVariantCollection::create((string)$cropString);
        $cropArea = $cropVariantCollection->getCropArea($cropVariant);
        $processingInstructions = [
            'width' => $width,
            'height' => $height,
            'crop' => $cropArea->isEmpty() ? null : $cropArea->makeAbsoluteBasedOnFile($file),
        ];
        $imageService = $this->getImageService();
        $processedImage = $imageService->applyProcessingInstructions($file, $processingInstructions);
        $imageUri = $imageService->getImageUri($processedImage);
        /**
         * The logic from MediaViewHelper is ended here.
         */

        // Restore derivative to thumbnail
        $bynderHelper->setDerivative(BynderHelper::DERIVATIVES_THUMBNAIL);
        return $imageUri;
    }

    /**
     * @param integer $originalWidth
     * @param integer $originalHeight
     * @param integer|string $width
     * @param integer|string $height
     * @return array
     */
    protected function getCalculatedSizes($originalWidth, $originalHeight, $width, $height)
    {
        $keepRatio = true;
        $crop = false;

        // When width and height are set and non of them have a 'm' suffix we don't keep existing ratio
        if ($width && $height && strpos($width . $height, 'm') === false) {
            $keepRatio = false;
        }

        // When width and height are set and one of then have a 'c' suffix we don't keep existing ratio and allow cropping
        if ($width && $height && strpos($width . $height, 'c') !== false) {
            $keepRatio = false;
            $crop = true;
        }

        $width = (int)$width;
        $height = (int)$height;

        if (!$keepRatio && $width > $originalWidth) {
            $height = $this->calculateRelativeDimension($width, $height, $originalWidth);
            $width = $originalWidth;
        } elseif (!$keepRatio && $height > $originalHeight) {
            $width = $this->calculateRelativeDimension($height, $width, $originalHeight);
            $height = $originalHeight;
        } elseif ($keepRatio && $width > $originalWidth) {
            $height = $originalWidth / $width * $height;
        } elseif ($keepRatio && $height > $originalHeight) {
            $height = $originalHeight / $height * $width;
        } elseif ($width === 0 && $height > 0) {
            $height = $this->calculateRelativeDimension($originalWidth, $originalHeight, $width);
        } elseif ($width === 0 && $height > 0) {
            $width = $this->calculateRelativeDimension($originalHeight, $originalWidth, $height);
        }
        if ($crop === true) {
            return [$width . 'c', $height . 'c'];
        } else {
            return [$width, $height];
        }
    }

    /**
     * Render image for given File(Reference) HTML output
     *
     * @param FileInterface $file
     * @param string $source
     * @param int|string $width TYPO3 known format; examples: 220, 200m or 200c
     * @param int|string $height TYPO3 known format; examples: 220, 200m or 200c
     * @param array $options
     * @param bool $usedPathsRelativeToCurrentScript See $file->getPublicUrl()
     * @return string
     */
    public function renderImageTag($file, $source, $width, $height, $options, $usedPathsRelativeToCurrentScript = false): string
    {
        $tagBuilderService = $this->getTagBuilderService();
        $tag = $tagBuilderService->getTagBuilder('img');
        $tagBuilderService->initializeAbstractTagBasedAttributes($tag, $options);

        $tag->addAttribute('src', ($usedPathsRelativeToCurrentScript ? $source : $source));
        $tag->addAttribute('width', $width);
        $tag->addAttribute('height', $height);

        // The alt-attribute is mandatory to have valid html-code, therefore add it even if it is empty
        if ($tag->hasAttribute('alt') === false) {
            $tag->addAttribute('alt', $file->getProperty('alternative'));
        }
        if ($tag->hasAttribute('title') === false) {
            $tag->addAttribute('title', $file->getProperty('title'));
        }

        return $tag->render();
    }

    /**
     * Calculate relative dimension
     * For instance you have the original width, height and new width.
     * And want to calculate the new height with the same ratio as the original dimensions
     *
     * @param int $orgA
     * @param int $orgB
     * @param int $newA
     * @return int
     */
    protected function calculateRelativeDimension(int $orgA, int $orgB, int $newA): int
    {
        return ($newA === 0) ? $orgB : (int)($orgB / ($orgA / $newA));
    }

    /**
     * Return an instance of ImageService
     *
     * @return ImageService
     */
    protected function getImageService()
    {
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        return $objectManager->get(ImageService::class);
    }

    /**
     * Return an instance of TagBuilderService
     *
     * @return TagBuilderService
     */
    protected function getTagBuilderService()
    {
        return GeneralUtility::makeInstance(TagBuilderService::class);
    }
}
