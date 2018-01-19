<?php

namespace Epoint\SwisspostSales\Block\Adminhtml\System\Config\DynamicCustomerGroupsFields;

use Magento\Framework\View\Element\Context;
use Magento\Customer\Model\ResourceModel\Group\Collection as CustomerGroupCollection;

class LocalCustomerGroupsSelector extends \Magento\Framework\View\Element\Html\Select
{
    /**
     * Customer Group
     *
     * @var \Magento\Customer\Model\ResourceModel\Group\Collection
     */
    protected $customerGroupCollection;

    /**
     * LocalCustomerGroupsSelector constructor.
     *
     * @param Context                 $context
     * @param CustomerGroupCollection $customerGroupCollection
     */
    public function __construct(
        Context $context,
        CustomerGroupCollection $customerGroupCollection
    ){
        $this->customerGroupCollection = $customerGroupCollection;
        parent::__construct($context);
    }

    /*
     * {@inheritdoc}
     */
    protected function _toHtml()
    {
        if (!$this->getOptions()) {
            $activeMethodsArray = $this->getActiveCustomerGroups();
            // Constructing the available options
            $methods = [];
            foreach ($activeMethodsArray as $key => $value) {
                $methods[$value['value']] = $value['label'];
            }
            $this->setOptions($methods);
        }
        return parent::_toHtml();
    }

    /**
     * Return a list with available options
     * @return array
     */
    public function getActiveCustomerGroups()
    {
        return $this->customerGroupCollection->toOptionArray();
    }

    /**
     * Sets name for input element
     * @param $value
     *
     * @return mixed
     */
    public function setInputName($value)
    {
        return $this->setName($value);
    }
}