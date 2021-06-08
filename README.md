# Quick Setup

[![Build Status](https://travis-ci.org/bitpay/bitpay-checkout-magento2.svg?branch=master)](https://travis-ci.org/bitpay/bitpay-checkout-magento2)

This version requires the following

* A BitPay merchant account ([Test](http://test.bitpay.com) or [Production](http://www.bitpay.com))
* An API Token ([Test](https://test.bitpay.com/dashboard/merchant/api-tokens) or [Production](https://bitpay.com/dashboard/merchant/api-tokens)
	* When setting up your token, **uncheck** the *Require Authentication button*
* Magento 2.x

# Installation

1. Upload all files to your Magento installation root
2. Login to your server, and in the root of your Magento2 install, run the following commands:

```
php bin/magento setup:upgrade
php bin/magento module:enable Bitpay_BPCheckout
php bin/magento setup:static-content:deploy -f
```

* Flush your Magento2 Caches

```
php bin/magento cache:flush
```

You can now activate BitPay in the *Sales->Configuration->Sales->Payment Methods*




* **Title** - This will be the title that appears on the checkout page

* **Merchant Tokens**
	* A ***development*** or ***production*** token will need to be set
* **BitPay Server Endpoint**
	* Choose **Test** or **Production**, depending on your current setup.  Your matching API Token must be set.
* **Checkout Flow**
	* **Redirect** - This will send the user to the BitPay invoice screen, and they will be redirected after the transaction to the Order Completed page
	* **Modal** - This will open a popup modal on your site, and will display the order details once the transaction is completed.
* **Auto Capture Email** - If enabled, the plugin will attempt to auto-fill the buyer's email address when paying the invoice
* **Payment from Specific Countries**	 - You **MUST** select the countries to enable BitPay to appear in the checkout  

	

This plugin also includes an IPN (Instant Payment Notification) endpoint that will update your Magento 2 order status.

An order note will automatically be added with a link to the BitPay invoice to monitor the status

 * Initially your order will be in a **Pending** status when it is intially created, unless you choose a different configuration
 * After the invoice is paid by the user, it will change to a **Processing** status
 * When BitPay finalizes the transaction, it will change to a **Complete** status, and your order will be safe to ship, allow access to downloadable products, etc.
 * If you decide to refund a payment via your BitPay dashboard, the Magento 2 order status will change to **Closed** once the refund is executed.
