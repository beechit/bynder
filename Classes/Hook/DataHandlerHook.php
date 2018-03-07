<?php

namespace BeechIt\Bynder\Hook;

/*
 * This source file is proprietary property of Beech.it
 * Date: 23-2-18
 * All code (c) Beech.it all rights reserved
 */
use BeechIt\Bynder\Resource\BynderDriver;
use BeechIt\Bynder\Service\BynderService;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Resource\Exception\FileDoesNotExistException;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class DataHandlerHook
 *
 * Notify Bynder about use of files
 */
class DataHandlerHook
{
    /**
     * After datamap operations
     *
     * @param string $status DataHandler operation status, either 'new' or 'update'
     * @param string $table The DB table the operation was carried out on
     * @param mixed $recordId The record's uid for update records, a string to look the record's uid up after it has been created
     * @param array $updatedFields Array of changed fields and their new values
     * @param DataHandler $dataHandler DataHandler parent object
     */
    public function processDatamap_afterAllOperations(DataHandler $dataHandler)
    {
        if (empty($dataHandler->newRelatedIDs['sys_file_reference'])) {
            return;
        }

        // As the foreign id is only set after all normal datamap operations
        // we this hook to process all new relations
        foreach ($dataHandler->datamap['sys_file_reference'] as $id => $record) {
            if (!is_numeric($id)) {
                $this->processNewSysFileReference((int)$dataHandler->substNEWwithIDs[$id]);
            }
        }
    }

    /**
     * Check if file reference is linked to Bynder asset and post usage
     *
     * @param int $uid
     */
    protected function processNewSysFileReference(int $uid)
    {
        $record = BackendUtility::getRecord('sys_file_reference', $uid);

        // Try to find the file
        try {
            $file = ResourceFactory::getInstance()->getFileObject($record['uid_local']);
        } catch (FileDoesNotExistException $e) {
            return;
        } catch (\InvalidArgumentException $e) {
            return;
        }

        // Check if it's a file from Bynder
        if ($file->getStorage()->getDriverType() !== BynderDriver::KEY) {
            return;
        }

        // @todo: adjust the $uri parameter to post real links
        // left this out for now because we also need to take care
        // of updating the usage is the link changes

        // Notify Bynder that the file is used
        GeneralUtility::makeInstance(BynderService::class)->addAssetUsage(
            $file->getIdentifier(),
            $record['tablenames'] . ':' . $record['uid_foreign'],
            $this->getUsageReference($record['tablenames'], $record['uid_foreign'])
        );
    }

    /**
     * hook that is called when an element shall get deleted
     *
     * @param string $table the table of the record
     * @param int $id the ID of the record
     * @param array $record The accordant database record
     * @param bool $recordWasDeleted can be set so that other hooks or
     * @param DataHandler $dataHandler reference to the main DataHandler object
     */
    public function processCmdmap_deleteAction($table, $id, array $record, &$recordWasDeleted, DataHandler $dataHandler)
    {
        // Only proceed for new file references
        if ($table !== 'sys_file_reference') {
            return;
        }

        // Try to find the file
        try {
            $file = ResourceFactory::getInstance()->getFileObject($record['uid_local']);
        } catch (FileDoesNotExistException $e) {
            return;
        } catch (\InvalidArgumentException $e) {
            return;
        }

        // Check if it's a file from Bynder
        if ($file->getStorage()->getDriverType() !== BynderDriver::KEY) {
            return;
        }

        // Notify Bynder that the file is used
        GeneralUtility::makeInstance(BynderService::class)->deleteAssetUsage(
            $file->getIdentifier(),
            $record['tablenames'] . ':' . $record['uid_foreign']
        );
    }

    /**
     * Get string representation of usage
     *
     * @param string $table
     * @param int $uid
     * @return string
     */
    protected function getUsageReference(string $table, int $uid): string
    {
        $record = BackendUtility::getRecord($table, $uid);
        if ($record) {
            return BackendUtility::getRecordTitle($table, $record, false, true);
        } else {
            return $table;
        }
    }
}