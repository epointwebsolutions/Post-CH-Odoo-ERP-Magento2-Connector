<?php

namespace Epoint\SwisspostSales\Block\Adminhtml\System\Config;

use Magento\Framework\DataObject;

class DynamicPaymentsMapping extends \Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray
{
    /**
     * @var \Epoint\SwisspostSales\Block\Adminhtml\System\Config\DynamicPaymentsFields\LocalPaymentsSelector
     */
    protected $localPaymentRenderer;

    /**
     * @inheritdoc
     */
    protected function getActiveLocalPaymentsRenderer()
    {
        if (!$this->localPaymentRenderer){
            $this->localPaymentRenderer = $this->getLayout()->createBlock(
                '\Epoint\SwisspostSales\Block\Adminhtml\System\Config\DynamicPaymentsFields\LocalPaymentsSelector',
                '', ['data' => ['is_render_to_js_template' => true]]
            );
        }
        return $this->localPaymentRenderer;
    }

    /*
     * @inherit
     */
    protected function _prepareToRender()
    {
        $this->addColumn('local_payment_code',
            [
                'label' => __('Local Payment'),
                'renderer' => $this->getActiveLocalPaymentsRenderer()
            ]
        );
        $this->addColumn('odoo_payment_code',
            [
                'label' => __('Odoo Payment'),
                'renderer' => false
            ]
        );

        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add');
    }

    /**
     * {@inheritdoc}
     */
    protected function _prepareArrayRow(DataObject $row)
    {
        $localPayment = $row->getLocalPaymentCode();

        $options = [];
        if ($localPayment) {
            $options['option_' . $this->getActiveLocalPaymentsRenderer()->calcOptionHash($localPayment)]
                = 'selected="selected"';
        }
        $row->setData('option_extra_attrs', $options);
    }

    /**
     * {@inheritdoc}
     */
    public function renderCellTemplate($columnName)
    {
        if ($columnName === "odoo_payment_code") {
            $this->_columns[$columnName]['class'] = 'input-text required-entry';
            $this->_columns[$columnName]['style'] = 'width:250px';
        }

        return parent::renderCellTemplate($columnName);
    }
}