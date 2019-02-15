# Quick Setup

This version requires the following

* A BitPay merchant account ([Test](http://test.bitpay.com) or [Production](http://www.bitpay.com))
* An API Token ([Test](https://test.bitpay.com/dashboard/merchant/api-tokens) or [Production](https://bitpay.com/dashboard/merchant/api-tokens)
	* When setting up your token, **uncheck** the *Require Authentication button*
* Magento 1.9.x

# Installation

1. Upload all files to your Magento installation root
2. Login to your server, and in the root of your Magento2 install, run the following commands:

```
php bin/magento setup:upgrade
php bin/magento module:enable BitpayCheckout_BPCheckout
php bin/magento setup:static-content:deploy -f
```

* Flush your Magento2 Caches




You can now activate the BitPay Checkout in the *Sales->Configuration->Sales->Payment Methods*




* **Title** - This will be the title that appears on the checkout page

* **Merchant Tokens**
	* A ***development*** or ***production*** token will need to be set
* **BitPay Server Endpoint**
	* Choose **Test** or **Production**, depending on your current setup.  Your matching API Token must be set.
* **Accepted Cryptocurrencies** - You can choose ***BitCoin***, ***BitCoin Cash***, or ***All***
* **Checkout Flow**
	* **Redirect** - This will send the user to the BitPay invoice screen, and they will be redirected after the transaction to the Order Completed page
	* **Modal** - This will open a popup modal on your site, and will display the order details once the transaction is completed.
* **Auto Capture Email** - If enabled, the plugin will attempt to auto-fill the buyer's email address when paying the invoice

	

This plugin also includes an IPN endpoint that will update  your Magento order status.

An order note will automatically be added with a link to the invoice to monitor the status.

* Initially your order will be in a **Pending Payment** status when it is intially created
* After the invoice is paid by the user, it will change to a **Processing** status
* When BitPay finalizes the transaction, it will change to a **Completed** status, and your order will be safe to ship, allow access to downloadable products, etc.