<?php

namespace BeechIt\Bynder\Resource\Helper;

use BeechIt\Bynder\Resource\BynderDriver;
use BeechIt\Bynder\Traits\BynderService;
use BeechIt\Bynder\Traits\BynderStorage;
use TYPO3\CMS\Core\Resource\Exception\FileDoesNotExistException;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;

/**
 * Class BynderVideoHelper
 * @package BeechIt\Bynder\Resource\Helper
 */
class BynderHelper extends \TYPO3\CMS\Core\Resource\OnlineMedia\Helpers\AbstractOnlineMediaHelper
{

    use BynderService;
    use BynderStorage;

    /**
     * Try to transform given URL to a File
     *
     * @param string $url
     * @param Folder $targetFolder
     * @return File
     */
    public function transformUrlToFile($url, Folder $targetFolder): File
    {
        DebuggerUtility::var_dump(__METHOD__);
    }

    /**
     * Get public url
     *
     * Return NULL if you want to use core default behaviour
     *
     * @param File $file
     * @param bool $relativeToCurrentScript
     * @return string|null
     */
    public function getPublicUrl(File $file, $relativeToCurrentScript = false): string
    {
        $identifier = $this->getBynderFileIdentifier($file);
        if (!empty($identifier)) {
            switch ($file->getMimeType()) {
                case 'bynder/' . BynderDriver::ASSET_TYPE_IMAGE:
                    try {
                        $mediaInfo = $this->getBynderService()->getMediaInfo($identifier);
                        return $mediaInfo['thumbnails']['webimage'];
                    } catch (\Exception $e) {
                        return '/typo3conf/ext/bynder/Resources/Public/Icons/Extension.svg';
                    }
                    break;
                // TODO
                // case 'bynder' . BynderDriver::ASSET_TYPE_DOCUMENT:
            }
        }
        return null;
    }

    public function generatePublicUrl($file, $relativeToCurrentScript, $width, $height): string
    {

    }

    /**
     * Get local absolute file path to preview image
     *
     * Return an empty string when no preview image is available
     *
     * @param File $file
     * @return string
     * @throws FileDoesNotExistException
     */
    public function getPreviewImage(File $file): string
    {
        try {
            return $this->downloadThumbnailToTemporaryFilePath($file);
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * Get meta data for OnlineMedia item
     *
     * See $GLOBALS[TCA][sys_file_metadata][columns] for possible fields to fill/use
     *
     * @param File $file
     * @return array with metadata
     */
    public function getMetaData(File $file): array
    {
        return $this->extractMetaData($this->getBynderFileIdentifier($file), ['title', 'description', 'width', 'height', 'copyright', 'keywords']);
    }

    /**
     * @param string $identifier
     * @param array $propertiesToExtract
     * @return array
     */
    public function extractMetaData($identifier, $propertiesToExtract = [])
    {
        try {
            $mediaInfo = $this->getBynderService()->getMediaInfo($identifier);
            return $this->extractFileInformation($mediaInfo, $propertiesToExtract);
        } catch (\Exception $e) {
        }
        return [];
    }

    /**
     * @param File $file
     * @return string
     */
    protected function getBynderFileIdentifier(File $file): string
    {
        return $file->getProperty('identifier');
    }

    /**
     * Extracts information about a file from the filesystem.
     *
     * @param array $mediaInfo as returned from getAssetBankManager()->getMediaInfo()
     * @param array $propertiesToExtract array of properties which should be returned, if empty all will be extracted
     * @return array
     */
    protected function extractFileInformation(array $mediaInfo, array $propertiesToExtract = []): array
    {
        if (empty($propertiesToExtract)) {
            $propertiesToExtract = [
                'size',
                'atime',
                'mtime',
                'ctime',
                'mimetype',
                'name',
                'extension',
                'identifier',
                'identifier_hash',
                'storage',
                'folder_hash'
            ];
        }
        $fileInformation = [];
        foreach ($propertiesToExtract as $property) {
            $fileInformation[$property] = $this->getSpecificFileInformation($mediaInfo, $property);
        }
        return $fileInformation;
    }

    /**
     * Extracts a specific FileInformation from the FileSystems.
     *
     * @param array $mediaInfo
     * @param string $property
     *
     * @return bool|int|string
     * @throws \InvalidArgumentException
     */
    protected function getSpecificFileInformation($mediaInfo, $property)
    {
        switch ($property) {
            case 'size':
                return $mediaInfo['fileSize'];
            case 'atime':
                return strtotime($mediaInfo['dateModified']);
            case 'mtime':
                return strtotime($mediaInfo['dateModified']);
            case 'ctime':
                return strtotime($mediaInfo['dateCreated']);
            case 'name':
                return $mediaInfo['name'] . '.bynder';
            case 'mimetype':
                return 'bynder/' . $mediaInfo['type'];
            case 'identifier':
                return $mediaInfo['id'];
            case 'extension':
                return 'bynder';
            case 'identifier_hash':
                return sha1($mediaInfo['id']);
            case 'storage':
                return $this->getBynderStorage()->getUid();
            case 'folder_hash':
                return sha1('bynder' . $this->getBynderStorage()->getUid());

            // Metadata
            case 'title':
                return $mediaInfo['name'];
            case 'description':
                return $mediaInfo['description'];
            case 'width':
                return $mediaInfo['width'];
            case 'height':
                return $mediaInfo['height'];
            case 'copyright':
                return $mediaInfo['copyright'];
            case 'keywords':
                return implode(', ', $mediaInfo['tags'] ?? []);
            default:
                throw new \InvalidArgumentException(sprintf('The information "%s" is not available.', $property), 1519130380);
        }
    }

    /**
     * Save a file to a temporary path and returns that path.
     *
     * @param File $file
     * @return string|null The temporary path
     * @throws FileDoesNotExistException
     * @throws \TYPO3\CMS\Core\Cache\Exception\NoSuchCacheException
     */
    protected function downloadThumbnailToTemporaryFilePath(File $file): string
    {
        try {
            $mediaInfo = $this->getBynderService()->getMediaInfo($this->getBynderFileIdentifier($file));
            $url = $mediaInfo['thumbnails']['thul'];
            $temporaryPath = $this->getTemporaryPathForFile($url);
            if (!is_file($temporaryPath)) {
                $report = [];
                $data = GeneralUtility::getUrl($url, 0, false, $report);
                if (!empty($data)) {
                    $result = GeneralUtility::writeFile($temporaryPath, $data);
                    if ($result === false) {
                        throw new \RuntimeException(
                            'Copying file "' . $file->getIdentifier() . '" to temporary path "' . $temporaryPath . '" failed.',
                            1519208427
                        );
                    }
                }
            }
        } catch (\GuzzleHttp\Exception\RequestException $exception) {
            throw new FileDoesNotExistException(
                sprintf('Requested file " % s" coudn\'t be found', $file->getIdentifier()),
                1519115242,
                $exception
            );
        }

        return $temporaryPath ?? null;
    }

    /**
     * Returns a temporary path for a given file, including the file extension.
     *
     * @param string $url
     * @return string
     */
    protected function getTemporaryPathForFile($url): string
    {
        $temporaryPath = PATH_site . 'typo3temp/assets/' . BynderDriver::KEY . '/';
        if (!is_dir($temporaryPath)) {
            GeneralUtility::mkdir_deep($temporaryPath);
        }
        $info = pathinfo($url);
        return $temporaryPath . $info['filename'] . '.' . $info['extension'];
    }

}