<?php

namespace Epoint\SwisspostApi\Model\Api;

use Magento\Framework\Event\Manager;
use Magento\Framework\ObjectManagerInterface;
use \Psr\Log\LoggerInterface;
use Epoint\SwisspostApi\Helper\Resource;
use Epoint\SwisspostApi\Helper\Transformer as HelperTransformer;
use Magento\Sales\Model\Order as LocalOrder;

class AnonymousAccount extends ApiDataObject
{
    /**
     * To avoid duplicates we are using the prefix for account_ref
     * @const ACCOUNT_REF_PREFIX
     */
    const ACCOUNT_REF_PREFIX = 'acc';

    /**
     * @var HelperTransformer
     */
    protected $helperTransformer;

    /**
     * AnonymousAccount constructor.
     *
     * @param ObjectManagerInterface $objectManager
     * @param Resource               $resource
     * @param Manager                $eventManager
     * @param LoggerInterface        $logger
     * @param HelperTransformer      $helperTransformer
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        Resource $resource,
        Manager $eventManager,
        LoggerInterface $logger,
        HelperTransformer $helperTransformer
    ) {
        parent::__construct($objectManager, $resource, $eventManager, $logger);
        $this->helperTransformer = $helperTransformer;
    }

    /**
     * @inheritdoc
     */
    public function getReferenceId($objectId = '')
    {
        if (!empty($objectId)) {
            return self::ACCOUNT_REF_PREFIX . $objectId;
        }
        return $this->get('account_ref');
    }

    /**
     * @inheritdoc
     */
    public function getInstance($orderIncrementId)
    {
        // Get the order by incrementId
        /** @var \Magento\Sales\Model\Order $order */
        $order = $this->objectManager->create(LocalOrder::class)->loadByIncrementId($orderIncrementId);

        $apiObject = $this->objectManager->get(
            \Epoint\SwisspostApi\Model\Api\AnonymousAccount::class
        );

        // Verify if customer exist
        if (!$order->getIncrementId()) {
            throw new \Exception(__('Missing order incrementId needed for constructing the anonymous customer data.'));
        }

        // Account_ref -> unique ID chosen by the webshop
        // In this case the reference account will be the customer email
        if ($order->getCustomerEmail() !== null){
            $apiObject->set('account_ref', $this->getReferenceId($order->getCustomerEmail()));
            $apiObject->set('account_email', $order->getCustomerEmail());
        }

        // Account maintag -> The name of the Main Tag. If not existing, it will be created.
        $apiObject->set('account_maintag', $this->helperTransformer->toAccountMainTag());

        // Getting the order address
        /** @var \Magento\Sales\Model\Order\Address $orderAddress */
        $orderAddress = $order->getShippingAddress();
        if ($orderAddress === null){
            // If no shipping address has been set we check the billing address as well
            if ($order->getBillingAddress() !== null){
                $orderAddress = $order->getBillingAddress();
            }
        }

        // Setup data from order address
        if ($orderAddress !== null){
            // Account title -> The title used to address the customer.
            if ($orderAddress->getPrefix() !== null){
                $apiObject->set(
                    'account_title', $this->helperTransformer->toAccountTitleByPrefix($orderAddress->getPrefix())
                );
            }

            // Customer first name
            if ($orderAddress->getFirstname() !== null) {
                $apiObject->set('account_firstname', $orderAddress->getFirstname());
            }

            // Customer last name
            $apiObject->set('account_lastname', '');
            if ($orderAddress->getLastname() !== null) {
                $apiObject->set('account_lastname', $orderAddress->getLastname());
            }

            // Account company -> The name of the company of the customer
            if ($orderAddress->getCompany()) {
                $apiObject->set('account_company', $orderAddress->getCompany());
            }

            // Default value
            $apiObject->set('account_street', '');
            // Address first line
            $street = $orderAddress->getStreet();
            if (isset($street[0]) && $street[0]) {
                $apiObject->set('account_street', $street[0]);
            }

            // Address second line
            if (isset($street[1]) && $street[1]) {
                $apiObject->set('account_street2', $street[1]);
            }

            // Zip codes
            if ($orderAddress->getPostcode()) {
                $apiObject->set('account_zip', $orderAddress->getPostcode());
            }

            // City
            if ($orderAddress->getCity()) {
                $apiObject->set('account_city', $orderAddress->getCity());
            }

            // Country
            if ($orderAddress->getCountryId()) {
                $apiObject->set('account_country',
                    strtoupper($orderAddress->getCountryId()));
            }

            // Mobile phone
            if ($orderAddress->getTelephone()) {
                $apiObject->set(
                    'account_mobile', $orderAddress->getTelephone()
                );
            }

            // Fax
            if ($orderAddress->getFax()){
                $apiObject->set(
                    'account_fax', $orderAddress->getFax()
                );
            }
        } else {
            throw new \Exception(__('Missing order shipping address for the anonymous customer.'));
        }

        // Account category -> List of categories. If not existing, they will be created.
        if ($accountCategory = $this->helperTransformer->toAccountOdooCategory($order->getCustomerGroupId())) {
            $apiObject->set('account_category', $accountCategory);
        }

        return $apiObject;
    }
}
