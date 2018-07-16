<?php

namespace BeechIt\Bynder\Service;

/*
 * This source file is proprietary property of Beech.it
 * Date: 19-2-18
 * All code (c) Beech.it all rights reserved
 */
use BeechIt\Bynder\Utility\ConfigurationUtility;
use Bynder\Api\BynderApiFactory;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

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
     * @var \Bynder\Api\Impl\BynderApi
     */
    protected $bynderApi;

    /**
     * @var FrontendInterface
     */
    protected $cache;

    /**
     * @return \Bynder\Api\Impl\BynderApi
     * @throws \InvalidArgumentException
     */
    public function getBynderApi()
    {
        return BynderApiFactory::create(ConfigurationUtility::getBynderApiFactoryCredentials());
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
