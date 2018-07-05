<?php

namespace BeechIt\Bynder\Traits;

use BeechIt\Bynder\Service;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Trait BynderService
 * @package BeechIt\Bynder\Traits
 */
trait BynderService
{

    /**
     * @return \BeechIt\Bynder\Service\BynderService
     */
    protected function getBynderService(): Service\BynderService
    {
        return GeneralUtility::makeInstance(Service\BynderService::class);
    }


}
