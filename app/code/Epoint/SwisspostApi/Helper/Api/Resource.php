<?php

namespace Epoint\SwisspostApi\Helper\Api;

use Epoint\SwisspostApi\Model\Api\Account;
use Epoint\SwisspostApi\Model\Api\Address;
use Epoint\SwisspostApi\Model\Api\SaleOrder;

interface Resource
{
    /**
     * @return mixed
     */
    public function sessionGetInfo();

    /**
     * @return mixed
     */
    public function sessionAuthenticate();

    /**
     * @param \Epoint\SwisspostApi\Model\Api\Order $object
     *
     * @return mixed
     */
    public function createSalesOrder(SaleOrder $object);

    /**
     * @param \Epoint\SwisspostApi\Model\Api\Account $object
     *
     * @return mixed
     */
    public function createUpdateAccount(Account $object);

    /**
     * Create or update an address in Odoo.
     * If an existing address with the supplied address_ref is found,
     * it will be updated with the fields supplied. Otherwise, a new address will be created.
     *
     * @param \Epoint\SwisspostApi\Helper\Api\Address $object
     *
     * @return result
     */
    public function createUpdateAddress(Address $object);

    /**
     * Returns information about all accounts.
     * "filters": "['name = ABC']",
     * "fields": "['account_last_name', 'account_first_name']",
     *
     * @param mixed
     *  Filter array
     *
     * @return result
     */
    public function searchReadAccount($filter);

    /**
     * It takes the following arguments in the following order.
     * All are required except offset, limit and order that have the defaults specified above.
     * "filters": "['address_city = Bern']",
     * "fields": "['address_street', 'address_city']",
     *
     * @param mixed
     *  Filter array
     *
     * @return result
     */
    public function searchReadAddress($filter);

    /**
     * Return information about all products.
     * The search can be filtered by search criteria and the returned information can be limited to a selection of fields.
     *
     * @param mixed
     * Filter dictionary
     *
     * @return result
     */
    public function getProducts($filters);

    /**
     * Return information about all product categories.
     * The search can be filtered by search criteria and the returned information can be limited to a selection of fields.
     *
     * @param mixed
     * Filter dictionary
     *
     * @return result
     */
    public function getProductCategories($filters);

    /**
     * Allows to check the credit status for one account and amount
     *
     * @param mixed
     * Filter dictionary
     *
     * @return result
     */
    public function checkCustomerCredit($filters);

    /**
     * Load inventory.
     *
     * @param mixed
     * Filter dictionary
     *
     * @return result
     */
    public function getInventory($filters);

    /**
     * Load product images.
     *
     * @param string $productRef
     *  The product reference
     *
     * @return $result
     */
    public function getImages($productRef);

    /**
     * Gets Order Invoice
     *
     * @param $orderRef
     * Order reference.
     *
     * @return mixed
     */
    public function getInvoice($orderRef);

    /**
     * Checking orders payment and invoice status
     *
     * @param array $orderIdsList
     * @param array $invoiceIdsList
     *
     * @return mixed
     */
    public function checkOrdersPaymentStatus($orderIdsList = [], $invoiceIdsList = []);

    /**
     * Checking orders transport status
     *
     * @param array $ordersIdsList
     *
     * @return mixed
     */
    public function checkOrdersTransferStatus($orderIdsList = []);

    /**
     * Update sale order with attached coupon
     *
     * @param $orderRef
     * @param $couponsData
     *
     * @return mixed
     */
    public function addSaleOrderCoupon($orderRef, $couponsData);

    /**
     * Gets shipment delivery report(s)
     *
     * @param $orderRef
     *
     * @return mixed
     */
    public function getDeliveryReports($orderRef);
}
