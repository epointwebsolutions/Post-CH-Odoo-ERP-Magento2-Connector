<?php
/**
 * Created by PhpStorm.
 * User: rudy23c
 * Date: 05.10.2017
 * Time: 18:48
 */

namespace Epoint\SwisspostDebug\Plugin\Block\Widget\Button;

use Magento\Backend\Block\Widget\Button\Toolbar as ToolbarContext;
use Magento\Framework\View\Element\AbstractBlock;
use Magento\Backend\Block\Widget\Button\ButtonList;
use Magento\Framework\App\Request\Http as RequestHttp;

class OrderOdooExport
{

    /**
     * @param ToolbarContext $toolbar
     * @param AbstractBlock $context
     * @param ButtonList $buttonList
     * @return array
     */
    public function beforePushButtons(
        ToolbarContext $toolbar,
        \Magento\Framework\View\Element\AbstractBlock $context,
        \Magento\Backend\Block\Widget\Button\ButtonList $buttonList
    ) {
        if (!$context instanceof \Magento\Sales\Block\Adminhtml\Order\View) {
            return [$context, $buttonList];
        }

        $orderId = $context->getRequest()->getParam('order_id');

        $message = __('Are you sure you want to export the order to Odoo?');
        $url = $context->getUrl('swisspost/order_export', ['order_id'=>$orderId]);
        $buttonList->add('order_export_odoo',
            [
                'label' => __('Odoo Export'),
                'on_click'    => "confirmSetLocation('{$message}', '{$url}')",
                'class' => 'action-secondary'
            ]
        );

        return [$context, $buttonList];
    }
}