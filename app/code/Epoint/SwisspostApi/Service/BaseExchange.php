<?php

namespace Epoint\SwisspostApi\Service;

use \Magento\Framework\ObjectManagerInterface;
use Psr\Log\LoggerInterface;
use Epoint\SwisspostApi\Helper\LoggerTrait;
use Magento\Framework\App\Config\ScopeConfigInterface;

abstract class BaseExchange
{
    /**
     * Logger
     */
    use LoggerTrait;

    /**
     * Object manager.
     * @var \Magento\Framework\App\ObjectManager::getInstance()
     */
    protected $objectManager;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * BaseExchange constructor.
     *
     * @param ObjectManagerInterface $objectManager
     * @param LoggerInterface        $logger
     * @param ScopeConfigInterface   $scopeConfig
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        LoggerInterface $logger,
        ScopeConfigInterface $scopeConfig
    )
    {
        $this->objectManager = $objectManager;
        $this->logger = $logger;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * The importer factory.
     *
     * @return $runner Epoint\SwisspostApi\Service\BaseRunner
     */
    abstract public function run($items);

    /**
     * The list factory.
     *
     * @return $list
     */
    abstract public function listFactory();

    /**
     * The helper.
     *
     * @return $list
     */
    abstract public function helperFactory();

    /**
     * Check if is enabled.
     * @return mixed
     */
    public function isEnabled()
    {
        $helper = $this->helperFactory();
        if($helper->isCronEnabled()){
            return true;
        }
        return false;
    }

    /**
     * Execute export.
     */
    public function execute()
    {
        /** @var \Epoint\SwisspostCatalog\Helper\Data $helper */
        if($this->isEnabled()) {
            /** @var \Epoint\SwisspostApi\Model\Api\Lists $itemList */
            $listFactory = $this->listFactory();
            /** @var list Epoint\SwisspostApi\Model\Api\ApiDataObject $items */
            $items = $listFactory->search();
            // Run import.
            $this->run($items);
        }
    }
}
