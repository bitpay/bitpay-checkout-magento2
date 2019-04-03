<?php
/**
 * @license Copyright 2011-2014 BitPay Inc., MIT License
 * @see https://github.com/bitpay/magento-plugin/blob/master/LICENSE
 */

class Bitpay_Core_Helper_DataTest extends PHPUnit_Framework_TestCase
{
    protected static $faker;

    public static function setUpBeforeClass()
    {
        self::$faker = Faker\Factory::create();
    }

    public function testGetLogFile()
    {
        $this->assertSame(
            'payment_bitpay.log',
            Mage::helper('bitpay')->getLogFile()
        );
    }

    public function testDebugData()
    {
        Mage::helper('bitpay')->debugData('Testing');
    }

    public function testIsDebugMode()
    {
        Mage::app()->getStore()->setConfig('payment/bitpay/debug', null);
        $this->assertFalse(Mage::helper('bitpay')->isDebug());

        Mage::app()->getStore()->setConfig('payment/bitpay/debug', false);
        $this->assertFalse(Mage::helper('bitpay')->isDebug());

        Mage::app()->getStore()->setConfig('payment/bitpay/debug', true);
        $this->assertTrue(Mage::helper('bitpay')->isDebug());
    }

    public function testHasTransactionSpeedFalse()
    {
        Mage::app()->getStore()->setConfig('payment/bitpay/speed', null);

        $this->assertFalse(Mage::helper('bitpay')->hasTransactionSpeed());
    }

    public function testHasTransactionSpeedTrue()
    {
        Mage::app()->getStore()->setConfig('payment/bitpay/speed', 'low');

        $this->assertTrue(Mage::helper('bitpay')->hasTransactionSpeed());
    }

    /**
     * Location where BitPay IPNs will go
     */
    public function testGetNotificationUrl()
    {
        $this->assertSame(
            'http://www.localhost.com/bitpay/ipn/',
            Mage::helper('bitpay')->getNotificationUrl()
        );
    }

    public function testGetRedirectUrl()
    {
        $this->assertSame(
            'http://www.localhost.com/checkout/onepage/success/',
            Mage::helper('bitpay')->getRedirectUrl()
        );
    }

    public function testRegisterAutoloader()
    {
        Mage::helper('bitpay')->registerAutoloader();
    }

    public function testGenerateAndSaveKeys()
    {
        Mage::helper('bitpay')->generateAndSaveKeys();
    }

    public function testGetSinKey()
    {
        Mage::helper('bitpay')->getSinKey();
    }

    private function createInvalidIpn()
    {
        $ipn = new Bitpay_Core_Model_Ipn();
        $ipn->setData(
            array(
                'quote_id'        => '',
                'order_id'        => '',
                'invoice_id'      => '',
                'url'             => '',
                'pos_data'        => '',
                'status'          => '',
                'btc_price'       => '',
                'price'           => '',
                'currency'        => '',
                'invoice_time'    => '',
                'expiration_time' => '',
                'current_time'    => '',
            )
        );
        $ipn->save();
        $ipn->load($ipn->getId());

        return $ipn;
    }

    private function createExpiredIpn()
    {
        $order = $this->createOrder();
        $ipn   = new Bitpay_Core_Model_Ipn();
        $ipn->setData(
            array(
                'quote_id'        => '',
                'order_id'        => $order->getIncrementId(),
                'invoice_id'      => '',
                'url'             => '',
                'pos_data'        => '',
                'status'          => '',
                'btc_price'       => '',
                'price'           => '',
                'currency'        => '',
                'invoice_time'    => '',
                'expiration_time' => '',
                'current_time'    => '',
            )
        );
        $ipn->save();
        $ipn->load($ipn->getId());

        return $ipn;
    }

    private function createOrder()
    {
        $product = $this->createProduct();
        $quote   = $this->createQuote();
        $quote->addProduct(
            $product,
            new Varien_Object(
                array(
                    'qty' => 1,
                )
            )
        );
        $address = array(
            'firstname'            => self::$faker->firstName,
            'lastname'             => self::$faker->lastName,
            'company'              => self::$faker->company,
            'email'                => self::$faker->email,
            'city'                 => self::$faker->city,
            'region_id'            => '',
            'region'               => 'State/Province',
            'postcode'             => self::$faker->postcode,
            'telephone'            => self::$faker->phoneNumber,
            'country_id'           => self::$faker->state,
            'customer_password'    => '',
            'confirm_password'     => '',
            'save_in_address_book' => 0,
            'use_for_shipping'     => 1,
            'street'               => array(
                self::$faker->streetAddress,
            ),
        );

        $quote->getBillingAddress()
            ->addData($address);

        $quote->getShippingAddress()
            ->addData($address)
            ->setShippingMethod('flatrate_flatrate')
            ->setPaymentMethod('checkmo')
            ->setCollectShippingRates(true)
            ->collectTotals();

        $quote
            ->setCheckoutMethod('guest')
            ->setCustomerId(null)
            ->setCustomerEmail($address['email'])
            ->setCustomerIsGuest(true)
            ->setCustomerGroupId(Mage_Customer_Model_Group::NOT_LOGGED_IN_ID);

        $quote->getPayment()
            ->importData(array('method' => 'checkmo'));

        $quote->save();

        $service = Mage::getModel('sales/service_quote', $quote);
        $service->submitAll();
        $order = $service->getOrder();

        $order->save();
        $order->load($order->getId());

        return $order;
    }

    private function createProduct()
    {
        $product = Mage::getModel('catalog/product');

        $product->addData(
            array(
                'attribute_set_id'  => 1,
                'website_ids'       => array(1),
                'categories'        => array(),
                'type_id'           => Mage_Catalog_Model_Product_Type::TYPE_SIMPLE,
                'sku'               => self::$faker->randomNumber,
                'name'              => self::$faker->name,
                'weight'            => self::$faker->randomDigit,
                'status'            => Mage_Catalog_Model_Product_Status::STATUS_ENABLED,
                'tax_class_id'      => 2,
                'visibility'        => Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH,
                'price'             => self::$faker->randomFloat(2),
                'description'       => self::$faker->paragraphs,
                'short_description' => self::$faker->sentence,
                'stock_data'        => array(
                    'is_in_stock' => 1,
                    'qty'         => 100,
                ),
            )
        );

        $product->save();
        $product->load($product->getId());

        return $product;
    }

    private function createQuote()
    {
        return Mage::getModel('sales/quote')
            ->setStoreId(Mage::app()->getStore('default')->getId());
    }
}
