<?php

namespace Epoint\SwisspostSales\Model\System\Config;

class DynamicMapping extends \Magento\Config\Model\Config\Backend\Serialized\ArraySerialized
{

    /**
     * @inheritdoc
     */
    public function beforeSave()
    {
        // For value validations
        $values = $this->getValue();

        // Validations
        $this->setValue($values);

        return parent::beforeSave();
    }
}