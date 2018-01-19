<?php

namespace Epoint\SwisspostSales\Controller\Adminhtml\Order\Invoice;

use Epoint\SwisspostSales\Helper\Invoice;
use Magento\Framework\App\Filesystem\DirectoryList;

class PrintAction
    extends \Magento\Sales\Controller\Adminhtml\Order\Invoice\PrintAction
{

    public function execute()
    {
        // Getting helper
        $helper = $this->_objectManager->get(
            \Epoint\SwisspostSales\Helper\Invoice::class
        );

        if ($helper->isPrintPdfEnabled()) {
            // File factory
            $fileFactory = $this->_objectManager->get(
                \Magento\Framework\App\Response\Http\FileFactory::class
            );

            // Get invoice id
            $localInvoiceId = $this->getRequest()->getParam('invoice_id');
            $localInvoice = $this->_objectManager->create(
                \Magento\Sales\Api\InvoiceRepositoryInterface::class
            )->get($localInvoiceId);
            $localOrder = $localInvoice->getOrder();

            // Instatiate invoice model
            $apiInvoice = $this->_objectManager->get(
                \Epoint\SwisspostApi\Model\Api\Invoice::class
            )->getInstance($localOrder);

            // Get invoice
            $result = $apiInvoice->load();
            $pdfContent = '';
            if ($result->isOK()) {
                // Checking to see what type it is: Binary or URL
                if ($apiInvoice->get('type') === Invoice::DOCUMENT_TYPE_BINARY) {
                    if ($apiInvoice->get('filename')
                        && $apiInvoice->get(
                            'content'
                        )
                    ) {
                        $pdfContent = base64_decode(
                            $apiInvoice->get('content')
                        );
                    }
                } else {
                    if ($apiInvoice->get('type')
                        === Invoice::DOCUMENT_TYPE_URL
                    ) {
                        $pdfContent = file_get_contents(
                            $apiInvoice->get('url')
                        );
                    }
                }

                // Return the pdf file if condition are met
                $filename = null;
                if ($pdfContent && $apiInvoice->get('filename')) {
                    $pdf = $helper->preparePdfFileWithContent(
                        $pdfContent
                    );
                    $filename = $apiInvoice->get('filename');
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
                    __('No invoice found in odoo, for order %s'),
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