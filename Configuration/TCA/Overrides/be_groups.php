<?php
call_user_func(function ($extension, $table) {
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTcaSelectItem(
        $table,
        'file_permissions',
        [
            'LLL:EXT:' . $extension . '/Resources/Private/Language/locallang_be.xlf:be_groups.file_permissions.folder_add_via_bynder',
            'addFileViaBynder',
            'permissions-bynder-compact-view'
        ],
        'addFile', 'after');
}, 'bynder', 'be_groups');
