<?php

namespace Epoint\SwisspostApi\Model\Api\Config\Source\Language;


class Code implements \Magento\Framework\Option\ArrayInterface
{
    const LANGUAGE_CODES = ['de', 'fr', 'it'];

    /**
     * Build options list for select.
     * @return array
     */
    public function toOptionArray()
    {
        $options = [];
        foreach (self::LANGUAGE_CODES as $code){
            $options[] = [
                'value' => $code,
                'label' => strtoupper($code),
            ];
        }
        return $options;
    }

}
