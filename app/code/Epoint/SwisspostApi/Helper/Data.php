<?php

namespace Epoint\SwisspostApi\Helper;


use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\App\Helper\Context;

class Data extends AbstractHelper
{
    /**
     * Configuration access.
     */
    use ConfigurableTrait;
    /**
     * All the website stores.
     * @var array
     */
    static public $stores = [];

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
    const XML_PATH = 'swisspostapi/';


    /**
     * Config xml base path for connection.
     * @const XML_PATH_CONNECTION
     */
    const XML_PATH_CONNECTION = 'swisspostapi/connection/';

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

    /**
     * @param $code
     * @param null $storeId
     *
     * @return mixed
     */
    public function getConnectionConfig($code, $storeId = null)
    {
        return $this->getConfigValue(self::XML_PATH_CONNECTION . $code, $storeId);
    }

    /**
     * Get stores.
     *
     * @return array
     */
    public function getStores()
    {
        if (!self::$stores) {
            self::$stores = $this->storeManager->getStores($withDefault = false);
        }
        return self::$stores;
    }

    /**
     * Get store language code ISO, 2 letter, lowercase.
     *
     * @return string
     */
    public function getStoresLanguageCode($storeId)
    {
        list(, $locale) = explode('_', strtolower($this->getConfigValue('general/locale/code',
                $storeId
            )
            )
        );
        return $locale;
    }
}
