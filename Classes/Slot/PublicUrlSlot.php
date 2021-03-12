<?php

namespace BeechIt\Bynder\Slot;

/*
 * This source file is proprietary property of Beech.it
 * Date: 26-2-18
 * All code (c) Beech.it all rights reserved
 */

use BeechIt\Bynder\Resource\BynderDriver;
use BeechIt\Bynder\Utility\ConfigurationUtility;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Resource\Driver\DriverInterface;
use TYPO3\CMS\Core\Resource\Exception\FileDoesNotExistException;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\ResourceInterface;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * Class PublicUrlSlot
 *
 * Bynder processed files have a CDN url we can use
 * directly as public url.
 *
 * @package BeechIt\Bynder\Slot
 */
class PublicUrlSlot
{
    /**
     * Generate public url for file
     *
     * @param  ResourceStorage  $storage
     * @param  DriverInterface  $driver
     * @param  ResourceInterface  $resourceObject
     * @param $relativeToCurrentScript
     * @param  array  $urlData
     * @return void
     */
    public function getPublicUrl(
        ResourceStorage $storage,
        DriverInterface $driver,
        ResourceInterface $resourceObject,
        $relativeToCurrentScript,
        array $urlData
    ) {
        if ($resourceObject instanceof FileInterface && $resourceObject->getProperty('bynder') === true) {
            if ($resourceObject->getProperty('bynder_url')) {
                $urlData['publicUrl'] = $resourceObject->getProperty('bynder_url');
            } else {
                $urlData['publicUrl'] = $this->getUnavailableImage($relativeToCurrentScript);
            }
        } elseif ($driver instanceof BynderDriver) {
            try {
                $urlData['publicUrl'] = $driver->getPublicUrl($resourceObject->getIdentifier());
            } catch (FileDoesNotExistException $e) {
                $urlData['publicUrl'] = $this->getUnavailableImage($relativeToCurrentScript);
            }
        }
    }

    /**
     * @param  bool  $relativeToCurrentScript
     * @return string
     */
    protected function getUnavailableImage($relativeToCurrentScript = false): string
    {
        $configuration = ConfigurationUtility::getExtensionConfiguration();
        $path = GeneralUtility::getFileAbsFileName(
            $configuration['image_unavailable'] ??
            'EXT:bynder/Resources/Public/Icons/ImageUnavailable.svg'
        );

        return ($relativeToCurrentScript) ? PathUtility::getAbsoluteWebPath($path) : str_replace(Environment::getPublicPath() . '/', '', $path);
    }
}
