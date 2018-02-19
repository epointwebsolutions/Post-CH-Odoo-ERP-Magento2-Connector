<?php

namespace Epoint\SwisspostCatalog\Model;

use Epoint\SwisspostApi\Model\Api\Image as ApiImage;
use Magento\Framework\Filesystem\Driver\File;

class Media
{
    /** @var  \Magento\Framework\Filesystem\Driver\File */

    private $file;

    /** @var  Epoint\SwisspostApi\Model\Api\Image */
    private $apiImage;

    /**
     * Media constructor.
     *
     * @param File $file
     */
    public function __construct(File $file)
    {
        $this->file = $file;
    }

    /**
     * @param Epoint\SwisspostApi\Model\Api\Image $apiImage
     */
    public function setApiImage(ApiImage $apiImage)
    {
        $this->apiImage = $apiImage;
    }

    /**
     * Delete file.
     */
    public function delete()
    {
        if ($this->getLocalPath()) {
            if ($this->file->isExists($this->getLocalPath())) {
                $this->file->deleteFile($this->apiImage->getLocalPath());
            }
        }
    }

    /**
     * Get file name
     *
     * @return mixed
     */
    public function getName()
    {
        if ($this->apiImage) {
            return $this->apiImage->getName();
        }
    }

    /**
     * Get Local Path
     *
     * @return mixed
     */
    public function getLocalPath()
    {
        if ($this->apiImage) {
            return $this->apiImage->getLocalPath();
        }
    }

    /**
     * Remove file if the object is discarded.
     */
    public function __destruct()
    {
        $this->delete();
    }
}
