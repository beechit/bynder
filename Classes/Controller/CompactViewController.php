<?php

namespace BeechIt\Bynder\Controller;

/*
 * This source file is proprietary property of Beech.it
 * Date: 20-2-18
 * All code (c) Beech.it all rights reserved
 */

use BeechIt\Bynder\Service\BynderService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Resource\Index\Indexer;
use TYPO3\CMS\Core\Resource\ResourceStorageInterface;
use TYPO3Fluid\Fluid\View\ViewInterface;

class CompactViewController
{
    /** @var \TYPO3Fluid\Fluid\View\ViewInterface */
    protected $view;

    /** @var \BeechIt\Bynder\Service\BynderService */
    protected $bynderService;

    public function __construct(
        ResourceStorageInterface $bynderStorage,
        BynderService $bynderService,
        Indexer $indexer,
        ViewInterface $view
    ) {
        $this->bynderStorage = $bynderStorage;
        $this->bynderService = $bynderService;
        $this->indexer = $indexer;
        $this->view = $view;
    }

    public function indexAction(ServerRequestInterface $request): ResponseInterface
    {
        $this->view->setTemplate('Index');

        $this->view->assignMultiple([
            'language' => $this->getBackendLanguage(),
            'apiBaseUrl' => $this->bynderService->getApiBaseUrl(),
            'element' => $request->getQueryParams()['element'],
        ]);

        return new HtmlResponse($this->view->render());
    }

    public function getFilesAction(ServerRequestInterface $request): ResponseInterface
    {
        // @todo verify oauth key/token and return message
        $files = [];
        $error = '';
        foreach ($request->getParsedBody()['files'] ?? [] as $fileIdentifier) {
            $file = $this->bynderStorage->getFile($fileIdentifier);
            if ($file) {
                // (Re)Fetch metadata
                $this->indexer->extractMetaData($file);
                $files[] = $file->getUid();
            }
        }

        if ($files === []) {
            $error = 'No files given/found';
        }

        return new JsonResponse(['files' => $files, 'error' => $error]);
    }

    protected function getBackendLanguage(): string
    {
        $backendUserAuthentication = $GLOBALS['BE_USER'];

        if ($backendUserAuthentication->uc['lang']) {
            return $backendUserAuthentication->uc['lang'];
        }

        if ($backendUserAuthentication->user['lang']) {
            return $backendUserAuthentication->user['lang'];
        }

        return 'en_EN';
    }
}
