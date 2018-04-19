<?php

namespace Epoint\SwisspostApi\Model\Api;

use Epoint\SwisspostApi\Helper\Resource;
use Magento\Framework\Event\Manager;
use Magento\Framework\ObjectManagerInterface;
use \Psr\Log\LoggerInterface;
use Epoint\SwisspostApi\Model\Api\Address as ApiModelAddress;
use Epoint\SwisspostApi\Model\Api\Account as ApiModelAccount;
use Epoint\SwisspostApi\Model\Api\AnonymousAccount as ApiModelAnonymousAccount;

class SaleOrder extends ApiDataObject implements Data\Entity
{
    /**
     * Order external id key.
     * @const EXTERNAL_ID_CODE
     */
    const EXTERNAL_ID_CODE = 'odoo_id';

    /**
     * Entity type
     * @const ENTITY_TYPE
     */
    const ENTITY_TYPE = 'order';

    /**
     * @const ENTITY_AUTOMATIC_EXPORT
     */
    const ENTITY_AUTOMATIC_EXPORT = 'automatic_export';

    /**
     * @const ENTITY_EXPORT_TRYOUTS
     */
    const ENTITY_EXPORT_TRYOUTS = 'export_tryouts';

    /**
     * Entity.
     * @var $_entity
     */
    protected $_entity;

    /**
     * @var \Epoint\SwisspostApi\Model\Api\Address
     */
    protected $apiModelAddress;

    /**
     * @var \Epoint\SwisspostApi\Model\Api\Account
     */
    protected $apiModelAccount;

    /**
     * @var \Epoint\SwisspostApi\Model\Api\AnonymousAccount
     */
    protected $apiModelAnonymousAccount;

    /**
     * SaleOrder constructor.
     *
     * @param ObjectManagerInterface $objectManager
     * @param Resource               $resource
     * @param Manager                $eventManager
     * @param LoggerInterface        $logger
     * @param Address                $apiModelAddress
     * @param Account                $apiModelAccount
     * @param AnonymousAccount       $apiModelAnonymousAccount
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        Resource $resource,
        Manager $eventManager,
        LoggerInterface $logger,
        ApiModelAddress $apiModelAddress,
        ApiModelAccount $apiModelAccount,
        ApiModelAnonymousAccount $apiModelAnonymousAccount
    ) {
        parent::__construct($objectManager, $resource, $eventManager, $logger);
        $this->apiModelAddress = $apiModelAddress;
        $this->apiModelAccount = $apiModelAccount;
        $this->apiModelAnonymousAccount = $apiModelAnonymousAccount;
    }

    /**
     * @inheritdoc
     */
    public function getReferenceId($objectId = '')
    {
        if (!empty($objectId)) {
            return $objectId;
        }
        return $this->get('order_ref');
    }

    /**
     * @inheritdoc
     */
    public function getInstance($order)
    {
        $apiObject = $this->objectManager->create(
            \Epoint\SwisspostApi\Model\Api\SaleOrder::class
        );

        if (!$order->getIncrementId()) {
            throw new \Exception(__('Missing order id.'));
        }
        // Add Order Reference
        $apiObject->set('order_ref', $this->getReferenceId($order->getIncrementId()));
        // Add date of the order
        $apiObject->set('date_order', $order->getCreatedAt());
        // Add transation_id -> Indicates the payment transaction ID. It is mandatory for an electronic payment method
        try {
            if ($lastTransId = $order->getPayment()->getLastTransId()) {
                $apiObject->set('transaction_id',
                    $order->getPayment()->getLastTransId());
            }
        } catch (\Exception $e) {
            // @Supress exception.
            $this->logException($e);
        }
        /** @var \Epoint\SwisspostApi\Model\Api\Address $address */
        $address = null;

        // Checking if the customer is anonymous or not
        if (empty($order->getCustomerIsGuest()) && $order->getCustomerID()){
            if ($order->getBillingAddress()->getCustomerAddressId() !== null) {
                $localBillingAddress = $this->objectManager->create(
                    \Magento\Customer\Model\Address::class
                )->load($order->getBillingAddress()->getCustomerAddressId());
            } else {
                $localBillingAddress = $this->objectManager->create(
                    \Magento\Customer\Model\Address::class
                )->load($order->getShippingAddress()->getCustomerAddressId());
            }
            $address = $this->apiModelAddress->getInstance($localBillingAddress);
        } else {
            $localBillingAddress = $order->getBillingAddress();
            $address = $this->apiModelAddress->getInstanceForAnonymousCustomer($localBillingAddress);
        }
        $apiObject->set('address_invoice', $address->getData());

        // Shipping address
        if ($order->getShippingAddress()) {
            /** @var \Magento\Customer\Model\Address $localShippingAddress */
            $localShippingAddress = null;
            // Checking if the customer is anonymous or not
            if (empty($order->getCustomerIsGuest()) && $order->getCustomerID()){
                $localShippingAddress = $this->objectManager->create(
                    \Magento\Customer\Model\Address::class
                )->load($order->getShippingAddress()->getCustomerAddressId());
                $address = $this->apiModelAddress->getInstance($localShippingAddress);
            } else {
                $localShippingAddress = $order->getShippingAddress();
                $address = $this->apiModelAddress->getInstanceForAnonymousCustomer($localShippingAddress);
            }
            $apiObject->set('address_shipping', $address->getData());
        }

        if ($apiObject->get('address_invoice')
            && !$apiObject->get('address_shipping')
        ) {
            $apiObject->set('address_shipping',
                $apiObject->get('address_invoice'));
        }

        // Checking if the customer is anonymous or not
        if (empty($order->getCustomerIsGuest()) && $order->getCustomerID()){
            // Add an optional code the final customer uses to refer to the order
            $apiObject->set('client_order_ref', $this->apiModelAccount->getReferenceId($order->getCustomerId()));
            // Add the main account of the customer
            $customer
                = $this->objectManager->create(\Magento\Customer\Model\Customer::class)
                ->load($order->getCustomerID());
            $account = $this->apiModelAccount->getInstance($customer);

            $apiObject->set('account', $account->getData());
        } else {
            // Add the anonymous customer data
            $anonymousAccount = $this->apiModelAnonymousAccount->getInstance($order->getIncrementId());
            // Attach data to the account field
            $apiObject->set('account', $anonymousAccount->getData());
        }

        // Additional setup observer
        $this->eventManager->dispatch(
            'swisspostapi_order_before_api_create_sale_order',
            [
                'apiObject' => $apiObject,
                'order'     => $order
            ]
        );
        return $apiObject;
    }

    /**
     * @inheritdoc
     */
    public function delete()
    {
        $this->set('active', false);
        throw new LocalizedException(__('Not intended to be used.'));
        return $this->save();
    }

    /**
     * @inheritdoc
     */
    public function save()
    {
        // Checking to see if the order has been sent before (has been saved in db)
        /** @var \Epoint\SwisspostApi\Model\Entity _entity */
        $this->_entity = $this->objectManager->create(
            \Epoint\SwisspostApi\Model\Entity::class
        )->loadByTypeAndLocalId(self::ENTITY_TYPE,
            $this->getReferenceId());

        if (empty($this->_entity->getExternalId())) {
            return $this->apiResource->createSalesOrder($this);
        }
        return null;
    }

    /**
     * @return \Magento\Sales\Model\Order |mixed
     */
    public function toLocal()
    {
        $order = $this->objectManager->create(\Magento\Sales\Model\Order::class);
        if ($this->isLocalSaved()) {
            // Load internal id.
            $order->load($this->getLocalId());
        }
        return $order;
    }

    /**
     * @return bool
     */
    public function isLocalSaved()
    {
        if (empty($this->getExternalId())) {
            return false;
        }
        return true;
    }

    /**
     * @return mixed
     */
    public function getExternalId()
    {
        if (empty($this->get(self::EXTERNAL_ID_CODE))){
            /** @var \Epoint\SwisspostApi\Model\Entity _entity */
            $this->_entity = $this->objectManager->create(
                \Epoint\SwisspostApi\Model\Entity::class
            )->loadByTypeAndLocalId(self::ENTITY_TYPE,
                $this->getReferenceId());
            if (!empty($this->_entity->getExternalId()) && $this->_entity){
                $this->set(self::EXTERNAL_ID_CODE, $this->_entity->getExternalId());
            } else {
                return null;
            }
        }
        return $this->get(self::EXTERNAL_ID_CODE);
    }

    /**
     * Return local id.
     *
     * @return mixed
     */
    public function getLocalId()
    {
        if ($this->getExternalId()) {
            /** @var \Epoint\SwisspostApi\Model\Entity _entity */
            $this->_entity = $this->objectManager->create(
                \Epoint\SwisspostApi\Model\Entity::class
            )->loadByTypeAndExternalId(self::ENTITY_TYPE,
                $this->getExternalId());
            if ($this->_entity) {
                return $this->_entity->getLocalId();
            }
        }
        return null;
    }

    /**
     * @param $externalId
     *
     * @return mixed
     */
    public function toLocalByExternalId($externalId)
    {
        $product
            = $this->objectManager->create(\Magento\Catalog\Model\Product::class);
        if ($externalId) {
            $this->_entity = $this->objectManager->create(
                \Epoint\SwisspostApi\Model\Entity::class
            )->loadByTypeAndExternalId(self::ENTITY_TYPE,
                $externalId);
            if ($this->_entity) {
                $product = $product->load($this->_entity->getLocalId());
            }
        }
        return $product;
    }

    /**
     * Save local entity
     *
     * @param $localId
     *
     * @throws \Exception
     */
    public function connect($localId)
    {
        if (!$localId) {
            throw new \Exception(__('Missing local id.'));
        }

        if (!$this->_entity) {
            $savedLocalId = $this->getLocalId();
            if ($savedLocalId && $savedLocalId != $localId) {
                throw new \Exception(__('Entity conflict on save.'));
            }
        }

        // External code
        if (!$this->getExternalId()) {
            $this->_entity->setExternalId('');
        } else {
            $this->_entity->setExternalId($this->getExternalId());
        }

        $this->_entity->setType(self::ENTITY_TYPE);

        // Automatic export flag
        // Default
        $this->_entity->setAutomaticExport('0');
        if ($this->get(self::ENTITY_AUTOMATIC_EXPORT)) {
            $this->_entity->setAutomaticExport($this->get(self::ENTITY_AUTOMATIC_EXPORT));
        }
        // Export tryouts
        // Default
        $this->_entity->setExportTryouts('0');
        if ($this->get(self::ENTITY_EXPORT_TRYOUTS)) {
            $this->_entity->setExportTryouts($this->get(self::ENTITY_EXPORT_TRYOUTS));
        }

        $this->_entity->setLocalId($localId);
        $this->_entity->save();
    }
}
