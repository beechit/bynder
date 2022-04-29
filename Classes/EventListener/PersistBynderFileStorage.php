<?php

namespace BeechIt\Bynder\EventListener;

use BeechIt\Bynder\Resource\BynderDriver;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Package\Event\AfterPackageActivationEvent;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class PersistBynderFileStorage
{
    private $executionTime;
    private $storageRepository;

    public function __construct(StorageRepository $storageRepository)
    {
        $this->executionTime = $GLOBALS['EXEC_TIME'] ?? time();
        $this->storageRepository = $storageRepository;
    }

    /**
     * @param  \TYPO3\CMS\Core\Package\Event\AfterPackageActivationEvent  $event
     * @return void
     */
    public function __invoke(AfterPackageActivationEvent $event)
    {
        if ($event->getPackageKey() !== 'bynder') {
            return;
        }

        /** @var $storageRepository StorageRepository */
        if ($this->storageRepository->findByStorageType(BynderDriver::KEY) !== []) {
            return;
        }
    }

    /**
     * @return void
     */
    private function createBynderStorage(): void
    {
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);

        // Create Bynder storage
        $field_values = [
            'pid' => 0,
            'tstamp' => $this->executionTime,
            'crdate' => $this->executionTime,
            'name' => 'Bynder',
            'description' => 'Automatically created during the installation of EXT:bynder',
            'driver' => BynderDriver::KEY,
            'configuration' => '',
            'is_online' => 1,
            'is_browsable' => 1,
            'is_public' => 1,
            'is_writable' => 0,
            'is_default' => 0,
            // We use the processed file folder of the default storage as fallback
            'processingfolder' => '1:/_processed_/',
        ];

        $dbConnection = $connectionPool->getConnectionForTable('sys_file_storage');
        $dbConnection->insert('sys_file_storage', $field_values);
        $storageUid = (int)$dbConnection->lastInsertId('sys_file_storage');

        // Create file mount (for the editors)
        $field_values = [
            'pid' => 0,
            'tstamp' => $this->executionTime,
            'title' => 'Bynder',
            'description' => 'Automatically created during the installation of EXT:bynder',
            'path' => '',
            'base' => $storageUid,
        ];

        $dbConnection = $connectionPool->getConnectionForTable('sys_filemounts');
        $dbConnection->insert('sys_filemounts', $field_values);
    }
}
