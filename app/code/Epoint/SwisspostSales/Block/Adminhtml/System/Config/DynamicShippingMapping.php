<?php

namespace Epoint\SwisspostSales\Block\Adminhtml\System\Config;

use Magento\Framework\DataObject;

class DynamicShippingMapping extends \Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray
{
    /**
     * @var \Epoint\SwisspostSales\Block\Adminhtml\System\Config\DynamicShippingFields\LocalShippingSelector
     */
    protected $localShippingRenderer;

    /**
     * @inheritdoc
     */
    protected function getActiveLocalShippingRenderer()
    {
        if (!$this->localShippingRenderer){
            $this->localShippingRenderer = $this->getLayout()->createBlock(
                '\Epoint\SwisspostSales\Block\Adminhtml\System\Config\DynamicShippingFields\LocalShippingSelector',
                '', ['data' => ['is_render_to_js_template' => true]]
            );
        }
        return $this->localShippingRenderer;
    }

    /*
     * @inherit
     */
    protected function _prepareToRender()
    {
        $this->addColumn('local_shipping_code',
            [
                'label' => __('Local Shipping'),
                'renderer' => $this->getActiveLocalShippingRenderer()
            ]
        );
        $this->addColumn('odoo_shipping_code',
            [
                'label' => __('Odoo Shipping'),
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
        $localPayment = $row->getLocalShippingCode();

        $options = [];
        if ($localPayment) {
            $options['option_' . $this->getActiveLocalShippingRenderer()->calcOptionHash($localPayment)]
                = 'selected="selected"';
        }
        $row->setData('option_extra_attrs', $options);
    }

    /**
     * {@inheritdoc}
     */
    public function renderCellTemplate($columnName)
    {
        if ($columnName === "odoo_shipping_code") {
            $this->_columns[$columnName]['class'] = 'input-text required-entry';
            $this->_columns[$columnName]['style'] = 'width:250px';
        }

        return parent::renderCellTemplate($columnName);
    }
}