<?php

namespace Epoint\SwisspostSales\Block\Adminhtml\System\Config\DynamicPaymentsFields;

use Magento\Framework\View\Element\Context;
use Magento\Framework\ObjectManagerInterface;
use Magento\Payment\Model\Config as PaymentConfigModel;
use Magento\Framework\App\Config\ScopeConfigInterface;

class LocalPaymentsSelector extends \Magento\Framework\View\Element\Html\Select
{
    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var \Magento\Payment\Model\Config
     */
    protected $paymentModelConfig;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * LocalPaymentsSelector constructor.
     *
     * @param Context                $context
     * @param ObjectManagerInterface $objectManager
     * @param PaymentConfigModel     $paymentModelConfig
     * @param ScopeConfigInterface   $scopeConfig
     */
    public function __construct(
        Context $context,
        ObjectManagerInterface $objectManager,
        PaymentConfigModel $paymentModelConfig,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->objectManager = $objectManager;
        $this->paymentModelConfig = $paymentModelConfig;
        $this->scopeConfig = $scopeConfig;
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
     *
     * @return array
     */
    public function getActivePaymentsMethods()
    {
        $paymentsActive = $this->paymentModelConfig->getActiveMethods();
        $methods = [];
        foreach ($paymentsActive as $paymentCode => $paymentModel) {
            $paymentTitle = $this->scopeConfig->getValue('payment/' . $paymentCode . '/title');
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
     *
     * @return $this
     */
    public function setInputName($value)
    {
        return $this->setName($value);
    }
}