<?php

namespace Epoint\SwisspostCatalog\Model;


use Epoint\SwisspostApi\Model\Api\Image as ApiImage;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Io\File As IoFile;

class MediaFactory
{

    /**
     * Media file
     */
    const IMPORT_TMP_DIR_PATH = 'tmp/image-odoo-import/';

    /**
     * Object manager.
     * @var \Magento\Framework\App\ObjectManager::getInstance() $objectManager
     */
    protected $objectManager;

    /**
     * @var \Magento\Framework\Filesystem
     */
    protected $filesystem;

    /**
     * @var \Magento\Framework\Filesystem\DirectoryList $directoryList
     */
    protected $directoryList;

    /**
     * @var \Magento\Framework\Filesystem\Io\File $io
     **/
    protected $io;

    /**
     * MediaFactory constructor.
     *
     * @param ObjectManagerInterface $objectManager
     * @param Filesystem             $filesystem
     * @param DirectoryList          $directoryList
     * @param IoFile                 $io
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        Filesystem $filesystem,
        DirectoryList $directoryList,
        IoFile $io
    ) {
        $this->objectManager = $objectManager;
        $this->filesystem = $filesystem;
        $this->directoryList = $directoryList;
        $this->io = $io;
        // Create
        if (!is_dir($this->directoryList->getPath(DirectoryList::MEDIA) . DIRECTORY_SEPARATOR . self::IMPORT_TMP_DIR_PATH)) {
            $this->io->mkdir($this->directoryList->getPath(DirectoryList::MEDIA) . DIRECTORY_SEPARATOR . self::IMPORT_TMP_DIR_PATH,
                0775);
        }
    }

    /**
     * Create a file that can be attached to product.
     * @return file
     */
    public function create(ApiImage $apiImage)
    {
        if (!$fileName = $apiImage->getName()) {
            throw new \Exception(__('Missing media name.'));
        }

        if (!$fileContent = $apiImage->getContent()) {
            throw new \Exception(__('Missing media name content.'));
        }
        $writer = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $localPath = $this->directoryList->getPath(DirectoryList::MEDIA). DIRECTORY_SEPARATOR . self::IMPORT_TMP_DIR_PATH  . $fileName;
        $file = $writer->openFile( self::IMPORT_TMP_DIR_PATH  . $fileName, 'w');
        // @todo a better implementation with properly logging.
        try {
            $file->lock();
            try {
                $file->write($fileContent);
            } catch (\Exception $e) {
                //@todo proper logging.
                print $e->getMessage();
            } finally {
                $file->unlock();

            }
        } catch (\Exception $e) {
            //@todo proper logging.
            print $e->getMessage();
        } finally {
            $file->close();
        }
        if ($file) {
            $media = $this->objectManager->create(
                \Epoint\SwisspostCatalog\Model\Media::class
            );
            // Set local path.
            $apiImage->setLocalPath($localPath);
            $media->setApiImage($apiImage);
            return $media;
        }
        return null;
    }
}
