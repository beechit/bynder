<?php

namespace BeechIt\Bynder\Resource\Index;

/*
 * This source file is proprietary property of Beech.it
 * Date: 20-2-18
 * All code (c) Beech.it all rights reserved
 */
use BeechIt\Bynder\Resource\BynderDriver;
use TYPO3\CMS\Core\Resource;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;

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
        return Resource\OnlineMedia\Helpers\OnlineMediaHelperRegistry::getInstance()->getOnlineMediaHelper($file) !== false;
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
        /** @var Resource\OnlineMedia\Helpers\OnlineMediaHelperInterface $helper */
        $helper = Resource\OnlineMedia\Helpers\OnlineMediaHelperRegistry::getInstance()->getOnlineMediaHelper($file);
        $output = $previousExtractedData;
        ArrayUtility::mergeRecursiveWithOverrule($output, ($helper !== false ? $helper->getMetaData($file) : []));
        return $output;
    }
}
