<?php

namespace Epoint\SwisspostApi\Model\Api;

use Epoint\SwisspostApi\Helper\Resource;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Event\Manager;
use Epoint\SwisspostApi\Helper\LoggerTrait;

abstract class ApiDataObject
{
    /**
     * Logger trait
     */
    use LoggerTrait;

    /**
     * Object manager.
     *
     * @var \Magento\Framework\App\ObjectManager::getInstance()
     */
    protected $objectManager;

    /**
     * The variable data.
     *
     * @var array
     */
    protected $_data = [];

    /**
     * The Api Resource
     *
     * @var \Epoint\SwisspostApi\Helper\Resource
     */
    protected $apiResource;

    /**
     * Event manager
     *
     * @var Manager
     */
    protected $eventManager;

    /**
     * ApiDataObject constructor.
     *
     * @param ObjectManagerInterface   $objectManager
     * @param Resource                 $resource
     * @param Manager                  $eventManager
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(ObjectManagerInterface $objectManager,
        Resource $resource, Manager $eventManager, \Psr\Log\LoggerInterface $logger
    ) {
        $this->objectManager = $objectManager;
        $this->apiResource = $resource;
        $this->eventManager = $eventManager;
        $this->logger = $logger;
    }

    /**
     * Convert local object to magento object.
     *
     * @param $object
     *   The magento base object
     *
     * @return \Magento\Framework\DataObject $object
     *
     */
    abstract public function getInstance($object);

    /**
     * Will return the reference id of the model
     * @return mixed
     */
    abstract public function getReferenceId($objectId = '');

    /**
     * Set Api Resource object.
     */
    public function setApiResource(Resource $apiResource)
    {
        $this->apiResource = $apiResource;
    }

    /**
     * Basic set variable
     *
     * @param string $variable
     *   The variable name
     * @param mixed  $value
     *   The variable value.
     */
    public function set($variable, $value)
    {
        $this->_data[$variable] = $value;
    }

    /**
     * Reset data.
     */
    protected function reset()
    {
        $this->_data = [];
    }

    /**
     * @param string $variable
     *   The variable name
     * @param        $value
     *
     * @return mixed
     */
    public function get($variable)
    {
        return isset($this->_data[$variable]) ? $this->_data[$variable] : null;
    }

    /**
     * Data getter.
     *
     * @return mixed
     */
    public function getData()
    {
        return $this->_data;
    }

    /**
     * Load api result item into load object.
     */
    public function loadFromResultItem($item)
    {
        if ($item) {
            foreach ($item as $key => $value) {
                $this->set($key, $value);
            }
        }
        return $this;
    }

}
