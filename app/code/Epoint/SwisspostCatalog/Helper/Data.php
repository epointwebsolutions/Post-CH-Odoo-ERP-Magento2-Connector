<?php

namespace Epoint\SwisspostCatalog\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\App\Helper\Context;
use Epoint\SwisspostApi\Helper\ConfigurableTrait;

class Data extends AbstractHelper
{
    /**
     * Configuration access.
     */
    use ConfigurableTrait;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * Config xml base path.
     * @const XML_PATH
     */
    const XML_PATH = 'swisspostcatalog/';


    /**
     * Data constructor.
     *
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        Context $context,
        ObjectManagerInterface $objectManager,
        StoreManagerInterface $storeManager
    ) {

        $this->objectManager = $objectManager;
        $this->storeManager = $storeManager;
        parent::__construct($context);
    }

}
