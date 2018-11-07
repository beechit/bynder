<?php
declare(strict_types=1);

namespace BeechIt\Bynder\Resource;

/*
 * This source file is proprietary property of Beech.it
 * Date: 19-2-18
 * All code (c) Beech.it all rights reserved
 */
use BeechIt\Bynder\Exception\NotImplementedException;
use BeechIt\Bynder\Resource\Helper\BynderHelper;
use TYPO3\CMS\Core\Resource\Driver\DriverInterface;
use TYPO3\CMS\Core\Resource\Exception;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;

/**
 * Class BynderDriver
 */
class BynderDriver implements DriverInterface
{
    use \BeechIt\Bynder\Traits\BynderService;

    const KEY = 'bynder';

    const ASSET_TYPE_VIDEO = 'video';
    const ASSET_TYPE_IMAGE = 'image';

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
     * Processes the configuration for this driver.
     */
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

    /**
     * Initializes this object. This is called by the storage after the driver
     * has been attached.
     */
    public function initialize()
    {
        $this->capabilities =
            ResourceStorage::CAPABILITY_BROWSABLE
            | ResourceStorage::CAPABILITY_PUBLIC
            | ResourceStorage::CAPABILITY_WRITABLE;
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
     * Cleans a fileName from not allowed characters
     *
     * @param string $fileName
     * @param string $charset Charset of the a fileName (defaults to current charset; depending on context)
     * @return string the cleaned filename
     */
    public function sanitizeFileName($fileName, $charset = '')
    {
        // Bynder allows all
        return $fileName;
    }

    /**
     * Hashes a file identifier, taking the case sensitivity of the file system
     * into account. This helps mitigating problems with case-insensitive
     * databases.
     *
     * @param string $identifier
     * @return string
     */
    public function hashIdentifier($identifier)
    {
        return $this->hash($identifier, 'sha1');
    }

    /**
     * Returns the identifier of the root level folder of the storage.
     *
     * @return string
     */
    public function getRootLevelFolder()
    {
        return $this->rootFolder;
    }

    /**
     * Returns the identifier of the default folder new files should be put into.
     *
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
        DebuggerUtility::var_dump(__METHOD__);
        return $identifier;

        $format = '';
        if (preg_match('/^processed_([0-9A-Z\-]{35})_([a-z]+)/', $identifier, $matches)) {
            $identifier = $matches[1];
            $format = $matches[2];
        }

        try {
            $fileInfo = $this->getBynderService()->getMediaInfo($identifier);
            switch ($fileInfo['type']) {
                case BynderDriver::ASSET_TYPE_IMAGE:
                    if (!in_array($format, ['mini', 'thul', 'webimage'])) {
                        $format = 'webimage';
                    }
                    return $fileInfo['thumbnails'][$format];
                case BynderDriver::ASSET_TYPE_VIDEO:
                    $urls = array_filter($fileInfo['videoPreviewURLs'], function ($url) {
                        return preg_match('/mp4$/i', $url);
                    });
                    if (empty($urls)) {
                        throw new Exception\FileDoesNotExistException(
                            'mp4 not found in video URL\'s',
                            1530626116454
                        );
                    }
                    return reset($urls);
            }
        } catch (\Exception $exception) {
            throw new Exception\FileDoesNotExistException(
                sprintf('Requested file "%s" couldn\'t be found', $identifier),
                1519115242,
                $exception
            );
        }
    }

    /**
     * Creates a folder, within a parent folder.
     * If no parent folder is given, a root level folder will be created
     *
     * @param string $newFolderName
     * @param string $parentFolderIdentifier
     * @param bool $recursive
     * @return string the Identifier of the new folder
     * @throws NotImplementedException
     */
    public function createFolder($newFolderName, $parentFolderIdentifier = '', $recursive = false)
    {
        throw new NotImplementedException(sprintf('Method %s::%s() is not implemented', __CLASS__, __METHOD__), 1519045381);
    }

    /**
     * Renames a folder in this storage.
     *
     * @param string $folderIdentifier
     * @param string $newName
     * @return array A map of old to new file identifiers of all affected resources
     * @throws NotImplementedException
     */
    public function renameFolder($folderIdentifier, $newName)
    {
        throw new NotImplementedException(sprintf('Method %s::%s() is not implemented', __CLASS__, __METHOD__), 1519045382);
    }

    /**
     * Removes a folder in filesystem.
     *
     * @param string $folderIdentifier
     * @param bool $deleteRecursively
     * @return bool
     * @throws NotImplementedException
     */
    public function deleteFolder($folderIdentifier, $deleteRecursively = false)
    {
        throw new NotImplementedException(sprintf('Method %s::%s() is not implemented', __CLASS__, __METHOD__), 1519045383);
    }


    /**
     * Checks if a file exists.
     *
     * @param string $fileIdentifier
     * @return bool
     */
    public function fileExists($fileIdentifier)
    {
        // We just assume that the processed file exists as this is just a CDN link
        return (!empty($fileIdentifier));
    }

    /**
     * Checks if a folder exists.
     *
     * @param string $folderIdentifier
     * @return bool
     */
    public function folderExists($folderIdentifier)
    {
        // We only know the root folder
        return $folderIdentifier === $this->rootFolder;
    }

    /**
     * Checks if a folder contains files and (if supported) other folders.
     *
     * @param string $folderIdentifier
     * @return bool TRUE if there are no files and folders within $folder
     */
    public function isFolderEmpty($folderIdentifier)
    {
        // We only know the root folder
        return $folderIdentifier === $this->rootFolder;
    }

    /**
     * Adds a file from the local server hard disk to a given path in TYPO3s
     * virtual file system. This assumes that the local file exists, so no
     * further check is done here! After a successful the original file must
     * not exist anymore.
     *
     * @param string $localFilePath (within PATH_site)
     * @param string $targetFolderIdentifier
     * @param string $newFileName optional, if not given original name is used
     * @param bool $removeOriginal if set the original file will be removed
     *                                after successful operation
     * @return string the identifier of the new file
     * @throws NotImplementedException
     */
    public function addFile($localFilePath, $targetFolderIdentifier, $newFileName = '', $removeOriginal = true)
    {
        throw new NotImplementedException(sprintf('Method %s::%s() is not implemented', __CLASS__, __METHOD__), 1519045386);
    }

    /**
     * Creates a new (empty) file and returns the identifier.
     *
     * @param string $fileName
     * @param string $parentFolderIdentifier
     * @return string
     * @throws NotImplementedException
     */
    public function createFile($fileName, $parentFolderIdentifier)
    {
        throw new NotImplementedException(sprintf('Method %s::%s() is not implemented', __CLASS__, __METHOD__), 1519045387);
    }

    /**
     * Copies a file *within* the current storage.
     * Note that this is only about an inner storage copy action,
     * where a file is just copied to another folder in the same storage.
     *
     * @param string $fileIdentifier
     * @param string $targetFolderIdentifier
     * @param string $fileName
     * @return string the Identifier of the new file
     * @throws NotImplementedException
     */
    public function copyFileWithinStorage($fileIdentifier, $targetFolderIdentifier, $fileName)
    {
        throw new NotImplementedException(sprintf('Method %s::%s() is not implemented', __CLASS__, __METHOD__), 1519045388);
    }

    /**
     * Renames a file in this storage.
     *
     * @param string $fileIdentifier
     * @param string $newName The target path (including the file name!)
     * @return string The identifier of the file after renaming
     * @throws NotImplementedException
     */
    public function renameFile($fileIdentifier, $newName)
    {
        throw new NotImplementedException(sprintf('Method %s::%s() is not implemented', __CLASS__, __METHOD__), 1519045389);
    }

    /**
     * Replaces a file with file in local file system.
     *
     * @param string $fileIdentifier
     * @param string $localFilePath
     * @return bool TRUE if the operation succeeded
     * @throws NotImplementedException
     */
    public function replaceFile($fileIdentifier, $localFilePath)
    {
        throw new NotImplementedException(sprintf('Method %s::%s() is not implemented', __CLASS__, __METHOD__), 1519045390);
    }

    /**
     * Removes a file from the filesystem. This does not check if the file is
     * still used or if it is a bad idea to delete it for some other reason
     * this has to be taken care of in the upper layers (e.g. the Storage)!
     *
     * @param string $fileIdentifier
     * @return bool TRUE if deleting the file succeeded
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
     * Moves a file *within* the current storage.
     * Note that this is only about an inner-storage move action,
     * where a file is just moved to another folder in the same storage.
     *
     * @param string $fileIdentifier
     * @param string $targetFolderIdentifier
     * @param string $newFileName
     * @return string
     * @throws NotImplementedException
     */
    public function moveFileWithinStorage($fileIdentifier, $targetFolderIdentifier, $newFileName)
    {
        throw new NotImplementedException(sprintf('Method %s::%s() is not implemented', __CLASS__, __METHOD__), 1519045392);
    }

    /**
     * Folder equivalent to moveFileWithinStorage().
     *
     * @param string $sourceFolderIdentifier
     * @param string $targetFolderIdentifier
     * @param string $newFolderName
     * @return array All files which are affected, map of old => new file identifiers
     * @throws NotImplementedException
     */
    public function moveFolderWithinStorage($sourceFolderIdentifier, $targetFolderIdentifier, $newFolderName)
    {
        throw new NotImplementedException(sprintf('Method %s::%s() is not implemented', __CLASS__, __METHOD__), 1519045393);
    }

    /**
     * Folder equivalent to copyFileWithinStorage().
     *
     * @param string $sourceFolderIdentifier
     * @param string $targetFolderIdentifier
     * @param string $newFolderName
     * @return void
     * @throws NotImplementedException
     */
    public function copyFolderWithinStorage($sourceFolderIdentifier, $targetFolderIdentifier, $newFolderName)
    {
        throw new NotImplementedException(sprintf('Method %s::%s() is not implemented', __CLASS__, __METHOD__), 1519045394);
    }

    /**
     * Returns the contents of a file. Beware that this requires to load the
     * complete file into memory and also may require fetching the file from an
     * external location. So this might be an expensive operation (both in terms
     * of processing resources and money) for large files.
     *
     * @param string $fileIdentifier
     * @return string The file contents
     * @throws NotImplementedException
     */
    public function getFileContents($fileIdentifier)
    {
        throw new NotImplementedException(sprintf('Method %s::%s() is not implemented', __CLASS__, __METHOD__), 1530716278);
    }

    /**
     * Sets the contents of a file to the specified value.
     *
     * @param string $fileIdentifier
     * @param string $contents
     * @return int The number of bytes written to the file
     * @throws NotImplementedException
     */
    public function setFileContents($fileIdentifier, $contents)
    {
        throw new NotImplementedException(sprintf('Method %s::%s() is not implemented', __CLASS__, __METHOD__), 1519045395);
    }

    /**
     * Checks if a file inside a folder exists
     *
     * @param string $fileName
     * @param string $folderIdentifier
     * @return bool
     */
    public function fileExistsInFolder($fileName, $folderIdentifier)
    {
        return !empty($fileName) && ($this->rootFolder === $folderIdentifier);
    }

    /**
     * Checks if a folder inside a folder exists.
     *
     * @param string $folderName
     * @param string $folderIdentifier
     * @return bool
     */
    public function folderExistsInFolder($folderName, $folderIdentifier)
    {
        // Currently we don't know the concept of folders within Bynder and for now always return false
        return false;
    }

    /**
     * Returns a path to a local copy of a file for processing it. When changing the
     * file, you have to take care of replacing the current version yourself!
     *
     * @param string $fileIdentifier
     * @param bool $writable Set this to FALSE if you only need the file for read
     *                       operations. This might speed up things, e.g. by using
     *                       a cached local version. Never modify the file if you
     *                       have set this flag!
     * @return string The path to the file on the local disk
     * @throws NotImplementedException
     */
    public function getFileForLocalProcessing($fileIdentifier, $writable = true)
    {
        throw new NotImplementedException(sprintf('Method %s::%s() is not implemented', __CLASS__, __METHOD__), 1530778712);
    }

    /**
     * Returns the permissions of a file/folder as an array
     * (keys r, w) of boolean flags
     *
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
     * @throws NotImplementedException
     */
    public function dumpFileContents($identifier)
    {
        throw new NotImplementedException(sprintf('Method %s::%s() is not implemented', __CLASS__, __METHOD__), 1530716441);
    }

    /**
     * Checks if a given identifier is within a container, e.g. if
     * a file or folder is within another folder.
     * This can e.g. be used to check for web-mounts.
     *
     * Hint: this also needs to return TRUE if the given identifier
     * matches the container identifier to allow access to the root
     * folder of a filemount.
     *
     * @param string $folderIdentifier
     * @param string $identifier identifier to be checked against $folderIdentifier
     * @return bool TRUE if $content is within or matches $folderIdentifier
     */
    public function isWithin($folderIdentifier, $identifier)
    {
        return ($folderIdentifier === $this->rootFolder);
    }

    /**
     * Returns information about a file.
     *
     * @param string $fileIdentifier
     * @param array $propertiesToExtract Array of properties which are be extracted
     *                                   If empty all will be extracted
     * @return array
     */
    public function getFileInfoByIdentifier($fileIdentifier, array $propertiesToExtract = [])
    {
        if (preg_match('/^processed_([0-9A-Z\-]{35})_([a-z]+)/', $fileIdentifier, $matches)) {
            $fileIdentifier = $matches[1];
        }

        $bynderHelper = GeneralUtility::makeInstance(BynderHelper::class, 'bynder');
        $fileInfo = $bynderHelper->extractMetaData($fileIdentifier, $propertiesToExtract);
        return $fileInfo;
    }

    /**
     * Returns information about a folder.
     *
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
     * Returns the identifier of a file inside the folder
     *
     * @param string $fileName
     * @param string $folderIdentifier
     * @return string file identifier
     */
    public function getFileInFolder($fileName, $folderIdentifier)
    {
        return '';
    }

    /**
     * Returns a list of files inside the specified path
     *
     * @param string $folderIdentifier
     * @param int $start
     * @param int $numberOfItems
     * @param bool $recursive
     * @param array $filenameFilterCallbacks callbacks for filtering the items
     * @param string $sort Property name used to sort the items.
     *                     Among them may be: '' (empty, no sorting), name,
     *                     fileext, size, tstamp and rw.
     *                     If a driver does not support the given property, it
     *                     should fall back to "name".
     * @param bool $sortRev TRUE to indicate reverse sorting (last to first)
     * @return array of FileIdentifiers
     */
    public function getFilesInFolder($folderIdentifier, $start = 0, $numberOfItems = 0, $recursive = false, array $filenameFilterCallbacks = [], $sort = '', $sortRev = false)
    {
        return [];
    }

    /**
     * Returns the identifier of a folder inside the folder
     *
     * @param string $folderName The name of the target folder
     * @param string $folderIdentifier
     * @return string folder identifier
     */
    public function getFolderInFolder($folderName, $folderIdentifier)
    {
        return '';
    }

    /**
     * Returns a list of folders inside the specified path
     *
     * @param string $folderIdentifier
     * @param int $start
     * @param int $numberOfItems
     * @param bool $recursive
     * @param array $folderNameFilterCallbacks callbacks for filtering the items
     * @param string $sort Property name used to sort the items.
     *                     Among them may be: '' (empty, no sorting), name,
     *                     fileext, size, tstamp and rw.
     *                     If a driver does not support the given property, it
     *                     should fall back to "name".
     * @param bool $sortRev TRUE to indicate reverse sorting (last to first)
     * @return array of Folder Identifier
     */
    public function getFoldersInFolder($folderIdentifier, $start = 0, $numberOfItems = 0, $recursive = false, array $folderNameFilterCallbacks = [], $sort = '', $sortRev = false)
    {
        return [];
    }


    /**
     * Returns the number of files inside the specified path
     *
     * @param string  $folderIdentifier
     * @param bool $recursive
     * @param array   $filenameFilterCallbacks callbacks for filtering the items
     * @return int Number of files in folder
     */
    public function countFilesInFolder($folderIdentifier, $recursive = false, array $filenameFilterCallbacks = [])
    {
        return 0;
    }

    /**
     * Returns the number of folders inside the specified path
     *
     * @param string  $folderIdentifier
     * @param bool $recursive
     * @param array   $folderNameFilterCallbacks callbacks for filtering the items
     * @return int Number of folders in folder
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
}
