<?php

namespace Epoint\SwisspostApi\Model\Api;

use Epoint\SwisspostApi\Helper\Result;

class Image extends ApiDataObject
{
    /**
     * Image type URL.
     * @const IMAGE_TYPE_URL
     */
    const IMAGE_TYPE_URL = 'url';

    /**
     * Image type binary, base64encoded.
     * @const IMAGE_TYPE_BASE64ENCODED
     */
    const IMAGE_TYPE_BASE64ENCODED = 'binary';

    /**
     * @inheritdoc
     */
    public function getReferenceId($objectId = '')
    {
        if (!empty($objectId)) {
            return $objectId;
        }
        return $this->get('id');
    }

    /**
     * @inheritdoc
     */
    public function getInstance($image)
    {
        $apiObject = $this->objectManager->get(
            \Epoint\SwisspostApi\Model\Api\Image::class
        );
        $apiObject->set('id', $this->getReferenceId($image->getId()));
        return $apiObject;
    }

    /**
     * The image name
     * @return string
     */
    public function getName()
    {
        if($this->get('type') == self::IMAGE_TYPE_URL){
            return basename($this->get('url'));
        }
        if($this->get('type') == self::IMAGE_TYPE_BASE64ENCODED){
            return $this->get('filename');
        }
        return '';
    }

    /**
     * Return image content.
     * @return bool|string
     */
    public function getContent()
    {
        if($this->get('type') == self::IMAGE_TYPE_URL){
            return @file_get_contents($this->get('url'));
        }
        if($this->get('type') == self::IMAGE_TYPE_BASE64ENCODED){
            return base64_decode($this->get('content'));
        }
        return '';
    }

    /**
     * Return image content.
     * @return bool|string
     */
    public function getLocalPath()
    {
        return $this->get('local_path');
    }

    /**
     * Return image content.
     * @return bool|string
     */
    public function setLocalPath($localPath)
    {
        return $this->set('local_path', $localPath);
    }

    /**
     * Will validate the image data
     * @return bool
     */
    public function validateImageData()
    {
        $isValidated = false;
        if(!empty($this->getContent()) && !empty($this->getName())){
            $isValidated = true;
        }
        return $isValidated;
    }
}
