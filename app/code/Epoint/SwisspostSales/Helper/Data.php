<?php

namespace Epoint\SwisspostSales\Helper;


use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\App\Helper\Context;
use Epoint\SwisspostApi\Helper\ConfigurableTrait;
use Epoint\SwisspostApi\Helper\LoggerTrait;
use Psr\Log\LoggerInterface;

class Data extends AbstractHelper
{
    /**
     * Logger
     */
    use LoggerTrait;

    /**
     * Configuration access.
     */
    use ConfigurableTrait;

    /**
     * All the website stores.
     *
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
     *
     * @const XML_PATH
     */
    const XML_PATH = 'swisspostsales/';

    /**
     * For this type we must generate a new pdf file from content with filename
     *
     * @const DOCUMENT_TYPE_BINARY
     */
    const DOCUMENT_TYPE_BINARY = 'binary';

    /**
     * For this type the pdf file will be downloaded from an url
     *
     * @const DOCUMENT_TYPE_URL
     */
    const DOCUMENT_TYPE_URL = 'url';

    /**
     * Data constructor.
     *
     * @param \Magento\Framework\App\Helper\Context      $context
     * @param \Magento\Framework\ObjectManagerInterface  $objectManager
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        Context $context,
        ObjectManagerInterface $objectManager,
        StoreManagerInterface $storeManager,
        LoggerInterface $logger
    ) {

        $this->objectManager = $objectManager;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
        parent::__construct($context);
    }

    /**
     * Will get the default status for provided state
     *
     * @param $state
     *
     * @return string
     */
    public function getDefaultStatusForState($state)
    {
        /** @var \Magento\Sales\Model\Order\Status $status */
        $status = $this->objectManager->get(\Magento\Sales\Model\Order\Status::class)->loadDefaultByState($state);
        return strtolower($status->getLabel());
    }

    /**
     * @param $content
     *
     * @return null|\Zend_Pdf
     */
    public function preparePdfFileWithContent($content)
    {
        $pdf = null;
        if ($content) {
            $pdf = \Zend_Pdf::parse($content);
        }
        return $pdf;
    }
}
