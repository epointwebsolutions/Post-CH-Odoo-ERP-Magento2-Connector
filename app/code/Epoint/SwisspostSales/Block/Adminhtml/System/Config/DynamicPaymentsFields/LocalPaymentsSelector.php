<?php

namespace Epoint\SwisspostSales\Block\Adminhtml\System\Config\DynamicPaymentsFields;

use Magento\Framework\View\Element\Context;
use Magento\Framework\ObjectManagerInterface;

class LocalPaymentsSelector extends \Magento\Framework\View\Element\Html\Select
{
    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * LocalPaymentsSelector constructor.
     *
     * @param Context                $context
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(
        Context $context,
        ObjectManagerInterface $objectManager
    ){
        $this->objectManager = $objectManager;
        parent::__construct($context);
    }

    /*
     * {@inheritdoc}
     */
    protected function _toHtml()
    {
        if (!$this->getOptions()) {
            $activeMethodsArray = $this->getActivePaymentsMethods();
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
    public function getActivePaymentsMethods()
    {
        $paymentModelConfig
            = $this->objectManager->get(\Magento\Payment\Model\Config::class);
        $scopeConfig
            = $this->objectManager->get(
            \Magento\Framework\App\Config\ScopeConfigInterface::class
        );
        $paymentsActive = $paymentModelConfig->getActiveMethods();
        $methods = [];
        foreach ($paymentsActive as $paymentCode => $paymentModel) {
            $paymentTitle = $scopeConfig
                ->getValue('payment/' . $paymentCode . '/title');
            $methods[$paymentCode] = [
                'label' => $paymentTitle,
                'value' => $paymentCode
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