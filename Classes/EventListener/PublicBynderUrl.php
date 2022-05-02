<?php

namespace BeechIt\Bynder\EventListener;

use BeechIt\Bynder\Resource\BynderDriver;
use BeechIt\Bynder\Utility\ConfigurationUtility;
use TYPO3\CMS\Core\Resource\Exception\FileDoesNotExistException;
use TYPO3\CMS\Core\Resource\FileInterface;

class PublicBynderUrl
{
    public function __invoke(\TYPO3\CMS\Core\Resource\Event\GeneratePublicUrlForResourceEvent $event)
    {
        $resourceObject = $event->getResource();
        $relativeToCurrentScript = $event->isRelativeToCurrentScript();

        /** @var \BeechIt\Bynder\Resource\BynderDriver $driver */
        $driver = $event->getDriver();

        if ($resourceObject instanceof FileInterface && $resourceObject->getProperty('bynder') === true) {
            if ($resourceObject->getProperty('bynder_url')) {
                $publicUrl = $resourceObject->getProperty('bynder_url');
            } else {
                $publicUrl = ConfigurationUtility::getUnavailableImage($relativeToCurrentScript);
            }
        } elseif ($driver instanceof BynderDriver) {
            try {
                $publicUrl = $driver->getPublicUrl($resourceObject->getIdentifier());
            } catch (FileDoesNotExistException $e) {
                $publicUrl = ConfigurationUtility::getUnavailableImage($relativeToCurrentScript);
            }
        } else {
            return;
        }

        if ($publicUrl) {
            $event->setPublicUrl($publicUrl);
        }
    }
}
