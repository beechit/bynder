<?php

namespace BeechIt\Bynder\Resource\Rendering;

use BeechIt\Bynder\Resource\BynderDriver;
use BeechIt\Bynder\Traits\BynderHelper;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\OnlineMedia\Helpers\OnlineMediaHelperRegistry;
use TYPO3\CMS\Core\Resource\Rendering\FileRendererInterface;

/**
 * Class BynderRenderer
 */
class BynderRenderer implements FileRendererInterface
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
        return 50;
    }

    /**
     * Check if given File(Reference) can be rendered
     *
     * @param FileInterface $file File or FileReference to render
     * @return bool
     */
    public function canRender(FileInterface $file): bool
    {
        return ($file->getExtension() === 'bynder'
            && ($file->getMimeType() === 'bynder/video') || ($file->getMimeType() === 'bynder/image'));
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
        switch ($file->getMimeType()) {
            case 'bynder/' . BynderDriver::ASSET_TYPE_IMAGE:
                return $this->renderImage($file, $width, $height, $options, $usedPathsRelativeToCurrentScript);
                break;
            case 'bynder/' . BynderDriver::ASSET_TYPE_VIDEO:
                return $this->renderVideo($file, $width, $height, $options, $usedPathsRelativeToCurrentScript);
                break;
        }
        return '';
    }

    /**
     * Render image for given File(Reference) HTML output
     *
     * @param FileInterface $file
     * @param int|string $width TYPO3 known format; examples: 220, 200m or 200c
     * @param int|string $height TYPO3 known format; examples: 220, 200m or 200c
     * @param array $options
     * @param bool $usedPathsRelativeToCurrentScript See $file->getPublicUrl()
     * @return string
     */
    public function renderImage($file, $width, $height, $options, $usedPathsRelativeToCurrentScript): string
    {
        if (is_callable([$file, 'getOriginalFile'])) {
            $file = $file->getOriginalFile();
        }

        //return $this->getBynderHelper($file)->getPublicUrl($file, $usedPathsRelativeToCurrentScript);
        return $this->getBynderHelper($file)->getPublicUrl($file, $usedPathsRelativeToCurrentScript). ' ' . $width . 'x' . $height . '?';
    }

    /**
     * Render video for given File(Reference) HTML output
     *
     * @param FileInterface $file
     * @param int|string $width TYPO3 known format; examples: 220, 200m or 200c
     * @param int|string $height TYPO3 known format; examples: 220, 200m or 200c
     * @param array $options
     * @param bool $usedPathsRelativeToCurrentScript See $file->getPublicUrl()
     * @return string
     */
    public function renderVideo($file, $width, $height, $options, $usedPathsRelativeToCurrentScript): string
    {
        return 'video ' . $width . 'x' . $height . '?';
    }


}
