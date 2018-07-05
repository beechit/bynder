<?php

namespace BeechIt\Bynder\Traits;

/**
 * Trait BynderStorage
 * @package BeechIt\Bynder\Traits
 */
trait BynderStorage
{

    /**
     * @var \TYPO3\CMS\Core\Resource\ResourceStorage
     */
    protected $bynderStorage;

    /**
     * @return \TYPO3\CMS\Core\Resource\ResourceStorageInterface
     */
    protected function getBynderStorage(): \TYPO3\CMS\Core\Resource\ResourceStorage
    {
        if ($this->bynderStorage === null) {
            /** @var \TYPO3\CMS\Core\Resource\ResourceStorage $fileStorage */
            $backendUserAuthentication = $GLOBALS['BE_USER'];
            foreach ($backendUserAuthentication->getFileStorages() as $fileStorage) {
                if ($fileStorage->getDriverType() === 'bynder') {
                    return $this->bynderStorage = $fileStorage;
                }
            }
            throw new \InvalidArgumentException('Missing Bynder file storage');
        }
        return $this->bynderStorage;
    }
}
