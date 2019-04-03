# NOTICE
This is a Community-supported project.

If you are interested in becoming a maintainer of this project, please contact us at integrations@bitpay.com. Developers at BitPay will attempt to work along the new maintainers to ensure the project remains viable for the foreseeable future.

# Description

Bitcoin payment plugin for Magento using the bitpay.com service.

## Quick Start Guide

To get up and running with our plugin quickly, see the GUIDE here: https://github.com/bitpay/magento2-plugin/blob/master/GUIDE.md

## Support

**BitPay Support:**

* Last Cart Version Tested: 2.1.8
* [GitHub Issues](https://github.com/bitpay/magento2-plugin/issues)
  * Open an issue if you are having issues with this plugin.
* [Support](https://help.bitpay.com)
  * BitPay merchant support documentation

**Magento Support:**

* [Homepage](http://magento.com)
* [Documentation](http://docs.magentocommerce.com)
* [Community Edition Support Forums](https://www.magentocommerce.com/support/ce/)

## Troubleshooting

1. Ensure a valid SSL certificate is installed on your server. Also ensure your root CA cert is updated. If your CA cert is not current, you will see curl SSL verification errors.
2. Verify that your web server is not blocking POSTs from servers it may not recognize. Double check this on your firewall as well, if one is being used.
3. Check the `payment_bitpay.log` file for any errors during BitPay payment attempts. If you contact BitPay support, they will ask to see the log file to help diagnose the problem.  The log file will be found inside your Magento's `var/log/` directory. **NOTE:** You will need to enable the debugging setting for the extension to output information into the log file.
4. Check the version of this plugin against the official plugin repository to ensure you are using the latest version. Your issue might have been addressed in a newer version! See the [Releases](https://github.com/bitpay/magento2-plugin/releases) page or the Magento Marketplace for the latest version.
5. If all else fails, send an email describing your issue **in detail** to support@bitpay.com

**TIP:** When contacting support it will help us is you provide:

* Magento CE Version (Found at the bottom page in the Administration section)
* Other extensions you have installed
  * Some extensions do not play nice
* Configuration settings for the extension (Most merchants take screen grabs)
* Any log files that will help
  * web server error logs
  * enabled debugging for this extension and send us `var/log/payment_bitpay.log`
* Screen grabs of error message if applicable.


## Contribute

For developers wanting to contribute to this project, it is assumed you have a stable Magento environment to work with, and are familiar with developing for Magento. You will need to clone this repository or fork and clone the repository you created.

Once you have cloned the repository, you will need to run [composer install](https://getcomposer.org/doc/00-intro.md#using-composer). Using and setting up composer is outside the scope, however you will find the documentation on their site comprehensive.  You can then run the ``scripts/package`` script to create a distribution files which you can find in ``build/dist``. This is the file that you can upload to your server to unzip or do with what you will.

If you encounter any issues or implement any updates or changes, please open an [issue](https://github.com/bitpay/magento2-plugin/issues) or submit a Pull Request.

**NOTE:** The ``scripts/package`` file contains some configuration settings that will need to change for different releases. If you are using this script to build files that are for distribution, these will need to be updated.


## License

The MIT License (MIT)

Copyright (c) 2011-2015 BitPay, Inc.

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
