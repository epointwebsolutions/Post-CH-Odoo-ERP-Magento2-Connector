<?php

namespace Epoint\SwisspostSales\Controller\Adminhtml\Shipment;

use Epoint\SwisspostSales\Helper\Shipping;
use Magento\Framework\App\Filesystem\DirectoryList;

class PrintAction
    extends \Magento\Sales\Controller\Adminhtml\Shipment\PrintAction
{
    public function execute()
    {
        // Getting helper
        $helper = $this->_objectManager->get(
            \Epoint\SwisspostSales\Helper\Shipping::class
        );

        // Verify if the option is enabled from config
        if ($helper->isPrintPdfEnabled()) {
            // File factory
            $fileFactory = $this->_objectManager->get(
                \Magento\Framework\App\Response\Http\FileFactory::class
            );

            // Get shipment id
            $shipmentId = $this->getRequest()->getParam('shipment_id');
            /** @var \Magento\Sales\Model\Order\Shipment $shipment */
            $shipment = $this->_objectManager->create(\Magento\Sales\Model\Order\Shipment::class)->load($shipmentId);
            /** @var \Magento\Sales\Model\Order $localOrder */
            $localOrder = $shipment->getOrder();

            // Instatiate shipment model
            /** @var \Epoint\SwisspostApi\Model\Api\Shipping $apiShipment */
            $apiShipment = $this->_objectManager->get(
                \Epoint\SwisspostApi\Model\Api\Shipping::class
            )->getInstance($localOrder);

            // Get shipment docs
            $result = $apiShipment->load();
            $pdfContent = '';
            if ($result->isOK()) {
                // Checking to see what type it is: Binary or URL
                if ($apiShipment->get('type') === Shipping::DOCUMENT_TYPE_BINARY) {
                    if ($apiShipment->get('filename')
                        && $apiShipment->get(
                            'content'
                        )
                    ) {
                        $pdfContent = base64_decode(
                            $apiShipment->get('content')
                        );
                    }
                } else {
                    if ($apiShipment->get('type')
                        === Shipping::DOCUMENT_TYPE_URL
                    ) {
                        $pdfContent = file_get_contents(
                            $apiShipment->get('url')
                        );
                    }
                }

                // Return the pdf file if condition are met
                $filename = null;
                if ($pdfContent && $apiShipment->get('filename')) {
                    $pdf = $helper->preparePdfFileWithContent(
                        $pdfContent
                    );
                    $filename = $apiShipment->get('filename');
                    return $fileFactory->create(
                        $filename,
                        $pdf->render(),
                        DirectoryList::VAR_DIR,
                        'application/pdf'
                    );
                }
            } else {
                // Display error message
                $outputMessage = sprintf(
                    __('No shipment delivery report found in Odoo, for order %s'),
                    $localOrder->getIncrementId()
                );
                $this->messageManager->addErrorMessage($outputMessage);
            }
            // Get referer url
            $url = $this->_redirect->getRefererUrl();
            $this->_redirect($url);
        }
        // Print magento invoice
        return parent::execute();
    }
}