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

class OrderTransferStatus
{

    /**
     * @param ToolbarContext $toolbar
     * @param AbstractBlock  $context
     * @param ButtonList     $buttonList
     *
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

        $message = __('Are you sure you want to check the order transfer status?');
        $url = $context->getUrl('swisspost/order_transfer', ['order_id' => $orderId]);
        $buttonList->add(
            'order_transfer_status',
            [
                'label'    => __('Odoo Transfer Status'),
                'on_click' => "confirmSetLocation('{$message}', '{$url}')",
                'class'    => 'action-secondary'
            ]
        );

        return [$context, $buttonList];
    }
}