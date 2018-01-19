<?php

namespace Epoint\SwisspostApi\Model\Api;


use Magento\Framework\Event\Manager;
use Magento\Framework\ObjectManagerInterface;
use \Psr\Log\LoggerInterface;
use Epoint\SwisspostApi\Helper\Resource;
use Epoint\SwisspostApi\Helper\Transformer as HelperTransformer;
use Epoint\SwisspostApi\Model\Api\Account as ApiModelAccount;
use Epoint\SwisspostApi\Model\Api\AnonymousAccount as ApiModelAnonymousAccount;

class Address extends ApiDataObject
{
    /**
     * Because the system has an issue if address_ref and account_ref have the same value,
     * the ADDRESS_REF_PREFIX will be used to avoid it
     *
     * @const ADDRESS_REF_PREFIX
     */
    const ADDRESS_REF_PREFIX = 'adr';

    /**
     * In order to avoid duplicates between register customer and guest(anonymous) address
     * the ADDRESS_REF_PREFIX will be used to avoid it
     *
     * @const ADDRESS_REF_PREFIX_FOR_GUEST
     */
    const ADDRESS_REF_PREFIX_FOR_GUEST = 'adr_guest';

    /**
     * @var \Epoint\SwisspostApi\Helper\Transformer
     */
    protected $helperTransformer;

    /**
     * @var \Epoint\SwisspostApi\Model\Api\Account
     */
    protected $apiModelAccount;

    /**
     * @var \Epoint\SwisspostApi\Model\Api\AnonymousAccount
     */
    protected $apiModelAnonymousAccount;

    /**
     * Address constructor.
     *
     * @param ObjectManagerInterface $objectManager
     * @param Resource               $resource
     * @param Manager                $eventManager
     * @param LoggerInterface        $logger
     * @param HelperTransformer      $helperTransformer
     * @param Account                $apiModelAccount
     * @param AnonymousAccount       $apiModelAnonymousAccount
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        Resource $resource,
        Manager $eventManager,
        LoggerInterface $logger,
        HelperTransformer $helperTransformer,
        ApiModelAccount $apiModelAccount,
        ApiModelAnonymousAccount $apiModelAnonymousAccount
    ) {
        parent::__construct($objectManager, $resource, $eventManager, $logger);
        $this->helperTransformer = $helperTransformer;
        $this->apiModelAccount = $apiModelAccount;
        $this->apiModelAnonymousAccount = $apiModelAnonymousAccount;
    }

    /**
     * @inheritdoc
     */
    public function getReferenceId($objectId = '')
    {
        if (!empty($objectId)) {
            return self::ADDRESS_REF_PREFIX . $objectId;
        }
        return $this->get('address_ref');
    }

    /**
     * @inheritdoc
     */
    public function getReferenceIdForAnonymousCustomer($objectId = '')
    {
        if (!empty($objectId)) {
            return self::ADDRESS_REF_PREFIX_FOR_GUEST . $objectId;
        }
        return $this->get('address_ref');
    }

    /**
     * @inheritdoc
     */
    public function getInstance($address)
    {
        $apiObject = $this->objectManager->get(
            \Epoint\SwisspostApi\Model\Api\Address::class
        );

        if (!$address->getId()) {
            throw new \Exception(__('Missing address id.'));
        }

        if (!$address->getCustomerId()) {
            throw new \Exception(__('Missing customer id.'));
        }

        $apiObject->set('account_ref',$this->apiModelAccount->getReferenceId($address->getCustomerId()));
        // Add prefix for address ref
        $apiObject->set('address_ref', $this->getReferenceId($address->getId()));

        if ($address->getFirstname()) {
            $apiObject->set('address_firstname', $address->getFirstname());
        }

        $apiObject->set('address_lastname', '');
        if ($address->getLastname()) {
            $apiObject->set('address_lastname', $address->getLastname());
        }

        if ($address->getCompany()) {
            $apiObject->set('address_company', $address->getCompany());
        }

        $street = $address->getStreet();
        if (isset($street[0]) && $street[0]) {
            $apiObject->set('address_street', $street[0]);
        }

        if (isset($street[1]) && $street[1]) {
            $apiObject->set('address_street2', $street[1]);
        }

        if ($address->getPostcode()) {
            $apiObject->set('address_zip', $address->getPostcode());
        }

        if ($address->getCity()) {
            $apiObject->set('address_city', $address->getCity());
        }

        if ($address->getCountry()) {
            $apiObject->set('address_country', $address->getCountry());
        }

        if ($address->getTelephone()) {
            $apiObject->set('address_mobile', $address->getTelephone());
        }

        $customer
            = $this->objectManager->create(\Magento\Customer\Model\Customer::class)
            ->load($address->getCustomerID());

        if ($customer) {
            if ($this->helperTransformer->toAccountTitle($customer)) {
                $apiObject->set(
                    'address_title', $this->helperTransformer->toAccountTitle($customer)
                );
            }

            $apiObject->set(
                'address_gender', $this->helperTransformer->toGender($customer->getGender())
            );

            $apiObject->set(
                'account_address_type', $this->helperTransformer->toAccountAddressType()
            );

            if ($customer->getEmail()) {
                $apiObject->set('address_email', $customer->getEmail());
            }

            /*if ($customer->getWebsiteId()) {
                $apiObject->set('address_website', $customer->getWebsiteId());
            }*/

            $apiObject->set('address_lang',
                $this->helperTransformer->getStoreLanguage($address->getStoreId()));
        }
        return $apiObject;
    }

    /**
     * Will return the address attached to an order made by an anonymous customer
     * @param \Magento\Customer\Model\Address $address
     *
     * @return mixed
     * @throws \Exception
     */
    public function getInstanceForAnonymousCustomer($address)
    {
        $apiObject = $this->objectManager->get(
            \Epoint\SwisspostApi\Model\Api\Address::class
        );

        $apiObject->set('account_ref',$this->apiModelAnonymousAccount->getReferenceId($address->getEmail()));
        // Add prefix for address ref
        $apiObject->set('address_ref', $this->getReferenceIdForAnonymousCustomer($address->getEntityId()));

        if ($address->getFirstname()) {
            $apiObject->set('address_firstname', $address->getFirstname());
        }

        $apiObject->set('address_lastname', '');
        if ($address->getLastname()) {
            $apiObject->set('address_lastname', $address->getLastname());
        }

        if ($address->getCompany()) {
            $apiObject->set('address_company', $address->getCompany());
        }

        $street = $address->getStreet();
        if (isset($street[0]) && $street[0]) {
            $apiObject->set('address_street', $street[0]);
        }

        if (isset($street[1]) && $street[1]) {
            $apiObject->set('address_street2', $street[1]);
        }

        if ($address->getPostcode()) {
            $apiObject->set('address_zip', $address->getPostcode());
        }

        if ($address->getCity()) {
            $apiObject->set('address_city', $address->getCity());
        }

        if ($address->getCountry()) {
            $apiObject->set('address_country', $address->getCountry());
        }

        if ($address->getTelephone()) {
            $apiObject->set('address_mobile', $address->getTelephone());
        }

        $customer
            = $this->objectManager->create(\Magento\Customer\Model\Customer::class)
            ->load($address->getCustomerID());

        if ($customer) {
            if ($this->helperTransformer->toAccountTitle($customer)) {
                $apiObject->set(
                    'address_title', $this->helperTransformer->toAccountTitle($customer)
                );
            }

            $apiObject->set(
                'address_gender', $this->helperTransformer->toGender($customer->getGender())
            );

            $apiObject->set(
                'account_address_type', $this->helperTransformer->toAccountAddressType()
            );

            if ($customer->getEmail()) {
                $apiObject->set('address_email', $customer->getEmail());
            }

            /*if ($customer->getWebsiteId()) {
                $apiObject->set('address_website', $customer->getWebsiteId());
            }*/

            $apiObject->set('address_lang',
                $this->helperTransformer->getStoreLanguage($address->getStoreId()));
        }
        return $apiObject;
    }

    /**
     * @inheritdoc
     */
    public function delete($address)
    {
        if ($address) {
            $this->load($address);
            if ($this->get('address_ref')) {
                if ($this->get('active')) {
                    $this->set('active', false);
                    return $this->save();
                }
            }
        }
        return false;
    }

    /**
     * Updating the account_address_type with billing identifier
     */
    public function setAccountAddressTypeForBilling()
    {
        $this->set(
            'account_address_type', $this->helperTransformer->toBillingAccountAddressType()
        );
    }

    /**
     * Updating the account_address_type with shipping identifier
     */
    public function setAccountAddressTypeForShipping()
    {
        $this->set(
            'account_address_type', $this->helperTransformer->toShippingAccountAddressType()
        );
    }

    /**
     * Export
     *
     * @inheritdoc
     */
    public function save()
    {
        return $this->apiResource->createUpdateAddress($this);
    }

    /**
     * Load from APi.
     *
     * @param $address
     *
     * @return $this
     */
    public function load($address)
    {
        $this->reset();
        $filter = [
            'filters' => ['address_ref = ' . $this->getReferenceId($address->getId()) . ''],
        ];
        /** @var  \Epoint\SwisspostApi\Helper\Api\Curl\Result $result */
        $result = $this->apiResource->searchReadAddress($filter);
        if ($result->isOk() && $result->get('values')) {
            $item = current($result->get('values'));
            if ($item) {
                $this->loadFromResultItem($item);
            }
        }
        return $this;
    }
}
