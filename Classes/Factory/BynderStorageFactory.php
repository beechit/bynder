<?php

namespace BeechIt\Bynder\Factory;

use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Resource\ResourceStorage;

class BynderStorageFactory
{
    public function __invoke(): ResourceStorage
    {
        foreach ($this->getBackendUserAuthentication()->getFileStorages() as $fileStorage) {
            if ($fileStorage->getDriverType() === 'bynder') {
                return $fileStorage;
            }
        }

        throw new \InvalidArgumentException('Missing Bynder file storage');
    }

    protected function getBackendUserAuthentication(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
