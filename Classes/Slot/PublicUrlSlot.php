<?php

namespace BeechIt\Bynder\Slot;

/*
 * This source file is proprietary property of Beech.it
 * Date: 26-2-18
 * All code (c) Beech.it all rights reserved
 */

use BeechIt\Bynder\Resource\BynderDriver;
use BeechIt\Bynder\Utility\ConfigurationUtility;
use TYPO3\CMS\Core\Resource\Driver\DriverInterface;
use TYPO3\CMS\Core\Resource\Exception\FileDoesNotExistException;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\ResourceInterface;
use TYPO3\CMS\Core\Resource\ResourceStorage;

/**
 * Bynder processed files have a CDN url we can use
 * directly as public url.
 */
class PublicUrlSlot
{
    /**
     * Generate public url for file
     *
     * @param  \TYPO3\CMS\Core\Resource\ResourceStorage  $storage
     * @param  \TYPO3\CMS\Core\Resource\Driver\DriverInterface  $driver
     * @param  \TYPO3\CMS\Core\Resource\ResourceInterface  $resourceObject
     * @param  bool  $relativeToCurrentScript
     * @param  array  $urlData
     * @return void
     */
    public function getPublicUrl(
        ResourceStorage $storage,
        DriverInterface $driver,
        ResourceInterface $resourceObject,
        bool $relativeToCurrentScript,
        array $urlData
    ): void {
        if ($resourceObject instanceof FileInterface && $resourceObject->getProperty('bynder') === true) {
            if ($resourceObject->getProperty('bynder_url')) {
                $urlData['publicUrl'] = $resourceObject->getProperty('bynder_url');
            } else {
                $urlData['publicUrl'] = ConfigurationUtility::getUnavailableImage($relativeToCurrentScript);
            }
        } elseif ($driver instanceof BynderDriver) {
            try {
                $urlData['publicUrl'] = $driver->getPublicUrl($resourceObject->getIdentifier());
            } catch (FileDoesNotExistException $e) {
                $urlData['publicUrl'] = ConfigurationUtility::getUnavailableImage($relativeToCurrentScript);
            }
        }
    }
}
