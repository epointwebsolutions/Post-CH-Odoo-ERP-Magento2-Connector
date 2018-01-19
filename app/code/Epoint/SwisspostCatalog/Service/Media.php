<?php

namespace Epoint\SwisspostCatalog\Service;

use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Io\File As IoFile;
use Epoint\SwisspostCatalog\Model\MediaFactory;
use Epoint\SwisspostApi\Model\Api\Product as ApiProduct;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product as ModelProduct;
use Magento\Catalog\Api\Data\ProductInterface;

class Media
{
    /**
     * Media file
     */
    const IMPORT_TMP_DIR_PATH = 'tmp/image-odoo-import/';

    const CATALOG_PRODUCT_PATH = '/catalog/product';

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
     * @var \Epoint\SwisspostCatalog\Model\MediaFactory $mediaFactory
     */
    protected $mediaFactory;

    /**
     * @var \Magento\Framework\Filesystem\Io\File $io
     **/
    protected $io;

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * Media constructor.
     *
     * @param ObjectManagerInterface     $objectManager
     * @param Filesystem                 $filesystem
     * @param DirectoryList              $directoryList
     * @param IoFile                     $io
     * @param MediaFactory               $mediaFactory
     * @param ProductRepositoryInterface $productRepository
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        Filesystem $filesystem,
        DirectoryList $directoryList,
        IoFile $io,
        MediaFactory $mediaFactory,
        ProductRepositoryInterface $productRepository
    ) {
        $this->objectManager = $objectManager;
        $this->filesystem = $filesystem;
        $this->directoryList = $directoryList;
        $this->io = $io;
        $this->mediaFactory = $mediaFactory;
        $this->productRepository = $productRepository;
        // Create
        if (!is_dir($this->directoryList->getPath(DirectoryList::MEDIA) . DIRECTORY_SEPARATOR . self::IMPORT_TMP_DIR_PATH)) {
            $this->io->mkdir($this->directoryList->getPath(DirectoryList::MEDIA) . DIRECTORY_SEPARATOR . self::IMPORT_TMP_DIR_PATH,
                0775);
        }
    }

    /**
     * Will check if the product has any images attached
     * @param ModelProduct $product
     *
     * @return bool
     */
    public function isProductImageGalleryEmpty(ModelProduct $product)
    {
        // Getting the list of the product images
        $productImages = $product->getMediaGalleryImages();
        if (count($productImages) > 0){
            return false;
        }
        return true;
    }

    /**
     * Removing all the existing product images
     * @param ModelProduct $product
     */
    public function removeProductMediaGallery(ModelProduct $product)
    {
        // Getting the list of the current product images
        $existingMediaGalleryEntries = $product->getMediaGalleryEntries();
        if (is_array($existingMediaGalleryEntries) && count($existingMediaGalleryEntries) > 0) {
            // Storing the paths of existing product images before we unset them
            $productImagePaths = [];
            foreach ($existingMediaGalleryEntries as $image){
                $productImagePaths[] = $image->getFile();
            }
            // Unlink product images
            $product->setMediaGalleryEntries([]);
            $this->productRepository->save($product);
            // Deleting all the images previous assign to the product
            foreach ($productImagePaths as $path){
                unlink($this->directoryList->getPath(DirectoryList::MEDIA) . self::CATALOG_PRODUCT_PATH . $path);
            }
        }
    }

    /**
     * Will setup the first image from Media Gallery as default image
     * @param \Magento\Catalog\Api\Data\ProductInterface $product
     */
    public function setProductDefaultImage(
        ProductInterface $product
    ){
        // Getting the product image gallery
        $productImages = $product->getMediaGalleryImages();
        if (count($productImages) > 0) {
            // Setting the first image as default image
            foreach ($productImages as $key => $value) {
                $product->setThumbnail($value['file']);
                $product->setSmallImage($value['file']);
                $product->setImage($value['file']);
                $product->save();
                break;
            }
        }
    }

    /**
     * @param ApiProduct   $apiProduct
     * @param ModelProduct $product
     */
    public function execute(ApiProduct $apiProduct, ModelProduct $product)
    {
        $images = [];
        // Request the product images from Odoo
        /** @var \Epoint\SwisspostApi\Model\Api\Image $image */
        foreach ($apiProduct->getImages() as $image){
            $media = $this->mediaFactory->create($image);
            if($media) {
                $images[] = $media;
            }
        }

        if($images && !empty($product->getId())){
            //Remove existing images.
            $this->removeProductMediaGallery($product);

            // Adding the images to the product media gallery
            foreach ($images as $image){
                $product->addImageToMediaGallery($image->getLocalPath(), null, true, false);
            }
            $product->save();
            // Setup default image
            $this->setProductDefaultImage($product);
        }
        $images = NULL;
    }
}
