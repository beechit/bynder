<?php
declare(strict_types=1);

namespace BeechIt\Bynder\Resource;

/*
 * This source file is proprietary property of Beech.it
 * Date: 19-2-18
 * All code (c) Beech.it all rights reserved
 */
use BeechIt\Bynder\Service\BynderService;
use Bynder\Api\Impl\AssetBankManager;
use TYPO3\CMS\Core\Resource\Driver\DriverInterface;
use TYPO3\CMS\Core\Resource\Exception;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use BeechIt\Bynder\Exception\NotImplementedException;

/**
 * Class BynderDriver
 */
class BynderDriver implements DriverInterface
{
    const KEY = 'bynder';

    /**
     * @var array
     */
    protected static $tempFiles = [];

    /**
     * @var string
     */
    protected $rootFolder = '';

    /**
     * The capabilities of this driver. See Storage::CAPABILITY_* constants for possible values.
     *
     * @var int
     */
    protected $capabilities = 0;

    /**
     * The storage uid the driver was instantiated for
     *
     * @var int
     */
    protected $storageUid;

    /**
     * The configuration of this driver
     *
     * @var array
     */
    protected $configuration = [];

    /**
     * @var AssetBankManager
     */
    protected $assetBankManager;

    /**
     * @var BynderService
     */
    protected $bynderService;

    /**
     * Creates this object.
     *
     * @param array $configuration
     */
    public function __construct(array $configuration = [])
    {
        $this->capabilities =
            ResourceStorage::CAPABILITY_BROWSABLE
            | ResourceStorage::CAPABILITY_PUBLIC
            | ResourceStorage::CAPABILITY_WRITABLE;
    }

    public function processConfiguration()
    {
    }

    /**
     * Checks a fileName for validity. This could be overidden in concrete
     * drivers if they have different file naming rules.
     *
     * @param string $fileName
     * @return bool TRUE if file name is valid
     */
    protected function isValidFilename($fileName)
    {
        if (strpos($fileName, '/') !== false) {
            return false;
        }
        if (!preg_match('/^[\\pL\\d[:blank:]._-]*$/u', $fileName)) {
            return false;
        }
        return true;
    }

    /**
     * Sets the storage uid the driver belongs to
     *
     * @param int $storageUid
     */
    public function setStorageUid($storageUid)
    {
        $this->storageUid = $storageUid;
    }

    /**
     * Returns the capabilities of this driver.
     *
     * @return int
     * @see Storage::CAPABILITY_* constants
     */
    public function getCapabilities()
    {
        return $this->capabilities;
    }

    public function initialize()
    {
    }

    /**
     * @param int $capabilities
     * @return int
     */
    public function mergeConfigurationCapabilities($capabilities)
    {
        $this->capabilities &= $capabilities;
        return $this->capabilities;
    }

    /**
     * @param int $capability
     * @return bool|int
     */
    public function hasCapability($capability)
    {
        return $this->capabilities & $capability === (int)$capability;
    }

    /**
     * @return bool
     */
    public function isCaseSensitiveFileSystem()
    {
        return true;
    }

    /**
     * @param string $fileName
     * @param string $charset
     * @return string
     */
    public function sanitizeFileName($fileName, $charset = '')
    {
        // Bynder allows all
        return $fileName;
    }

    /**
     * @param string $identifier
     * @return string
     */
    public function hashIdentifier($identifier)
    {
        return sha1($identifier);
    }

    /**
     * @return string
     */
    public function getRootLevelFolder()
    {
        return $this->rootFolder;
    }

    /**
     * @return string
     */
    public function getDefaultFolder()
    {
        return $this->rootFolder;
    }

    /**
     * For now we handle every folder as it's own parent
     *
     * @param string $fileIdentifier
     * @return string
     */
    public function getParentFolderIdentifierOfIdentifier($fileIdentifier)
    {
        return $this->rootFolder;
    }

    /**
     * Returns the (temp) public URL to a file.
     *
     * @param string $identifier
     * @return string
     * @throws Exception\FileDoesNotExistException
     */
    public function getPublicUrl($identifier)
    {
        $format = '';
        if (preg_match('/^processed_([0-9A-Z\-]{35})_([a-z]+)/', $identifier, $matches)) {
            $identifier = $matches[1];
            $format = $matches[2];
        }

        if (!in_array($format, ['mini', 'thul', 'webimage'])) {
            $format = 'webimage';
        }

        try {
            $fileInfo = $this->getBynderService()->getMediaInfo($identifier);
            return $fileInfo['thumbnails'][$format];
        } catch (\Exception $exception) {
            throw new Exception\FileDoesNotExistException(
                sprintf('Requested file "%s" coudn\'t be found', $identifier),
                1519115242,
                $exception
            );
        }
    }

    public function createFolder($newFolderName, $parentFolderIdentifier = '', $recursive = false)
    {
        throw new NotImplementedException(sprintf('Method %s::%s() is not implemented', __CLASS__, __METHOD__), 1519045381);
    }

    public function renameFolder($folderIdentifier, $newName)
    {
        throw new NotImplementedException(sprintf('Method %s::%s() is not implemented', __CLASS__, __METHOD__), 1519045382);
    }

    public function deleteFolder($folderIdentifier, $deleteRecursively = false)
    {
        throw new NotImplementedException(sprintf('Method %s::%s() is not implemented', __CLASS__, __METHOD__), 1519045383);
    }

    /**
     * @param string $fileIdentifier
     * @return bool
     */
    public function fileExists($fileIdentifier)
    {
        // We just assume that the processed file exists as this is just a CDN link
        if ($this->isProcessedFile($fileIdentifier)) {
            return true;
        }

        try {
            $asset = $this->getBynderService()->getMediaInfo($fileIdentifier);
            return true;
        } catch (\Exception $exception) {
            return false;
        }
    }

    /**
     * @param string $folderIdentifier
     * @return bool
     */
    public function folderExists($folderIdentifier)
    {
        // We only know the root folder
        return $folderIdentifier === $this->rootFolder;
    }

    /**
     * @param string $folderIdentifier
     * @return bool
     */
    public function isFolderEmpty($folderIdentifier)
    {
        // We just say that every folder has some content as
        // Bynder doesn't know the concept of folders and we don't want
        // any call like deleteFolder()
        return false;
    }

    /**
     * @param string $localFilePath
     * @param string $targetFolderIdentifier
     * @param string $newFileName
     * @param bool $removeOriginal
     * @return string|void
     * @throws NotImplementedException
     */
    public function addFile($localFilePath, $targetFolderIdentifier, $newFileName = '', $removeOriginal = true)
    {
        throw new NotImplementedException(sprintf('Method %s::%s() is not implemented', __CLASS__, __METHOD__), 1519045386);
    }

    /**
     * @param string $fileName
     * @param string $parentFolderIdentifier
     * @return string|void
     * @throws NotImplementedException
     */
    public function createFile($fileName, $parentFolderIdentifier)
    {
        throw new NotImplementedException(sprintf('Method %s::%s() is not implemented', __CLASS__, __METHOD__), 1519045387);
    }

    /**
     * @param string $fileIdentifier
     * @param string $targetFolderIdentifier
     * @param string $fileName
     * @return string|void
     * @throws NotImplementedException
     */
    public function copyFileWithinStorage($fileIdentifier, $targetFolderIdentifier, $fileName)
    {
        throw new NotImplementedException(sprintf('Method %s::%s() is not implemented', __CLASS__, __METHOD__), 1519045388);
    }

    /**
     * @param string $fileIdentifier
     * @param string $newName
     * @return string|void
     * @throws NotImplementedException
     */
    public function renameFile($fileIdentifier, $newName)
    {
        throw new NotImplementedException(sprintf('Method %s::%s() is not implemented', __CLASS__, __METHOD__), 1519045389);
    }

    /**
     * @param string $fileIdentifier
     * @param string $localFilePath
     * @return bool|void
     * @throws NotImplementedException
     */
    public function replaceFile($fileIdentifier, $localFilePath)
    {
        throw new NotImplementedException(sprintf('Method %s::%s() is not implemented', __CLASS__, __METHOD__), 1519045390);
    }

    /**
     * @param string $fileIdentifier
     * @return bool
     * @throws NotImplementedException
     */
    public function deleteFile($fileIdentifier)
    {
        // Deleting processed files isn't needed as this is just a link to a file in the CDN
        // to prevent false errors for the user we just tell the API that deleting was successful
        if ($this->isProcessedFile($fileIdentifier)) {
            return true;
        }
        throw new NotImplementedException(sprintf('Method %s::%s() is not implemented', __CLASS__, __METHOD__), 1519045391);
    }

    /**
     * Creates a (cryptographic) hash for a file.
     *
     * @param string $fileIdentifier
     * @param string $hashAlgorithm The hash algorithm to use
     * @return string
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    public function hash($fileIdentifier, $hashAlgorithm)
    {
        if (!in_array($hashAlgorithm, ['sha1', 'md5'], true)) {
            throw new \InvalidArgumentException('Hash algorithm "' . $hashAlgorithm . '" is not supported.', 1519131571);
        }
        switch ($hashAlgorithm) {
            case 'sha1':
                return sha1($fileIdentifier);
                break;
            case 'md5':
                return md5($fileIdentifier);
                break;
            default:
                throw new \RuntimeException('Hash algorithm ' . $hashAlgorithm . ' is not implemented.', 1519131572);
        }
    }

    /**
     * @param string $fileIdentifier
     * @param string $targetFolderIdentifier
     * @param string $newFileName
     * @return string|void
     * @throws NotImplementedException
     */
    public function moveFileWithinStorage($fileIdentifier, $targetFolderIdentifier, $newFileName)
    {
        throw new NotImplementedException(sprintf('Method %s::%s() is not implemented', __CLASS__, __METHOD__), 1519045392);
    }

    /**
     * @param string $sourceFolderIdentifier
     * @param string $targetFolderIdentifier
     * @param string $newFolderName
     * @return array|void
     * @throws NotImplementedException
     */
    public function moveFolderWithinStorage($sourceFolderIdentifier, $targetFolderIdentifier, $newFolderName)
    {
        throw new NotImplementedException(sprintf('Method %s::%s() is not implemented', __CLASS__, __METHOD__), 1519045393);
    }

    /**
     * @param string $sourceFolderIdentifier
     * @param string $targetFolderIdentifier
     * @param string $newFolderName
     * @return bool|void
     * @throws NotImplementedException
     */
    public function copyFolderWithinStorage($sourceFolderIdentifier, $targetFolderIdentifier, $newFolderName)
    {
        throw new NotImplementedException(sprintf('Method %s::%s() is not implemented', __CLASS__, __METHOD__), 1519045394);
    }

    /**
     * @param string $fileIdentifier
     * @return string
     * @throws Exception\FileDoesNotExistException
     */
    public function getFileContents($fileIdentifier)
    {
        try {
            $downloadLocation = $this->getAssetBankManager()->getMediaDownloadLocation($fileIdentifier)->wait();
            return file_get_contents($downloadLocation['s3_file']);
        } catch (\Exception $exception) {
            throw new Exception\FileDoesNotExistException(
                sprintf('Requested file "%s" coudn\'t be found', $fileIdentifier),
                1519115242,
                $exception
            );
        }
    }

    /**
     * @param string $fileIdentifier
     * @param string $contents
     * @return int|void
     * @throws NotImplementedException
     */
    public function setFileContents($fileIdentifier, $contents)
    {
        throw new NotImplementedException(sprintf('Method %s::%s() is not implemented', __CLASS__, __METHOD__), 1519045395);
    }

    /**
     * @param string $fileName
     * @param string $folderIdentifier
     * @return bool
     */
    public function fileExistsInFolder($fileName, $folderIdentifier)
    {
        if ($folderIdentifier !== $this->rootFolder) {
            return false;
        }

        // @todo: find a file by name instead of identifier
        return false;
    }

    /**
     * @param string $folderName
     * @param string $folderIdentifier
     * @return bool
     */
    public function folderExistsInFolder($folderName, $folderIdentifier)
    {
        // Currently we don't know the concept of folders within Bynder and for now always return false
        return false;
    }

    public function getFileForLocalProcessing($fileIdentifier, $writable = true)
    {
        return $this->saveFileToTemporaryPath($fileIdentifier);
    }

    /**
     * @param string $identifier
     * @return array
     */
    public function getPermissions($identifier)
    {
        return [
            'r' => $identifier === $this->rootFolder || $this->fileExists($identifier),
            'w' => false
        ];
    }

    /**
     * Directly output the contents of the file to the output
     * buffer. Should not take care of header files or flushing
     * buffer before. Will be taken care of by the Storage.
     *
     * @param string $identifier
     * @throws Exception\FileDoesNotExistException
     */
    public function dumpFileContents($identifier)
    {
        try {
            $downloadLocation = $this->getAssetBankManager()->getMediaDownloadLocation($identifier)->wait();
            readfile($downloadLocation['s3_file'], 0);
        } catch (\Exception $exception) {
            throw new Exception\FileDoesNotExistException(
                sprintf('Requested file "%s" coudn\'t be found', $identifier),
                1519115242,
                $exception
            );
        }
    }

    /**
     * @param string $folderIdentifier
     * @param string $identifier
     * @return bool
     */
    public function isWithin($folderIdentifier, $identifier)
    {
        if ($folderIdentifier === $this->rootFolder) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param string $fileIdentifier
     * @param array $propertiesToExtract
     * @return array
     * @throws Exception\FileDoesNotExistException
     */
    public function getFileInfoByIdentifier($fileIdentifier, array $propertiesToExtract = [])
    {
        if (preg_match('/^processed_([0-9A-Z\-]{35})_([a-z]+)/', $fileIdentifier, $matches)) {
            $fileIdentifier = $matches[1];
        }

        try {
            $mediaInfo = $this->getBynderService()->getMediaInfo($fileIdentifier);
        } catch (\Exception $exception) {
            throw new Exception\FileDoesNotExistException(
                sprintf('Requested file "%s" coudn\'t be found', $fileIdentifier),
                1519115242,
                $exception
            );
        }

        return $this->extractFileInformation($mediaInfo, $propertiesToExtract);
    }

    /**
     * @param string $folderIdentifier
     * @return array
     */
    public function getFolderInfoByIdentifier($folderIdentifier)
    {
        return [
            'identifier' => $folderIdentifier,
            'name' => 'Bynder',
            'mtime' => 0,
            'ctime' => 0,
            'storage' => $this->storageUid
        ];
    }

    /**
     * @param string $fileName
     * @param string $folderIdentifier
     * @return string
     */
    public function getFileInFolder($fileName, $folderIdentifier)
    {
        return '';
    }

    /**
     * @param string $folderIdentifier
     * @param int $start
     * @param int $numberOfItems
     * @param bool $recursive
     * @param array $filenameFilterCallbacks
     * @param string $sort
     * @param bool $sortRev
     * @return array
     */
    public function getFilesInFolder($folderIdentifier, $start = 0, $numberOfItems = 0, $recursive = false, array $filenameFilterCallbacks = [], $sort = '', $sortRev = false)
    {
        return [];
    }

    /**
     * @param string $folderName
     * @param string $folderIdentifier
     * @return string
     */
    public function getFolderInFolder($folderName, $folderIdentifier)
    {
        return '';
    }

    /**
     * @param string $folderIdentifier
     * @param int $start
     * @param int $numberOfItems
     * @param bool $recursive
     * @param array $folderNameFilterCallbacks
     * @param string $sort
     * @param bool $sortRev
     * @return array
     */
    public function getFoldersInFolder($folderIdentifier, $start = 0, $numberOfItems = 0, $recursive = false, array $folderNameFilterCallbacks = [], $sort = '', $sortRev = false)
    {
        return [];
    }

    /**
     * @param string $folderIdentifier
     * @param bool $recursive
     * @param array $filenameFilterCallbacks
     * @return int
     */
    public function countFilesInFolder($folderIdentifier, $recursive = false, array $filenameFilterCallbacks = [])
    {
        return 0;
    }

    /**
     * @param string $folderIdentifier
     * @param bool $recursive
     * @param array $folderNameFilterCallbacks
     * @return int
     */
    public function countFoldersInFolder($folderIdentifier, $recursive = false, array $folderNameFilterCallbacks = [])
    {
        return 0;
    }

    /**
     * @param string $fileIdentifier
     * @return bool
     */
    protected function isProcessedFile(string $fileIdentifier): bool
    {
        return (bool)preg_match('/^processed_([0-9A-Z\-]{35})_([a-z]+)/', $fileIdentifier);
    }

    /**
     * @return AssetBankManager
     * @throws \InvalidArgumentException
     */
    protected function getAssetBankManager(): AssetBankManager
    {
        if ($this->assetBankManager === null) {
            $this->assetBankManager = $this->getBynderService()
                ->getBynderApi()
                ->getAssetBankManager();
        }

        return $this->assetBankManager;
    }

    /**
     * @return BynderService
     * @throws \InvalidArgumentException
     */
    protected function getBynderService(): BynderService
    {
        if ($this->bynderService === null) {
            $this->bynderService = GeneralUtility::makeInstance(BynderService::class);
        }

        return $this->bynderService;
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
                'size', 'atime', 'mtime', 'ctime', 'mimetype', 'name', 'extension',
                'identifier', 'identifier_hash', 'storage', 'folder_hash'
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
                return $mediaInfo['name'] . '.' . $mediaInfo['extension'][0];
            case 'mimetype':
                // @todo: find beter way to determine mimetype
                return $mediaInfo['type'] . '/' . $mediaInfo['extension'][0];
            case 'identifier':
                return $mediaInfo['id'];
            case 'extension':
                return $mediaInfo['extension'][0];
            case 'storage':
                return $this->storageUid;
            case 'identifier_hash':
                return $this->hashIdentifier($mediaInfo['id']);
            case 'folder_hash':
                return $this->hashIdentifier('');

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
     * @param string $fileIdentifier
     * @return string The temporary path
     * @throws \RuntimeException
     * @throws Exception\FileDoesNotExistException
     */
    protected function saveFileToTemporaryPath($fileIdentifier): string
    {
        $temporaryPath = $this->getTemporaryPathForFile($fileIdentifier);
        $result = file_put_contents($temporaryPath, $this->getFileContents($fileIdentifier));
        if ($result === false) {
            throw new \RuntimeException(
                'Copying file "' . $fileIdentifier . '" to temporary path "' . $temporaryPath . '" failed.',
                1519208427
            );
        }
        return $temporaryPath;
    }

    /**
     * Returns a temporary path for a given file, including the file extension.
     *
     * @param string $fileIdentifier
     * @return string
     * @throws Exception\FileDoesNotExistException
     */
    protected function getTemporaryPathForFile($fileIdentifier): string
    {
        list($fileExtension) = $this->getFileInfoByIdentifier($fileIdentifier, ['extension']);
        $tempFile = GeneralUtility::tempnam('fal-tempfile-', '.' . $fileExtension);

        return self::$tempFiles[] = $tempFile;
    }

    /**
     * Cleanup temp files that a still present
     */
    public function __destruct()
    {
        foreach (self::$tempFiles as $tempFile) {
            GeneralUtility::unlink_tempfile($tempFile);
        }
    }
}