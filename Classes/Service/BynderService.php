<?php

namespace BeechIt\Bynder\Service;

/*
 * This source file is proprietary property of Beech.it
 * Date: 19-2-18
 * All code (c) Beech.it all rights reserved
 */
use BeechIt\Bynder\Exception\InvalidExtensionConfigurationException;
use Bynder\Api\BynderApiFactory;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extensionmanager\Utility\ConfigurationUtility;

/**
 * Class BynderService
 */
class BynderService implements SingletonInterface
{
    /**
     * @var string
     */
    protected $bynderIntegrationId = '8517905e-6c2f-47c3-96ca-0312027bbc95';

    /**
     * @var string
     */
    protected $apiBaseUrl;

    /**
     * @var string
     */
    protected $otfBaseUrl;

    /**
     * @var string
     */
    protected $oAuthConsumerKey;

    /**
     * @var string
     */
    protected $oAuthConsumerSecret;

    /**
     * @var string
     */
    protected $oAuthTokenKey;

    /**
     * @var string
     */
    protected $oAuthTokenSecret;

    /**
     * @var \Bynder\Api\Impl\BynderApi
     */
    protected $bynderApi;

    /**
     * @var FrontendInterface
     */
    protected $cache;

    public function __construct()
    {
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        if (class_exists(ConfigurationUtility::class)) {
            $configuration = $objectManager->get(ConfigurationUtility::class)->getCurrentConfiguration('bynder');
            $extensionConfiguration = [];
            foreach ($configuration as $key => $value) {
                $extensionConfiguration[$key] = $value['value'];
            }
        } else {
            $extensionConfiguration = $objectManager->get(ExtensionConfiguration::class)->get('bynder');
        }

        $this->apiBaseUrl = $this->cleanUrl($extensionConfiguration['url'] ?? '');
        $this->otfBaseUrl = $this->cleanUrl($extensionConfiguration['otfBaseUrl'] ?? '');
        $this->oAuthConsumerKey = $extensionConfiguration['consumer_key'] ?? null;
        $this->oAuthConsumerSecret = $extensionConfiguration['consumer_secret'] ?? null;
        $this->oAuthTokenKey = $extensionConfiguration['token_key'] ?? null;
        $this->oAuthTokenSecret = $extensionConfiguration['token_secret'] ?? null;

        if (empty($this->apiBaseUrl) || empty($this->oAuthConsumerKey) || empty($this->oAuthConsumerSecret) || empty($this->oAuthTokenKey) || empty($this->oAuthTokenSecret)) {
            throw new InvalidExtensionConfigurationException('Make sure all Bynder oAuth settings are set in extension manager', 1519051718);
        }
    }

    /**
     * @return \Bynder\Api\Impl\BynderApi
     * @throws \InvalidArgumentException
     */
    public function getBynderApi()
    {
        return BynderApiFactory::create(
            [
                'consumerKey' => $this->oAuthConsumerKey,
                'consumerSecret' => $this->oAuthConsumerSecret,
                'token' => $this->oAuthTokenKey,
                'tokenSecret' => $this->oAuthTokenSecret,
                'baseUrl' => $this->apiBaseUrl
            ]
        );
    }

    /**
     * @return string
     */
    public function getApiBaseUrl(): string
    {
        return $this->apiBaseUrl;
    }

    /**
     * @return string
     */
    public function getOtfBaseUrl(): string
    {
        return $this->otfBaseUrl;
    }

    /**
     * @return string
     */
    public function getOAuthConsumerKey(): string
    {
        return $this->oAuthConsumerKey;
    }

    /**
     * @return string
     */
    public function getOAuthConsumerSecret(): string
    {
        return $this->oAuthConsumerSecret;
    }

    /**
     * @return string
     */
    public function getOAuthTokenKey(): string
    {
        return $this->oAuthTokenKey;
    }

    /**
     * @return string
     */
    public function getOAuthTokenSecret(): string
    {
        return $this->oAuthTokenSecret;
    }

    /**
     * @param string $uuid
     * @return array
     * @throws \TYPO3\CMS\Core\Cache\Exception\NoSuchCacheException
     */
    public function getMediaInfo(string $uuid): array
    {
        // If $entry is null, it hasn't been cached. Calculate the value and store it in the cache:
        if (($fileInfo = $this->getCache()->get('mediainfo_' . $uuid)) === false) {
            $fileInfo = $this->getBynderApi()->getAssetBankManager()->getMediaInfo($uuid)->wait();
            $this->getCache()->set('mediainfo_' . $uuid, $fileInfo, [], 60);
        }

        return $fileInfo;
    }

    /**
     * @param string $uuid
     * @param string $uri
     * @param string|null $additionalInfo
     * @param \DateTime|null $dateTime
     * @return bool
     */
    public function addAssetUsage(string $uuid, string $uri, string $additionalInfo = null, \DateTime $dateTime = null): bool
    {
        try {
            $usage = $this->getBynderApi()->getAssetBankManager()->createUsage([
                'integration_id' => $this->bynderIntegrationId,
                'asset_id' => $uuid,
                'uri' => $uri,
                'additional' => $additionalInfo,
                'timestamp' => ($dateTime ?? new \DateTime())->format('c'),
            ])->wait();
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            $usage = null;
        }

        return !empty($usage);
    }

    /**
     * @param string $uuid
     * @param string $uri
     * @return bool
     */
    public function deleteAssetUsage(string $uuid, string $uri): bool
    {
        try {
            $response = $this->getBynderApi()->getAssetBankManager()->deleteUSage([
                'integration_id' => $this->bynderIntegrationId,
                'asset_id' => $uuid,
                'uri' => $uri,
            ])->wait();
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            $response = null;
        }

        return $response !== null && $response->getStatusCode() === 204;
    }

    /**
     * Clean url
     *
     * When url given, make sure url is a valid url
     *
     * @param string $url
     * @return string
     */
    public function cleanUrl(string $url): string
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

    /**
     * @return FrontendInterface
     * @throws \TYPO3\CMS\Core\Cache\Exception\NoSuchCacheException
     */
    protected function getCache(): FrontendInterface
    {
        if ($this->cache === null) {
            $this->cache = GeneralUtility::makeInstance(CacheManager::class)->getCache('bynder_api');
        }
        return $this->cache;
    }
}