<?php

namespace BeechIt\Bynder\XClass;

/*
 * This source file is proprietary property of Beech.it
 * Date: 20-2-18
 * All code (c) Beech.it all rights reserved
 */
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * Temporary XClass of Indexer until core is fixed
 *
 * Fix: https://forge.typo3.org/issues/83976
 *
 */
class Indexer extends \TYPO3\CMS\Core\Resource\Index\Indexer
{
    /**
     * Collects the information to be cached in sys_file
     *
     * @param string $identifier
     * @return array
     */
    protected function gatherFileInformationArray($identifier)
    {
        $fileInfo = $this->storage->getFileInfoByIdentifier($identifier);
        $fileInfo = $this->transformFromDriverFileInfoArrayToFileObjectFormat($fileInfo);
        $fileInfo['type'] = $this->getFileType($fileInfo['mime_type']);
        $fileInfo['sha1'] = $this->storage->hashFileByIdentifier($identifier, 'sha1');
        if (empty($fileInfo['extension'])) {
            $fileInfo['extension'] = PathUtility::pathinfo($fileInfo['name'], PATHINFO_EXTENSION);
        }
        $fileInfo['missing'] = 0;

        return $fileInfo;
    }
}