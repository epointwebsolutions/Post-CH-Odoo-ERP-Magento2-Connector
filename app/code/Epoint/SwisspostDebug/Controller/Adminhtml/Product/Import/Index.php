<?php

namespace Epoint\SwisspostDebug\Controller\Adminhtml\Product\Import;

use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Request\Http;
use Epoint\SwisspostApi\Model\Api\Product as ApiProductModel;
use Epoint\SwisspostCatalog\Service\Product as ServiceProduct;

class Index extends \Magento\Backend\App\Action
{
    /**
     * @var Http $_request
     */
    protected $request;

    /**
     * @var ApiProductModel
     */
    protected $apiProductModel;

    /**
     * @var ServiceProduct
     */
    protected $serviceProduct;

    /**
     * Index constructor.
     *
     * @param Context         $context
     * @param ApiProductModel $apiProductModel
     * @param ServiceProduct  $serviceProduct
     */
    public function __construct(
        Context $context,
        ApiProductModel $apiProductModel,
        ServiceProduct $serviceProduct
    ) {
        parent::__construct($context);
        $this->request = $this->getRequest();
        $this->apiProductModel = $apiProductModel;
        $this->serviceProduct = $serviceProduct;
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        // Retrieving the product sku
        $productSku = $this->request->getParam('sku');

        $products = [];
        if ($productSku) {
            $apiProduct = $this->apiProductModel->load($productSku);
            if ($apiProduct) {
                $products[] = $apiProduct;
            }
        }

        $outputMessage = '';
        if ($products) {
            $imported = $this->serviceProduct->run($products);
            /** @var \Magento\Catalog\Model\Product $product */
            foreach ($imported as $product) {
                $outputMessage
                    = sprintf(
                    __('Product with SKU -> %s has been imported!'),
                    $product->getSKU()
                );
                // Display success message
                if ($outputMessage !== '') {
                    $this->messageManager->addSuccessMessage($outputMessage);
                }
            }
        } else {
            $outputMessage
                = sprintf(
                __('An error occurred while importing the product with SKU - %s!'),
                $productSku
            );
            // Display error message
            $this->messageManager->addErrorMessage($outputMessage);
        }
        // Get referer url
        $url = $this->_redirect->getRefererUrl();
        $this->_redirect($url);
    }
}