<?php

namespace Epoint\SwisspostApi\Helper;

use Epoint\SwisspostApi\Model\Api\Address;
use Epoint\SwisspostApi\Model\Api\SaleOrder;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\ObjectManagerInterface;
use Epoint\SwisspostApi\Model\Api\Account;
use Epoint\SwisspostApi\Helper\Api\Resource As SwisspostResource;
use Epoint\SwisspostApi\Helper\Api\Curl\Client As SwisspostClient;

class Resource extends Data implements SwisspostResource
{
    /**
     * @var \Epoint\SwisspostApi\Helper\Api\Curl\Client
     */
    protected $client;

    /**
     * Resource constructor.
     *
     * @param Context                $context
     * @param ObjectManagerInterface $objectManager
     * @param StoreManagerInterface  $storeManager
     */
    public function __construct(
        Context $context,
        ObjectManagerInterface $objectManager,
        StoreManagerInterface $storeManager
    ) {
        parent::__construct($context, $objectManager, $storeManager);
    }

    /**
     * Instantiate client
     */
    private function instantiateClient()
    {
        if ($this->client !== null) {
            return;
        }

        $tmpDir = sys_get_temp_dir();
        $fileSystem = $this->objectManager->create('\Magento\Framework\Filesystem');
        if ($fileSystem) {
            $tmpDir = $fileSystem->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::TMP)->getAbsolutePath();
        }

        if (!$this->getConnectionConfig('base_location')) {
            throw new \Exception(__('Missing API connection url, please configure it.'));
        }
        if (!$this->getConnectionConfig('shop_ident')) {
            throw new \Exception(__('Missing API shop_ident, please configure it.'));
        }
        if (!$this->getConnectionConfig('base_location')) {
            throw new \Exception(__('Missing API base_location, please configure it.'));
        }
        if (!$this->getConnectionConfig('db')) {
            throw new \Exception(__('Missing API database, please configure it.'));
        }
        if (!$this->getConnectionConfig('login')) {
            throw new \Exception(__('Missing API login, please configure it.'));
        }
        if (!$this->getConnectionConfig('password')) {
            throw new \Exception(__('Missing API password, please configure it.'));
        }
        // Add config.
        $config = [
            'jsonrpc'       => $this->getConnectionConfig('jsonrpc'),
            'shop_ident'    => $this->getConnectionConfig('shop_ident'),
            'base_location' => $this->getConnectionConfig('base_location'),
            'db'            => $this->getConnectionConfig('db'),
            'login'         => $this->getConnectionConfig('login'),
            'password'      => $this->getConnectionConfig('password'),
            'tmp_dir'       => $tmpDir,
            'logger'        => $this->_logger,
            'timeout'       => MAX($this->getConnectionConfig('timeout'), 15),
        ];
        /* @var \Epoint\SwisspostApi\Helper\Api\Client $client */
        $this->client = new SwisspostClient($config);
    }

    /**
     * @inheritdoc
     */
    public function sessionGetInfo()
    {
        $this->instantiateClient();
        return $this->client->call('web/session/get_session_info');
    }

    /**
     * @inheritdoc
     */
    public function sessionAuthenticate()
    {
        $this->instantiateClient();
        $this->client->connect();
        return $this->client->getLastResult();
    }

    /**
     * @param $methdod
     * @param $data
     *
     * @return Api\Curl\Result
     */
    protected function ApiCall($methdod, $data)
    {
        $this->instantiateClient();
        $result = $this->client->call($methdod, $data);
        if (!$result->isOK()) {
            $this->_logger->error(
                sprintf(
                    __('Swisspost API error on method: %s, response: %s')
                    ,
                    $methdod,
                    $result->getDebugMessage()
                )
            );
        }
        return $result;
    }

    /**
     * @inheritdoc
     */
    public function createUpdateAccount(Account $account)
    {
        return $this->ApiCall(
            'ecommerce_api_v2/create_update_account',
            ['account' => $account->getData()]
        );
    }

    /**
     * @inheritdoc
     */
    public function createSalesOrder(SaleOrder $order)
    {
        return $this->ApiCall(
            'ecommerce_api_v2/create_sale_order',
            ['sale_order' => $order->getData()]
        );
    }

    /**
     * @inheritdoc
     */
    public function createUpdateAddress(Address $address)
    {
        return $this->ApiCall(
            'ecommerce_api_v2/create_update_address',
            ['address' => $address->getData()]
        );
    }

    /**
     * @inheritdoc
     */
    public function searchReadAccount($filter)
    {
        if (!isset($filter['filters'])) {
            $filter['filters'] = [];
        }

        if (!isset($filter['fields'])) {
            $filter['fields'] = [];
        }

        return $this->ApiCall(
            'ecommerce_api_v2/search_read_account',
            $filter
        );
    }

    /**
     * @inheritdoc
     */
    public function searchReadAddress($filter)
    {
        if (!isset($filter['filters'])) {
            $filter['filters'] = [];
        }

        if (!isset($filter['fields'])) {
            $filter['fields'] = [];
        }
        return $this->ApiCall(
            'ecommerce_api_v2/search_read_address',
            $filter
        );
    }

    /**
     * @inheritdoc
     */
    public function getProducts($filter)
    {
        if (!isset($filter['filters'])) {
            $filter['filters'] = [];
        }

        if (!isset($filter['fields'])) {
            $filter['fields'] = [];
        }
        return $this->ApiCall(
            'ecommerce_api_v2/get_products',
            $filter
        );
    }

    /**
     * @inheritdoc
     */
    public function getProductCategories($filter)
    {
        if (!isset($filter['filters'])) {
            $filter['filters'] = [];
        }

        if (!isset($filter['fields'])) {
            $filter['fields'] = [];
        }
        return $this->ApiCall(
            'ecommerce_api_v2/get_product_categories',
            $filter
        );
    }

    /**
     * @inheritdoc
     */
    public function checkCustomerCredit($filter)
    {
        if (!isset($filter['account_ref'])) {
            $filter['account_ref'] = '';
        }
        if (!isset($filter['amount'])) {
            $filter['amount'] = 0;
        }
        return $this->ApiCall(
            'ecommerce_api_v2/check_customer_credit',
            $filter
        );
    }

    /**
     * @inheritdoc
     */
    public function getInventory($filter)
    {
        return $this->ApiCall(
            'ecommerce_api_v2/get_inventory',
            $filter
        );
    }

    /**
     * @inheritdoc
     */
    public function getImages($productRef)
    {
        return $this->ApiCall(
            'ecommerce_api_v2/get_images',
            ['product_ref' => $productRef]
        );
    }

    /**
     * @inheritdoc
     */
    public function getInvoice($orderRef)
    {
        return $this->ApiCall(
            'ecommerce_api_v2/get_invoice_docs',
            ['order_ref' => $orderRef]
        );
    }

    /**
     * @inheritdoc
     */
    public function checkOrdersPaymentStatus($orderIdsList = [], $invoiceIdsList = [])
    {
        return $this->ApiCall(
            'ecommerce_api_v2/get_payment_status',
            [
                'order_refs'   => $orderIdsList,
                'invoice_refs' => $invoiceIdsList
            ]
        );
    }

    /**
     * @inheritdoc
     */
    public function checkOrdersTransferStatus($orderIdsList = [])
    {
        return $this->ApiCall(
            'ecommerce_api_v2/get_transfer_status',
            ['order_refs' => $orderIdsList]
        );
    }

    /**
     * @inheritdoc
     */
    public function addSaleOrderCoupon($orderRef, $couponsData)
    {
        return $this->ApiCall(
            'ecommerce_api_v2/add_sale_order_gift_cards',
            [
                'order_ref'  => $orderRef,
                'gift_cards' => $couponsData
            ]
        );
    }

    /**
     * @inheritdoc
     */
    public function getDeliveryReports($orderRef)
    {
        return $this->ApiCall(
            'ecommerce_api_v2/get_delivery_docs',
            ['order_ref' => $orderRef]
        );
    }
}
