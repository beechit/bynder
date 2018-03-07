<?php

namespace BeechIt\Bynder\Slot;

/*
 * This source file is proprietary property of Beech.it
 * Date: 26-2-18
 * All code (c) Beech.it all rights reserved
 */
use TYPO3\CMS\Core\Resource\AbstractFile;
use TYPO3\CMS\Core\Resource\Driver\DriverInterface;
use TYPO3\CMS\Core\Resource\ResourceInterface;
use TYPO3\CMS\Core\Resource\ResourceStorage;

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
     * @param ResourceStorage $storage
     * @param DriverInterface $driver
     * @param ResourceInterface $resourceObject
     * @param $relativeToCurrentScript
     * @param array $urlData
     * @return void
     */
    public function getPublicUrl(
        ResourceStorage $storage,
        DriverInterface $driver,
        ResourceInterface $resourceObject,
        $relativeToCurrentScript,
        array $urlData
    ) {
        if ($resourceObject instanceof AbstractFile && $resourceObject->getProperty('bynder_url')) {
            $urlData['publicUrl'] = $resourceObject->getProperty('bynder_url');
        }
    }
}