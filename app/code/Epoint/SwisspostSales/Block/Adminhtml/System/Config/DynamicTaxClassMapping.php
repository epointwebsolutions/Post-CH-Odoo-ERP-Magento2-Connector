<?php

namespace Epoint\SwisspostSales\Block\Adminhtml\System\Config;

use Magento\Framework\DataObject;

class DynamicTaxClassMapping extends \Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray
{
    /**
     * @var \Epoint\SwisspostSales\Block\Adminhtml\System\Config\DynamicTaxClassFields\LocalTaxClassSelector
     */
    protected $localTaxClassRenderer;

    /**
     * @inheritdoc
     */
    protected function getActiveLocalTaxClassRenderer()
    {
        if (!$this->localTaxClassRenderer){
            $this->localTaxClassRenderer = $this->getLayout()->createBlock(
                '\Epoint\SwisspostSales\Block\Adminhtml\System\Config\DynamicTaxClassFields\LocalTaxClassSelector',
                '', ['data' => ['is_render_to_js_template' => true]]
            );
        }
        return $this->localTaxClassRenderer;
    }

    /*
     * @inherit
     */
    protected function _prepareToRender()
    {
        $this->addColumn('local_tax_class_code',
            [
                'label' => __('Local Tax Class'),
                'renderer' => $this->getActiveLocalTaxClassRenderer()
            ]
        );
        $this->addColumn('odoo_tax_class_code',
            [
                'label' => __('Odoo Tax Class'),
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
        $localPayment = $row->getLocalTaxClassCode();

        $options = [];
        if ($localPayment) {
            $options['option_' . $this->getActiveLocalTaxClassRenderer()->calcOptionHash($localPayment)]
                = 'selected="selected"';
        }
        $row->setData('option_extra_attrs', $options);
    }

    /**
     * {@inheritdoc}
     */
    public function renderCellTemplate($columnName)
    {
        if ($columnName === "odoo_tax_class_code") {
            $this->_columns[$columnName]['class'] = 'input-text required-entry';
            $this->_columns[$columnName]['style'] = 'width:250px';
        }
        return parent::renderCellTemplate($columnName);
    }
}