<?php

namespace BeechIt\Bynder\Utility;

use BeechIt\Bynder\Exception\InvalidExtensionConfigurationException;
use GuzzleHttp\HandlerStack;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Exception;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * Utility: Configuration
 */
class ConfigurationUtility
{
    const EXTENSION = 'bynder';

    /** @var array */
    private static $configuration;

    /**
     * @return array
     */
    protected static function getExtensionConfiguration(): array
    {
        if (!self::$configuration) {
            try {
                $configuration = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get(self::EXTENSION);
            } catch (Exception $e) {
            }

            self::$configuration = $configuration ?: [];
        }

        return self::$configuration;
    }

    /**
     * @return string
     * @throws \BeechIt\Bynder\Exception\InvalidExtensionConfigurationException
     */
    public static function getDomain(): string
    {
        $url = self::getExtensionConfiguration()['url'] ?? '';

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

        $domain = parse_url($url, PHP_URL_HOST) ?: '';
        if (empty($domain)) {
            throw new InvalidExtensionConfigurationException('Make sure Bynder domain is configured in extension manager', 1651241069);
        }

        return $domain;
    }

    /**
     * @return string
     * @throws \BeechIt\Bynder\Exception\InvalidExtensionConfigurationException
     */
    public static function getPermanentToken(): string
    {
        $token = self::getExtensionConfiguration()['permanent_token'] ?? '';

        if (empty($token)) {
            throw new InvalidExtensionConfigurationException('Make sure Bynder permanent token is configured in extension manager', 1651241125);
        }

        return $token;
    }

    /**
     * @return array
     */
    public static function getHTTPRequestOptions(): array
    {
        $httpOptions = $GLOBALS['TYPO3_CONF_VARS']['HTTP'] ?? [];
        $httpOptions['verify'] = filter_var($httpOptions['verify'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $httpOptions['verify'];

        if (isset($httpOptions['handler']) && is_array($httpOptions['handler'])) {
            $stack = HandlerStack::create();
            foreach ($httpOptions['handler'] ?? [] as $handler) {
                $stack->push($handler);
            }
            $httpOptions['handler'] = $stack;
        }

        return $httpOptions;
    }

    /**
     * @param  bool  $relativeToCurrentScript
     * @return string
     */
    public static function getUnavailableImage(bool $relativeToCurrentScript = false): string
    {
        $path = GeneralUtility::getFileAbsFileName(
            self::getExtensionConfiguration()['image_unavailable'] ?: 'EXT:bynder/Resources/Public/Icons/ImageUnavailable.svg'
        );

        return ($relativeToCurrentScript) ? PathUtility::getAbsoluteWebPath($path) : str_replace(Environment::getPublicPath() . '/', '', $path);
    }
}
