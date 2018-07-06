<?php

namespace BeechIt\Bynder\Resource\Rendering;

use BeechIt\Bynder\Traits\BynderHelper;
use BeechIt\Bynder\Utility\ConfigurationUtility;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\Rendering\FileRendererInterface;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;

/**
 * Class BynderRenderer
 */
class BynderVideoRenderer implements FileRendererInterface
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
        return ($file->getExtension() === 'bynder' && $file->getMimeType() === 'bynder/video');
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
        return $this->renderVideo($file, $width, $height, $options, $usedPathsRelativeToCurrentScript);
    }

    /**
     * Render video for given File(Reference) HTML output
     *
     * @param FileInterface $source
     * @param int|string $width in video, always 0
     * @param int|string $height in video, always 0
     * @param array $options
     * @param bool $usedPathsRelativeToCurrentScript
     * @return string
     * @throws \TYPO3\CMS\Core\Cache\Exception\NoSuchCacheException
     * @throws \TYPO3\CMS\Extbase\Object\InvalidObjectException
     */
    public function renderVideo($source, $width, $height, $options, $usedPathsRelativeToCurrentScript): string
    {
        $sources = [];

        try {
            if ($source instanceof File) {
                $file = $source;
            } elseif (is_callable([$source, 'getOriginalFile'])) {
                $file = $source->getOriginalFile();
            }

            $mediaInfo = $this->getBynderHelper($file)->getBynderMediaInfo($file->getIdentifier());
            foreach ((array)$mediaInfo['videoPreviewURLs'] as $url) {
                switch (pathinfo($url, PATHINFO_EXTENSION)) {
                    case 'webm':
                        $sources[$url] = '<source src="' . $url . '" type="video/ogg">';
                        break;
                    case 'mp4':
                        $sources[$url] = '<source src="' . $url . '" type="video/mp4">';
                        break;
                }
            }
        }catch (\Exception $e){
            // Catch all exceptions as these should never crash the frontend website
        }
        
        if (empty($sources)) {
            return '<!-- Video not available for simple embedding -->';
        } else {
            return '<video width="100%" height="480" controls>' . implode(PHP_EOL, $sources) . '</video>';
        }
    }


}
