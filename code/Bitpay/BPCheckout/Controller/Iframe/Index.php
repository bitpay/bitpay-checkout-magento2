<?php
/**
 * @license Copyright 2011-2014 BitPay Inc., MIT License
 * @see https://github.com/bitpay/magento-plugin/blob/master/LICENSE
 */

namespace Bitpay\Core\Controller\Iframe;

use Bitpay\Core\Helper\Data;
use Magento\Framework\App\Action\Context;

/**
 * @route bitpay/index/
 */
class Index extends \Magento\Framework\App\Action\Action
{

    /**
     * @var Data
     */
    protected $helper;

    /**
     * Index constructor.
     * @param Context $context
     * @param Data $helper
     */
    public function __construct(Context $context, Data $helper) {
        parent::__construct($context);

        $this->helper = $helper;
    }


    /**
     * @route bitpay/iframe/index
     */
    public function execute()
    {
        $html = '';

        if($this->helper->isFullScreen()){
            $html = 'You will be transferred to <a href="https://bitpay.com" target="_blank\">BitPay</a> to complete your purchase when using this payment method.';
        }
        
        $this->getResponse()->setBody(json_encode(array('html' => $html)));
    }

}
