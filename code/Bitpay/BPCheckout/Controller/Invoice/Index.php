<?php

/**
 * @license Copyright 2011-2014 BitPay Inc., MIT License
 * @see https://github.com/bitpay/magento-plugin/blob/master/LICENSE
 */
namespace Bitpay\Core\Controller\Invoice;

use Bitpay\Core\Helper\Data;
use Bitpay\Core\Model\Invoice;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;

/**
 * @route bitpay/invoice/
 */
class Index extends Action {

    /**
     * @var Data
     */
	protected $helper;

    /**
     * @var Invoice
     */
	protected $invoiceFactory;

    /**
     * @var Registry
     */
	protected $_coreRegistry;

    /**
     * @var PageFactory
     */
	protected $resultPageFactory;

    /**
     * Index constructor.
     * @param Context $context
     * @param Registry $coreRegistry
     * @param Data $helper
     * @param Invoice $invoiceFactory
     * @param PageFactory $resultPageFactory
     */
	public function __construct(Context $context, Registry $coreRegistry, Data $helper, Invoice $invoiceFactory, PageFactory $resultPageFactory) {
        parent::__construct($context);

		$this->_coreRegistry = $coreRegistry;
		$this->helper = $helper;
		$this->invoiceFactory = $invoiceFactory;
		$this->resultPageFactory = $resultPageFactory;

	}
	
	/**
	 * @route bitpay invoice url
	 */
	public function execute() {
		$objectManager = ObjectManager::getInstance();

		/* @var $quote \Magento\Checkout\Model\Session */
		$quote = $objectManager->get('\Magento\Checkout\Model\Session');
		$lastQuoteId = $quote->getData('last_success_quote_id');

		if (empty($lastQuoteId)) {
			return $this->_redirect('checkout/cart');
		}
		
		$this->_coreRegistry->register('last_success_quote_id', $lastQuoteId);
		
		if ($this->helper->isFullScreen()) {
			$invoice = $this->invoiceFactory->load($lastQuoteId, 'quote_id' );
			$resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
			$resultRedirect->setUrl( $invoice->getData('url') );

			return $resultRedirect;
		}
		else {
			$resultPage = $this->resultPageFactory->create();
			$resultPage->getConfig()->getTitle()->set( __ ('Pay with Bitcoin') );

			return $resultPage;
		}
	}
}
