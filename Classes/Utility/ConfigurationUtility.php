<?php

namespace BeechIt\Bynder\Utility;

use BeechIt\Bynder\Exception\InvalidExtensionConfigurationException;
use BeechIt\Bynder\Resource\BynderDriver;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
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
     * @param string $allowedElements
     * @return array
     * @throws InvalidExtensionConfigurationException
     */
    public static function getAssetTypesByAllowedElements($allowedElements): array
    {
        $assetTypes = [];
        if (empty($allowedElements)) {
            $assetTypes = [BynderDriver::ASSET_TYPE_IMAGE, BynderDriver::ASSET_TYPE_VIDEO];
        } else {
            $allowedElements = GeneralUtility::trimExplode(',', strtolower($allowedElements), true);
            $allowed = [
                BynderDriver::ASSET_TYPE_IMAGE => ((self::getExtensionConfiguration())['asset_type_image'] ?? 'jpg,png,gif'),
                BynderDriver::ASSET_TYPE_VIDEO => ((self::getExtensionConfiguration())['asset_type_video'] ?? 'mp4,mov')
            ];

            foreach (array_filter($allowed) as $key => $elements) {
                foreach (GeneralUtility::trimExplode(',', $elements, true) as $element) {
                    if (in_array($element, $allowedElements)) {
                        $assetTypes[] = $key;
                        break;
                    }
                }
            }
        }

        return $assetTypes;
    }

    /**
     * @param boolean $relativeToCurrentScript
     * @return string
     * @throws InvalidExtensionConfigurationException
     */
    public static function getUnavailableImage($relativeToCurrentScript = false): string
    {
        $path = GeneralUtility::getFileAbsFileName(
            (self::getExtensionConfiguration())['image_unavailable'] ??
            'EXT:bynder/Resources/Public/Icons/ImageUnavailable.svg'
        );

        return ($relativeToCurrentScript) ? PathUtility::getAbsoluteWebPath($path) : str_replace(PATH_site, '', $path);
    }

    /**
     * @return array
     * @throws InvalidExtensionConfigurationException
     */
    public static function getBynderApiFactoryCredentials(): array
    {
        $credentials = [
            'baseUrl' => static::getApiBaseUrl(),
            'consumerKey' => ((self::getExtensionConfiguration())['consumer_key'] ?? ''),
            'consumerSecret' => ((self::getExtensionConfiguration())['consumer_secret'] ?? ''),
            'token' => ((self::getExtensionConfiguration())['token_key'] ?? ''),
            'tokenSecret' => ((self::getExtensionConfiguration())['token_secret'] ?? ''),
        ];
        return $credentials;
    }

    /**
     * @return string
     * @throws InvalidExtensionConfigurationException
     */
    public static function getApiBaseUrl(): string
    {
        return static::cleanUrl((self::getExtensionConfiguration())['url']);
    }

    /**
     * @return string
     * @throws InvalidExtensionConfigurationException
     */
    public static function getOnTheFlyBaseUrl(): string
    {
        return static::cleanUrl((self::getExtensionConfiguration())['otf_base_url']);
    }

    /**
     * @return boolean
     * @throws InvalidExtensionConfigurationException
     */
    public static function isOnTheFlyConfigured(): bool
    {
        return !empty((self::getExtensionConfiguration())['otf_base_url']);
    }

    /**
     * @return array
     * @throws InvalidExtensionConfigurationException
     */
    public static function getExtensionConfiguration(): array
    {
        static $configuration;
        if ($configuration === null) {
            $configuration = [
                'url' => '',
                'otf_base_url' => '',
                'consumer_key' => '',
                'consumer_secret' => '',
                'token_key' => '',
                'token_secret' => '',
                'image_unavailable' => 'EXT:bynder/Resources/Public/Icons/ImageUnavailable.svg',
                'asset_type_image' => 'jpg,png,gif',
                'asset_type_video' => 'mp4'
            ];

            $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
            if (class_exists(CoreConfigurationUtility::class)) {
                $currentConfiguration = $objectManager->get(CoreConfigurationUtility::class)->getCurrentConfiguration('bynder');
                $configuration = [];
                foreach ($currentConfiguration as $key => $value) {
                    $configuration[$key] = $value['value'];
                }
            } else {
                \TYPO3\CMS\Core\Utility\ArrayUtility::mergeRecursiveWithOverrule($configuration, (array)$objectManager->get(ExtensionConfiguration::class)->get('bynder'));
            }

            if (empty($configuration['url']) ||
                empty($configuration['consumer_key']) || empty($configuration['consumer_secret']) ||
                empty($configuration['token_key']) || empty($configuration['token_secret'])
            ) {
                throw new InvalidExtensionConfigurationException('Make sure all Bynder oAuth settings are set in extension manager', 1519051718);
            }
        }
        return $configuration;
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
