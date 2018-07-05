<?php

namespace BeechIt\Bynder\Traits;

use BeechIt\Bynder\Resource\Helper;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\OnlineMedia\Helpers\OnlineMediaHelperRegistry;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\InvalidObjectException;

/**
 * Trait BynderHelper
 * @package BeechIt\Bynder\Traits
 */
trait BynderHelper
{

    /**
     * @param File $file
     * @return \BeechIt\Bynder\Resource\Helper\BynderHelper
     * @throws InvalidObjectException
     */
    protected function getBynderHelper(File $file = null): Helper\BynderHelper
    {
        $helper = null;
        if ($file !== null) {
            $helper = OnlineMediaHelperRegistry::getInstance()->getOnlineMediaHelper($file);
        } else {
            $helper = GeneralUtility::makeInstance(Helper\BynderHelper::class, 'bynder');
        }

        if ($helper instanceof Helper\BynderHelper) {
            return $helper;
        }
        throw new InvalidObjectException('Bynder Helper cannot be initialized', 1530782854569);
    }
}
