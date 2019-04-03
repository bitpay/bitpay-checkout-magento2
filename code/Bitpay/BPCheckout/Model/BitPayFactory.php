<?php

namespace Bitpay\Core\Model;

use Bitpay\Buyer;
use Bitpay\Currency;
use Bitpay\Invoice;
use Bitpay\Item;
use Magento\Framework\App\ObjectManager;
use Bitpay\Core\Helper\Data;
use Magento\Sales\Model\Order;

class BitPayFactory {

    /**
     * @var Data
     */
    protected $dataHelper;

    /**
     * BitPayFactory constructor.
     * @param Data $dataHelper
     */
    public function __construct(Data $dataHelper) {
        $this->dataHelper = $dataHelper;
    }

    /**
     * Returns invoice based on the given order.
     *
     * @param Order $order
     * @return Invoice
     */
    public function createInvoiceFromOrder(Order $order) {
        $invoice = new Invoice();

        $invoice->setFullNotifications(true);
        $invoice->setTransactionSpeed($this->dataHelper->getTransactionSpeed());
        $invoice->setNotificationUrl($this->dataHelper->getNotificationUrl());
        $invoice->setRedirectUrl($this->dataHelper->getRedirectUrl());

        $invoice->setBuyer($this->createBuyerFromOrder($order));
        $invoice->setCurrency($this->createCurrencyFromOrder($order));
        $invoice->setItem($this->createInvoiceItemFromOrder($order));

        $invoice->setOrderId($order->getIncrementId());
        $invoice->setPosData(json_encode(array('orderId' => $order->getIncrementId())));

        return $invoice;
    }

    /**
     * Returns buyer based on the given order.
     *
     * @param Order $order
     * @return Buyer
     */
    public function createBuyerFromOrder(Order $order) {
        $buyer = new Buyer();

        $buyer->setFirstName($order->getCustomerFirstname());
        $buyer->setLastName($order->getCustomerLastname());

        if($this->dataHelper->isFullScreen()) {
            $address = $order->getBillingAddress();
        } else {
            /* @var $session \Magento\Checkout\Model\Session */
            $session = ObjectManager::getInstance()->get('\Magento\Checkout\Model\Session');
            $address = $session->getQuote()->getBillingAddress();
        }

        if($street = $address->getStreet()) {
            $buyer->setAddress($street);
        }

        if($regionCode = $address->getRegionCode()) {
            $buyer->setState($regionCode);
        }
        else if($region = $address->getRegion()) {
            $buyer->setState($region);
        }

        if($country = $address->getCountry()) {
            $buyer->setCountry($country);
        }

        if($city = $address->getCity()) {
            $buyer->setCity($city);
        }

        if($postcode = $address->getPostcode()) {
            $buyer->setZip($postcode);
        }

        if($email = $address->getEmail()) {
            $buyer->setEmail($email);
        }

        if ($telephone = $address->getTelephone()) {
            $buyer->setPhone($telephone);
        }

        return $buyer;
    }

    /**
     * Returns currency based on the given order.
     *
     * @param Order $order
     * @return Currency
     */
    public function createCurrencyFromOrder(Order $order) {
        $currency = new Currency();

        $currency->setCode($order->getOrderCurrencyCode());

        return $currency;
    }

    /**
     * Returns invoice item based on the given order.
     *
     * @param Order $order
     * @return Item
     */
    public function createInvoiceItemFromOrder(Order $order) {
        $item = new Item();

        $item->setPrice($order->getGrandTotal());

        return $item;
    }

}