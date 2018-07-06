<?php

namespace BeechIt\Bynder\Utility;

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extensionmanager\Utility\ConfigurationUtility as CoreConfigurationUtility;

/**
 * Utility: Configuration
 * @package BeechIt\Bynder\Utility
 */
class ConfigurationUtility
{
    const EXTENSION = 'bynder';

    /**
     * @return array
     */
    public static function getExtensionConfiguration(): array
    {
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        if (class_exists(CoreConfigurationUtility::class)) {
            $configuration = $objectManager->get(CoreConfigurationUtility::class)->getCurrentConfiguration('bynder');
            $extensionConfiguration = [];
            foreach ($configuration as $key => $value) {
                $extensionConfiguration[$key] = $value['value'];
            }
        } else {
            $extensionConfiguration = $objectManager->get(ExtensionConfiguration::class)->get('bynder');
        }

        if (isset($extensionConfiguration['url'])) {
            $extensionConfiguration['url'] = static::cleanUrl($extensionConfiguration['url']);
        }
        $extensionConfiguration['otf_base_url'] = $extensionConfiguration['otf_base_url'] ??  $extensionConfiguration['otfBaseUrl'] ?? null;
        if (isset($extensionConfiguration['otf_base_url'])) {
            $extensionConfiguration['otf_base_url'] = static::cleanUrl($extensionConfiguration['otf_base_url']);
        }
        return $extensionConfiguration;
    }

    /**
     * Clean url
     *
     * When url given, make sure url is a valid url
     *
     * @param string $url
     * @return string
     */
    public static function cleanUrl(string $url): string
    {
        if ($url === '') {
            return $url;
        }

        // Make sure scheme is given
        $urlParts = parse_url($url);
        if (empty($urlParts['scheme'])) {
            $url = 'https://' . $url;
            $urlParts = parse_url($url);
        }

        // When there is a path make sure there is a leading slash
        if (!empty($urlParts['path'])) {
            $url = rtrim($url, '/') . '/';
        }

        return $url;
    }
}
