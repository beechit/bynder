<?php

namespace BeechIt\Bynder\Factory;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

class StandaloneViewFactory
{
    public function __invoke(): StandaloneView
    {
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setPartialRootPaths(['EXT:bynder/Resources/Private/Partials/CompactView']);
        $view->setTemplateRootPaths(['EXT:bynder/Resources/Private/Templates/CompactView']);
        $view->setLayoutRootPaths(['EXT:bynder/Resources/Private/Layouts/CompactView']);

        return $view;
    }
}
