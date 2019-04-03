<?php
/**
 * @license Copyright 2011-2014 BitPay Inc., MIT License
 * @see https://github.com/bitpay/magento-plugin/blob/master/LICENSE
 */

namespace Bitpay\Core\Block\Form;

use Bitpay\Core\Model\Method\Bitcoin;
use Magento\Customer\Helper\Session\CurrentCustomer;
use Magento\Framework\App\ObjectManager;
use Magento\Payment\Block\Form;

class Bitpay extends Form
{
    
	/**
     * Payment method code
     *
     * @var string
     */
    protected $_methodCode = Bitcoin::CODE;

    /**
     * @var CurrentCustomer
     */
    protected $currentCustomer;

    protected function _construct()
    {
        $template = 'Bitpay_Core::bitpay/form/bitpay.phtml';
        $this->setTemplate($template);

        parent::_construct();
    }

    /**
     * @return \Bitpay\Core\Helper\Data
     */
    public function getHelper() {
        return ObjectManager::getInstance()->get('Bitpay\Core\Helper\Data');
    }
    
}