# Quick Setup

[![Build Status](https://travis-ci.org/bitpay/bitpay-checkout-magento2.svg?branch=master)](https://travis-ci.org/bitpay/bitpay-checkout-magento2)

This version requires the following

* A BitPay merchant account ([Test](http://test.bitpay.com) or [Production](http://www.bitpay.com))
* An API Token ([Test](https://test.bitpay.com/dashboard/merchant/api-tokens) or [Production](https://bitpay.com/dashboard/merchant/api-tokens)
	* When setting up your token, **uncheck** the *Require Authentication button*
* Magento 2.x

# Installation

This module is now installable via Composer using the following command:

```
composer require bitpay/module-bpcheckout
```

After installing via Composer, run the following commands:

```
php bin/magento setup:upgrade
php bin/magento module:enable Bitpay_BPCheckout
php bin/magento setup:static-content:deploy -f
```

* Flush your Magento2 Caches

```
php bin/magento cache:flush
```

You can now activate BitPay in the *Stores->Configuration->Sales->Payment Methods*



* **Enabled** - Status for payment method
* * **Send emails for BitPay Orders** - Allows an Admin to suppress Order emails for BitPay Orders. Default to false
* **Title** - This will be the title that appears on the checkout page
* **Environment**
	* Choose **Test** or **Production**, depending on your current setup
* **Status mapping - BitPay invoice / Magento order** - Map the BitPay “confirmed” invoice status to the preferred Magento order status, based on the transaction speed you have selected in your BitPay <a target="_blank" href="https://bitpay.com/dashboard/settings/edit/order">dashboard</a>
* **Status mapping - BitPay invoice / Magento order on BitPay Refunds** - If set to TRUE, Magento will set the Order State to Closed.  If set to FALSE, no changes will be made to the Magento order
* **Status mapping - BitPay invoice / Magento order on BitPay Canceled** - If set to TRUE, Magento will set the Order State to Canceled after the order has expired.  If set to FALSE, no changes will be made to the Magento order
* **Checkout Flow**
	* **Redirect** - This will send the user to the BitPay invoice screen, and they will be redirected after the transaction to the Order Completed page
	* **Modal** - This will open a popup modal on your site, and will display the order details once the transaction is completed.
* **New Order Status** - Select status for new order
* **Payment from Specific Countries**	 - You **MUST** select the countries to enable BitPay to appear in the checkout  
 
<h3>Merchant Token</h3>
To generate merchant token visit *Admin->Stores->Configuration->Bitpay->Merchant Facade->Authenticate.*
You need to specify following data:
* Token Label
* Password (Used to decrypt your private key)
* Full path to private key (e.g /app/secure/private2.key)

Note: Each time before creating token please save private key path and password
When you hit Create token button you will get pairing code that you use in <a href="https://test.bitpay.com/dashboard/merchant/api-tokens">Bitpay Token</a>

<h3>Refund</h3>
* Refund setting are located in *Admin->Stores->Configuration->Bitpay->Merchant Facade->Refund.*
There are following options to set:
	* Preview Mode
    * Immediate Refund
    * Buyer Pays Refund Fee
    * Suppress Order Emails

	All options by default are set to false.
* Refund request is send when user attempts to create a Credit Memo against a BitPay Order. Refund amount is configured in the credit meno


This plugin also includes an IPN (Instant Payment Notification) endpoint that will update your Magento 2 order status.

An order note will automatically be added with a link to the BitPay invoice to monitor the status

 * Initially your order will be in a **Pending** status when it is intially created, unless you choose a different configuration
 * After the invoice is paid by the user, it will change to a **Processing** status
 * When BitPay finalizes the transaction, it will change to a **Complete** status, and your order will be safe to ship, allow access to downloadable products, etc.
 * If you decide to refund a payment via your BitPay dashboard, the Magento 2 order status will change to **Closed** once the refund is executed.
