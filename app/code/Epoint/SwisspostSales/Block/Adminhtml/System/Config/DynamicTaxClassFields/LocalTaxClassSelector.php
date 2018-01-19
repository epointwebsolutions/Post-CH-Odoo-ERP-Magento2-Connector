<?php

namespace Epoint\SwisspostSales\Block\Adminhtml\System\Config\DynamicTaxClassFields;

use Magento\Framework\View\Element\Context;
use Magento\Framework\ObjectManagerInterface;
use Magento\Tax\Model\TaxClass\Source\Product as ProductTaxClassSource;

class LocalTaxClassSelector extends \Magento\Framework\View\Element\Html\Select
{
    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var ProductTaxClassSource
     */
    protected $productTaxClassSource;

    /**
     * LocalTaxClassSelector constructor.
     *
     * @param Context                $context
     * @param ObjectManagerInterface $objectManager
     * @param ProductTaxClassSource  $productTaxClassSource
     */
    public function __construct(
        Context $context,
        ObjectManagerInterface $objectManager,
        ProductTaxClassSource $productTaxClassSource
    ){
        $this->objectManager = $objectManager;
        $this->productTaxClassSource = $productTaxClassSource;
        parent::__construct($context);
    }

    /*
     * {@inheritdoc}
     */
    protected function _toHtml()
    {
        if (!$this->getOptions()) {
            $activeMethodsArray = $this->getAllProductTaxClasses();

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
    public function getAllProductTaxClasses()
    {
        // Get all tax classes
        $taxClasses = $this->productTaxClassSource->getAllOptions(true);

        $classes = [];
        // Setting up classes
        foreach ($taxClasses as $taxCode => $taxName) {
            $classes[$taxCode] = [
                'label' => $taxName['label'],
                'value' => $taxName['value']
            ];
        }
        return $classes;
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