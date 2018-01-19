<?php

namespace Epoint\SwisspostSales\Block\Adminhtml\System\Config\DynamicShippingFields;

use Magento\Framework\View\Element\Context;
use Magento\Framework\ObjectManagerInterface;
use Magento\Shipping\Model\Config as ShippingModelConfig;

class LocalShippingSelector extends \Magento\Framework\View\Element\Html\Select
{
    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var ShippingModelConfig
     */
    protected $shippingModelConfig;

    /**
     * LocalShippingSelector constructor.
     *
     * @param Context                $context
     * @param ObjectManagerInterface $objectManager
     * @param ShippingModelConfig    $shippingModelConfig
     */
    public function __construct(
        Context $context,
        ObjectManagerInterface $objectManager,
        ShippingModelConfig $shippingModelConfig
    ){
        $this->objectManager = $objectManager;
        $this->shippingModelConfig = $shippingModelConfig;
        parent::__construct($context);
    }

    /*
     * {@inheritdoc}
     */
    protected function _toHtml()
    {
        if (!$this->getOptions()) {
            $activeMethodsArray = $this->getActiveShippingMethods();
            // Constructing the available options
            $methods = [];
            foreach ($activeMethodsArray as $key => $value) {
                $methods[$key] = $value['label'];
            }
            $this->setOptions($methods);
        }
        return parent::_toHtml();
    }

    /**
     * Return a list with available options
     * @return array
     */
    public function getActiveShippingMethods()
    {
        $shippingActive = $this->shippingModelConfig->getActiveCarriers();
        $methods = [];
        foreach ($shippingActive as $shippingCode => $shippingModel) {
            $shippingTitle = $this->_scopeConfig
                ->getValue('carriers/' . $shippingCode . '/title');
            $methods[$shippingCode] = [
                'label' => $shippingTitle,
                'value' => $shippingCode
            ];
        }
        return $methods;
    }


    /**
     * Sets name for input element
     *
     * @param string $value
     * @return $this
     */
    public function setInputName($value)
    {
        return $this->setName($value);
    }
}