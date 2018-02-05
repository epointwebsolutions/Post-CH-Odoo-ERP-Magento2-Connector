<?php

namespace Epoint\SwisspostApi\Model\Api;

use Magento\Framework\Event\Manager;
use Magento\Framework\ObjectManagerInterface;
use \Psr\Log\LoggerInterface;
use Epoint\SwisspostApi\Helper\Resource;
use Epoint\SwisspostApi\Helper\Transformer as HelperTransformer;
use \Magento\Customer\Api\GroupRepositoryInterface;

class Account extends ApiDataObject
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
     * @var \Magento\Customer\Api\GroupRepositoryInterface
     */
    protected $groupRepository;

    /**
     * Account constructor.
     *
     * @param ObjectManagerInterface   $objectManager
     * @param Resource                 $resource
     * @param Manager                  $eventManager
     * @param LoggerInterface          $logger
     * @param HelperTransformer        $helperTransformer
     * @param GroupRepositoryInterface $groupRepository
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        Resource $resource,
        Manager $eventManager,
        LoggerInterface $logger,
        HelperTransformer $helperTransformer,
        GroupRepositoryInterface $groupRepository
    ) {
        parent::__construct($objectManager, $resource, $eventManager, $logger);
        $this->helperTransformer = $helperTransformer;
        $this->groupRepository = $groupRepository;
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
    public function getInstance($customer)
    {
        $apiObject = $this->objectManager->get(
            \Epoint\SwisspostApi\Model\Api\Account::class
        );

        // Verify if customer exist
        if (!$customer->getId()) {
            throw new \Exception(__('Missing customer id.'));
        }

        // Account_ref -> unique ID chosen by the webshop
        $apiObject->set('account_ref', $this->getReferenceId($customer->getId()));

        // Account title -> The title used to address the customer.
        if (!empty($this->helperTransformer->toAccountTitle($customer))){
            $apiObject->set(
                'account_title', $this->helperTransformer->toAccountTitle($customer)
            );
        }

        // Customer first name
        if ($customer->getFirstname()) {
            $apiObject->set('account_firstname', $customer->getFirstname());
        }

        // Customer last name
        $apiObject->set('account_lastname', '');
        if ($customer->getLastname()) {
            $apiObject->set('account_lastname', $customer->getLastname());
        }

        // Account gender -> One of male, female or other
        $apiObject->set(
            'account_gender', $this->helperTransformer->toGender($customer->getGender())
        );

        // Account address type -> Can be one of default, invoice, shipping
        $apiObject->set(
            'account_address_type', $this->helperTransformer->toAccountAddressType()
        );

        // Account maintag -> The name of the Main Tag. If not existing, it will be created.
        $apiObject->set('account_maintag', $this->helperTransformer->toAccountMainTag());

        // Active flag -> This allows to disable the record.
        $apiObject->set('active', true);

        // Customer email
        if ($customer->getEmail()) {
            $apiObject->set('account_email', $customer->getEmail());
        }

        /*if ($customer->getWebsiteId()) {
            $apiObject->set('account_website', $customer->getWebsiteId());
        }*/

        // Account category -> List of categories. If not existing, they will be created.
        if ($accountCategory = $this->helperTransformer->toAccountOdooCategory($customer->getGroupId())) {
            $apiObject->set('account_category', $accountCategory);
        } else {
            // Getting local customer group name
            $customerGroup = $this->groupRepository->getById($customer->getGroupId());
            $apiObject->set('account_category', $customerGroup->getCode());
        }

        // Account lang -> The preferred language. Can be one of EN, DE, FR, IT
        // All country codes will be also accepted lowercase
        $apiObject->set('account_lang',
            $this->helperTransformer->getStoreLanguage($customer->getStoreId()));

        if ((int)$customer->getDefaultBilling() > 0) {
            $addressModel = $this->objectManager->create(
                \Magento\Customer\Model\Address::class
            )->load($customer->getDefaultBilling());

            // Account company -> The name of the company of the customer
            if ($addressModel->getCompany()) {
                $apiObject->set('account_company', $addressModel->getCompany());
            }

            // Address first line
            $street = $addressModel->getStreet();
            if (isset($street[0]) && $street[0]) {
                $apiObject->set('account_street', $street[0]);
            }

            // Address second line
            if (isset($street[1]) && $street[1]) {
                $apiObject->set('account_street2', $street[1]);
            }

            // Zip codes
            if ($addressModel->getPostcode()) {
                $apiObject->set('account_zip', $addressModel->getPostcode());
            }

            // City
            if ($addressModel->getCity()) {
                $apiObject->set('account_city', $addressModel->getCity());
            }

            // Country
            if ($addressModel->getCountry()) {
                $apiObject->set('account_country',
                    strtoupper($addressModel->getCountry()));
            }

            // Mobile phone
            if ($addressModel->getTelephone()) {
                $apiObject->set(
                    'account_mobile', $addressModel->getTelephone()
                );
            }
        }
        return $apiObject;
    }

    /**
     * @inheritdoc
     */
    public function delete($customer)
    {
        if ($customer) {
            // Retriving customer data
            $this->load($customer);
            // If account ref is present and is active -> disable record by setting active to false
            if ($this && $this->get('account_ref')) {
                if ($this->get('active')) {
                    $this->set('active', false);
                    return $this->save();
                }
            }
        }
        return false;
    }

    /**
     * @inheritdoc
     */
    public function save()
    {
        return $this->apiResource->createUpdateAccount($this);
    }

    /**
     * Load API account from customer
     *
     * @param $customer
     *
     * @return $this
     */
    public function load($customer)
    {
        // Removing data
        $this->reset();
        // Setting up the filters for request
        $filter = [
            'filters' => ['account_ref= ' . $this->getReferenceId($customer->getId())]
        ];
        // Request account data
        $result = $this->apiResource->searchReadAccount($filter);
        if ($result->isOk() && $result->get('values')) {
            $item = current($result->get('values'));
            // If item is present -> setup values
            if ($item) {
                $this->loadFromResultItem($item);
            }
        }
        return $this;
    }

    /**
     * Check the credit status for one account and amount
     *
     * @param $customerID
     * @param $creditAmount
     *
     * @return \Epoint\SwisspostApi\Helper\Api\Curl\Result|\Epoint\SwisspostApi\Helper\Api\result
     */
    public function checkCustomerCredit($customerID, $creditAmount)
    {
        // Setup filter for request
        $filter = [
            'account_ref' => $this->getReferenceId($customerID),
            'amount'      => (float)$creditAmount
        ];
        
        return $this->apiResource->checkCustomerCredit($filter);
    }
}
