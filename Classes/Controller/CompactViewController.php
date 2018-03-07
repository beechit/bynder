<?php

namespace BeechIt\Bynder\Controller;

/*
 * This source file is proprietary property of Beech.it
 * Date: 20-2-18
 * All code (c) Beech.it all rights reserved
 */

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use BeechIt\Bynder\Service\BynderService;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Resource\Index\Indexer;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * Class CompactViewController
 */
class CompactViewController
{
    /**
     * Fluid Standalone View
     *
     * @var StandaloneView
     */
    protected $view;

    /**
     * TemplateRootPath
     *
     * @var string[]
     */
    protected $templateRootPaths = ['EXT:bynder/Resources/Private/Templates/CompactView'];

    /**
     * PartialRootPath
     *
     * @var string[]
     */
    protected $partialRootPaths = ['EXT:bynder/Resources/Private/Partials/CompactView'];

    /**
     * LayoutRootPath
     *
     * @var string[]
     */
    protected $layoutRootPaths = ['EXT:bynder/Resources/Private/Layouts/CompactView'];

    /**
     * @var BynderService
     */
    protected $bynderService;

    public function __construct()
    {
        $this->bynderService = GeneralUtility::makeInstance(BynderService::class);

        $this->view = GeneralUtility::makeInstance(StandaloneView::class);
        $this->view->setPartialRootPaths($this->partialRootPaths);
        $this->view->setTemplateRootPaths($this->templateRootPaths);
        $this->view->setLayoutRootPaths($this->layoutRootPaths);
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function indexAction(ServerRequestInterface $request, ResponseInterface $response)
    {
        $this->view->setTemplate('Index');

        $this->view->assignMultiple([
            'language' => $this->getBackendUserAuthentication()->uc['lang'] ?: ($this->getBackendUserAuthentication()->user['lang'] ?: 'en_EN'),
            'apiBaseUrl' => $this->bynderService->getApiBaseUrl(),
            'element' => $request->getQueryParams()['element'],
        ]);

        $response->getBody()->write($this->view->render());

        return $response;
    }

    public function getFilesAction(ServerRequestInterface $request, ResponseInterface $response)
    {
        $files = [];
        $error = '';
        $fileStorage = $this->getBynderStorage();
        foreach ($request->getParsedBody()['files'] ?? [] as $fileIdentifier) {
            $file = $fileStorage->getFile($fileIdentifier);
            if ($file) {
                // (Re)Fetch metadata
                $this->getIndexer($file->getStorage())->extractMetaData($file);
                $files[] = $file->getUid();
            }
        }

        if ($files === []) {
            $error = 'No files given/found';
        }

        $response->getBody()->write(json_encode(['files' => $files, 'error' => $error]));
        return $response;
    }

    protected function getBynderStorage(): ResourceStorage
    {
        /** @var ResourceStorage $fileStorage */
        foreach ($this->getBackendUserAuthentication()->getFileStorages() as $fileStorage) {
            if ($fileStorage->getDriverType() === 'bynder') {
                return $fileStorage;
            }
        }

        throw new \InvalidArgumentException('Missing Bynder file storage');
    }

    /**
     * @return BackendUserAuthentication
     */
    protected function getBackendUserAuthentication()
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * Gets the Indexer.
     *
     * @param ResourceStorage $storage
     * @return Indexer
     */
    protected function getIndexer(ResourceStorage $storage)
    {
        return GeneralUtility::makeInstance(Indexer::class, $storage);
    }
}