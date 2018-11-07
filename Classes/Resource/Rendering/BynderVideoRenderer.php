<?php

namespace BeechIt\Bynder\Resource\Rendering;

use BeechIt\Bynder\Traits\BynderHelper;
use BeechIt\Bynder\Utility\ConfigurationUtility;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\Rendering\FileRendererInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

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
        return ($file->getExtension() === 'bynder' && $file->getMimeType() === 'bynder/video');
    }

    /**
     * Render for given File(Reference) HTML output
     *
     * @param FileInterface $source
     * @param int|string $width TYPO3 known format; examples: 220, 200m or 200c
     * @param int|string $height TYPO3 known format; examples: 220, 200m or 200c
     * @param array $options
     * @param bool $usedPathsRelativeToCurrentScript See $file->getPublicUrl()
     * @return string
     * @throws \TYPO3\CMS\Core\Cache\Exception\NoSuchCacheException
     * @throws \TYPO3\CMS\Extbase\Object\InvalidObjectException
     */
    public function render(FileInterface $source, $width, $height, array $options = [], $usedPathsRelativeToCurrentScript = false): string
    {
        if ($source instanceof File) {
            $file = $source;
        } elseif (is_callable([$source, 'getOriginalFile'])) {
            $file = $source->getOriginalFile();
        }

        return $this->getEmbedCode($file);
    }

    /**
     * Include Video.JS javascript libraries and configuration
     *
     * @return void
     */
    protected function addVideoScripts()
    {
        $this->getPageRenderer()->addCssFile('EXT:bynder/Resources/Public/Styles/video-js.min.css');
        $this->getPageRenderer()->addJsInlineCode('video-js', 'window.VIDEOJS_NO_DYNAMIC_STYLE = true;');
        $this->getPageRenderer()->addJsFooterFile('EXT:bynder/Resources/Public/JavaScript/video-js.min.js');
    }

    /**
     * Generate Video.JS embed code
     *
     * @param File $file
     * @return string
     */
    protected function getEmbedCode(File $file): string
    {
        $sources = [];
        try {
            $mediaInfo = $this->getBynderHelper($file)->getBynderMediaInfo($file->getIdentifier());
            foreach ((array)$mediaInfo['videoPreviewURLs'] as $url) {
                $sources[] = '<source src="' . $url . '" type="video/' . pathinfo($url, PATHINFO_EXTENSION) . '">';
            }
        } catch (\Exception $e) {
            // Catch all exceptions as these should never crash the frontend website
        }
        if (!empty($sources)) {
            $this->addVideoScripts();
            return '<video id="video-' . $file->getIdentifier() . '" width="100%" class="video-js" '
                . 'poster="' . $mediaInfo['thumbnails'][\BeechIt\Bynder\Resource\Helper\BynderHelper::DERIVATIVES_WEB_IMAGE] . '" controls preload="auto">'
                . implode('', $sources)
                . '<p class="vjs-no-js">' . LocalizationUtility::translate('bynder.video.javascript_required', ConfigurationUtility::EXTENSION) . '</p>'
                . '</video>';
        } else {
            return '<!-- Video #' . $file->getIdentifier() . ' not available for embedding -->';
        }
    }

    /**
     * @return PageRenderer
     */
    protected function getPageRenderer(): PageRenderer
    {
        return GeneralUtility::makeInstance(PageRenderer::class);
    }
}
