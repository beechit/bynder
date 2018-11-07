<?php

########################################################################
# Extension Manager/Repository config file for ext "bynder".
########################################################################

$EM_CONF[$_EXTKEY] = [
    'title' => 'Bynder integration for TYPO3',
    'description' => 'Integrate the Bynder DAM into TYPO3',
    'category' => 'distribution',
    'version' => '0.0.3',
    'state' => 'beta',
    'clearcacheonload' => 1,
    'author' => 'Frans Saris - Beech.it',
    'author_email' => 't3ext@beech.it',
    'constraints' => [
        'depends' => [
            'typo3' => '8.7.13-9.5.99'
        ],
        'conflicts' => [],
        'suggests' => [],
    ]
];
