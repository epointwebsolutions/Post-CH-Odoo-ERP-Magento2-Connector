<?php

namespace Epoint\SwisspostDebug\Block\Adminhtml\Product\Edit\Button;

use Magento\Catalog\Block\Adminhtml\Product\Edit\Button\Generic;
use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

class ImportOdooProduct extends Generic implements ButtonProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function getButtonData()
    {
        $productId = $this->getProduct()->getId();
        $productSku = $this->getProduct()->getSku();
        $message = __('Are you sure you want to import the product?');
        $url = $this->getUrl('swisspost/product_import',
            ['id' => $productId, 'sku' => $productSku]);
        $data = [
            'label'       => __('Odoo Import'),
            'back_button' => 'import_odoo',
            'class'       => 'action-secondary',
            'on_click'    => "confirmSetLocation('{$message}', '{$url}')",
            'sort_order'  => 19
        ];
        return $data;
    }
}
