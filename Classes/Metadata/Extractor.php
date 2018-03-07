<?php

namespace BeechIt\Bynder\Metadata;

/*
 * This source file is proprietary property of Beech.it
 * Date: 20-2-18
 * All code (c) Beech.it all rights reserved
 */
use BeechIt\Bynder\Resource\BynderDriver;
use TYPO3\CMS\Core\Resource;

/**
 * Class Extractor
 */
class Extractor implements Resource\Index\ExtractorInterface
{
    /**
     * @return array
     */
    public function getFileTypeRestrictions()
    {
        return [];
    }

    /**
     * @return array
     */
    public function getDriverRestrictions()
    {
        return [BynderDriver::KEY];
    }

    /**
     * @return int
     */
    public function getPriority()
    {
        return 10;
    }

    /**
     * @return int
     */
    public function getExecutionPriority()
    {
        return 10;
    }

    /**
     * @param Resource\File $file
     * @return bool
     */
    public function canProcess(Resource\File $file)
    {
        return true;
    }

    /**
     * Extract metadata of Bynder assets
     *
     * @param Resource\File $file
     * @param array $previousExtractedData
     * @return array
     */
    public function extractMetaData(Resource\File $file, array $previousExtractedData = [])
    {
        $fileInfo = $file->getStorage()->getFileInfoByIdentifier(
            $file->getIdentifier(),
            [
                'title',
                'description',
                'width',
                'height',
                'copyright',
                'keywords',
            ]
        );
        return array_merge($previousExtractedData, $fileInfo);
    }
}