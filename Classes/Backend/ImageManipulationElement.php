<?php

namespace BeechIt\Bynder\Backend;

/*
 * This source file is proprietary property of Beech.it
 * Date: 21-2-18
 * All code (c) Beech.it all rights reserved
 */
use BeechIt\Bynder\Resource\BynderDriver;

/**
 * Class ImageManipulationElement
 *
 * Override of ImageManipulationElement to hide cropper for Bynder files
 */
class ImageManipulationElement extends \TYPO3\CMS\Backend\Form\Element\ImageManipulationElement
{
    /**
     * @return array
     * @throws \TYPO3\CMS\Core\Imaging\ImageManipulation\InvalidConfigurationException
     */
    public function render()
    {
        $resultArray = $this->initializeResultArray();

        $file = $this->getFile($this->data['databaseRow'], $this->data['parameterArray']['fieldConf']['config']['file_field'] ?? 'uid_local');
        if (!$file || $file->getStorage()->getDriverType() === BynderDriver::KEY) {
            // Early return in case of Bynder file (or no file)
            return $resultArray;
        }
        return parent::render();
    }
}
