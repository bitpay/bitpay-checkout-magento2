<?php
/**
 * @license Copyright 2011-2014 BitPay Inc., MIT License
 * @see https://github.com/bitpay/magento-plugin/blob/master/LICENSE
 */

if ($mage = realpath(__DIR__.'/../build/magento/app/Mage.php')) {
    require_once $mage;
    Mage::app();
} else {
    exit('Could not find Mage.php');
}

if ($composer = realpath(__DIR__.'/../vendor/autoload.php')) {
    require_once $composer;
} else {
    exit('Composer not found');
}
