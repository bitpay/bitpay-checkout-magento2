<?php

/**
 * @license Copyright 2011-2014 BitPay Inc., MIT License
 * @see https://github.com/bitpay/magento-plugin/blob/master/LICENSE
 * 
 * TODO: Finish this iFrame implemenation... :/
 */

namespace Bitpay\Core\Block;

use Bitpay\Core\Helper\Data;
use Bitpay\Core\Model\Invoice;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

class Iframe extends Template {

    /**
     * @var Invoice
     */
	protected $invoiceFactory;
	/**
	 *
	 * @var Data
	 */
	protected $helper;

    /**
     * @var Registry
     */
	protected $coreRegistry;

    /**
     * Iframe constructor.
     * @param Context $context
     * @param Registry $coreRegistry
     * @param Invoice $invoiceFactory
     * @param Data $helper
     * @param array $data
     */
	public function __construct(Context $context, Registry $coreRegistry, Invoice $invoiceFactory, Data $helper, array $data = []) {
        parent::__construct ($context, $data);

		$this->invoiceFactory = $invoiceFactory;
		$this->helper = $helper;
		$this->coreRegistry = $coreRegistry;
	}

    /**
     * @return Data
     */
	protected function getHelper() {
		return $this->helper;
	}
	
	/**
	 * create an invoice and return the url so that iframe.phtml can display it
	 *
	 * @return string
	 */
	public function getFrameActionUrl() {
		$invoice = $this->invoiceFactory->load($this->getLastQuoteId(), 'quote_id');

		return $invoice->getData('url') . '&view=model&v=2';
	}

    /**
     * @return int|string
     */
	public function getLastQuoteId() {
		return $this->coreRegistry->registry('last_success_quote_id');
	}

    /**
     * @return string
     */
	public function getValidateUrl() {
		return $this->getUrl('bitpay/index/index');
	}

    /**
     * @return string
     */
	public function getSuccessUrl() {
		return $this->getUrl('checkout/onepage/success');
	}

    /**
     * @return string
     */
    public function getCartUrl() {
		return $this->getUrl('checkout/cart/index');
	}

    /**
     * @return bool
     */
	public function isTestMode() {
		return $this->helper->isTestnetNetwork();
	}
	
}
