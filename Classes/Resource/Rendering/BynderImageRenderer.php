<?php

namespace BeechIt\Bynder\Resource\Rendering;

use BeechIt\Bynder\Traits\BynderHelper;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\Rendering\FileRendererInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class BynderRenderer
 */
class BynderImageRenderer implements FileRendererInterface
{

    use BynderHelper;

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
        return 10;
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
     */
    public function render(FileInterface $file, $width, $height, array $options = [], $usedPathsRelativeToCurrentScript = false): string
    {
        if (!($file instanceof File) && is_callable([$file, 'getOriginalFile'])) {
            $originalFile = $file->getOriginalFile();
        } else {
            $originalFile = $file;
        }

        return $this->renderImageTag(
            $file,
            $this->getBynderHelper($originalFile)->getPublicUrl($originalFile),
            $width, $height
        );
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
    public function renderImageTag($file, $source, $width, $height): string
    {
        $tag = GeneralUtility::makeInstance(\TYPO3Fluid\Fluid\Core\ViewHelper\TagBuilder::class);
        $tag->setTagName('img');

        $tag->addAttribute('src', $source);
        $tag->addAttribute('width', $width);
        $tag->addAttribute('height', $height);

        $alt = $file->getProperty('alternative');
        $title = $file->getProperty('title');

        // The alt-attribute is mandatory to have valid html-code, therefore add it even if it is empty
        if ($alt) {
            $tag->addAttribute('alt', $alt);
        }
        if ($title) {
            $tag->addAttribute('title', $title);
        }

        return $tag->render();
    }

    /**
     * Calculate the best suitable/available dimensions for the requested file configuration
     *
     * @param array $configuration
     * @param int $orgWidth
     * @param int $orgHeight
     * @param string $otfBaseUrl
     * @param array $derivatives
     * @return array
     */
    protected function calculateImagelInfo(array $configuration, $width, $height, string $otfBaseUrl, array $derivatives): array
    {
        $width = $configuration['width'] ?? $configuration['maxWidth'] ?? 0;
        $height = $configuration['height'] ?? $configuration['maxHeight'] ?? 0;

        $keepRatio = true;
        $crop = false;
        $otf = $otfBaseUrl !== '';

        // When width and height are set and non of them have a 'm' suffix we don't keep existing ratio
        if ($width && $height && strpos($width . $height, 'm') < 0) {
            $keepRatio = false;
        }

        // When width and height are set and one of then have a 'c' suffix we don't keep existing ratio and allow cropping
        if ($width && $height && strpos($width . $height, 'c') >= 0) {
            $keepRatio = false;
            $crop = true;
        }

        $width = (int)$width;
        $height = (int)$height;

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

        if ($otf) {
            return [
                'type' => 'otf',
                'width' => $width ?: $orgWidth,
                'height' => $height ?: $orgHeight,
                'url' => $otfBaseUrl . '?' . http_build_query([
                        'w' => $width ?: '',
                        'h' => $height ?: '',
                        'crop' => $crop ? 'true' : 'false'
                    ]),
            ];
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
     * @param int $orgA
     * @param int $orgB
     * @param int $newA
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
