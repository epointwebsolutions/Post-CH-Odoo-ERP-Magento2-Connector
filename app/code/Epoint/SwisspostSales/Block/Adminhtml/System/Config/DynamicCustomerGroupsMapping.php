<?php

namespace Epoint\SwisspostSales\Block\Adminhtml\System\Config;

use Magento\Framework\DataObject;

class DynamicCustomerGroupsMapping extends \Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray
{
    /**
     * @var \Epoint\SwisspostSales\Block\Adminhtml\System\Config\DynamicCustomerGroupsFields\LocalCustomerGroupsSelector
     */
    protected $localCustomerGroupsRenderer;

    /**
     * @inheritdoc
     */
    protected function getActiveLocalCustomerGroupsRenderer()
    {
        if (!$this->localCustomerGroupsRenderer){
            $this->localCustomerGroupsRenderer = $this->getLayout()->createBlock(
                '\Epoint\SwisspostSales\Block\Adminhtml\System\Config\DynamicCustomerGroupsFields\LocalCustomerGroupsSelector',
                '', ['data' => ['is_render_to_js_template' => true]]
            );
        }
        return $this->localCustomerGroupsRenderer;
    }

    /*
     * @inherit
     */
    protected function _prepareToRender()
    {
        $this->addColumn('local_customer_group_code',
            [
                'label' => __('Local Customer Group'),
                'renderer' => $this->getActiveLocalCustomerGroupsRenderer()
            ]
        );
        $this->addColumn('odoo_customer_group_code',
            [
                'label' => __('Odoo Customer Group'),
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
        $localCustomerGroup = $row->getLocalCustomerGroupCode();

        $options = [];
        if ($localCustomerGroup) {
            $options['option_' . $this->getActiveLocalCustomerGroupsRenderer()->calcOptionHash($localCustomerGroup)]
                = 'selected="selected"';
        }
        $row->setData('option_extra_attrs', $options);
    }

    /**
     * {@inheritdoc}
     */
    public function renderCellTemplate($columnName)
    {
        if ($columnName === "odoo_customer_group_code") {
            $this->_columns[$columnName]['class'] = 'input-text required-entry';
            $this->_columns[$columnName]['style'] = 'width:250px';
        }

        return parent::renderCellTemplate($columnName);
    }
}