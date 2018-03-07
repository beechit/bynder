<?php
defined('TYPO3_MODE') || die('Access denied.');

// Register inlineController override to add the Bynder button
$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1433198160] = [
    'nodeName' => 'inline',
    'priority' => 50,
    'class' => \BeechIt\Bynder\Backend\InlineControlContainer::class,
];
$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1433198161] = [
    'nodeName' => 'imageManipulation',
    'priority' => 50,
    'class' => \BeechIt\Bynder\Backend\ImageManipulationElement::class,
];

// Register the FAL driver for Bynder
$GLOBALS['TYPO3_CONF_VARS']['SYS']['fal']['registeredDrivers']['bynder'] = [
    'class' => \BeechIt\Bynder\Resource\BynderDriver::class,
    'label' => 'Bynder',
    // @todo: is currently needed to not break the backend. Needs to be fixed in TYPO3
    'flexFormDS' => 'FILE:EXT:bynder/Configuration/FlexForms/BynderDriverFlexForm.xml'
];

// Register slot to use Bynder API for processed file
$signalSlotDispatcher = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\SignalSlot\Dispatcher::class);
$signalSlotDispatcher->connect(
    TYPO3\CMS\Core\Resource\ResourceStorage::class,
    \TYPO3\CMS\Core\Resource\Service\FileProcessingService::SIGNAL_PreFileProcess,
    \BeechIt\Bynder\Resource\AssetProcessing::class,
    'processFile'
);
$signalSlotDispatcher->connect(
    \TYPO3\CMS\Core\Resource\ResourceStorage::class,
    \TYPO3\CMS\Core\Resource\ResourceStorage::SIGNAL_PreGeneratePublicUrl,
    \BeechIt\Bynder\Slot\PublicUrlSlot::class,
    'getPublicUrl'
);
$signalSlotDispatcher->connect(
    \TYPO3\CMS\Extensionmanager\Utility\InstallUtility::class,
    'afterExtensionInstall',
    \BeechIt\Bynder\Slot\InstallSlot::class,
    'createBynderFileStorage'
);
unset($signalSlotDispatcher);

// Register hooks to post/delete usage registration
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][] =
    \BeechIt\Bynder\Hook\DataHandlerHook::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass'][] =
    \BeechIt\Bynder\Hook\DataHandlerHook::class;

// Register BynderAPI cache
if (!is_array($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['bynder_api'])) {
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['bynder_api'] = [];
}

// Register Icons
$iconRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconRegistry::class);
$iconRegistry->registerIcon(
    'actions-bynder-compact-view',
    \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
    ['source' => 'EXT:bynder/Resources/Public/Icons/Extension.svg']
);
unset($iconRegistry);

// Register the extractor to fetch metadata from Bynder
$extractorRegistry = \TYPO3\CMS\Core\Resource\Index\ExtractorRegistry::getInstance();
$extractorRegistry->registerExtractionService(\BeechIt\Bynder\Metadata\Extractor::class);
unset($extractorRegistry);

if (!\TYPO3\CMS\Core\Core\Bootstrap::usesComposerClassLoading()) {
    require_once(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('bynder')
        . 'Resources/Private/PHP/autoload.php');
}

// XClass to fix core issue https://forge.typo3.org/issues/83976
$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][TYPO3\CMS\Core\Resource\Index\Indexer::class] = [
    'className' => \BeechIt\Bynder\XClass\Indexer::class,
];