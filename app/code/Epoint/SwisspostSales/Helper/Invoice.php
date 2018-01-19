<?php

namespace Epoint\SwisspostSales\Helper;

class Invoice extends Data
{
    /**
     * Checking config settings
     *
     * @return bool
     */
    public function isPrintPdfEnabled()
    {
        return $this->getConfigValue(
            self::XML_PATH . 'order_invoice/odoo_invoice'
        ) ? true : false;
    }
}
