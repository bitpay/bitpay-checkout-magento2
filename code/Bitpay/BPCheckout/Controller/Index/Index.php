<?php
/**
 * @license Copyright 2011-2014 BitPay Inc., MIT License
 * @see https://github.com/bitpay/magento-plugin/blob/master/LICENSE
 */

namespace Bitpay\Core\Controller\Index;

use Bitpay\Core\Helper\Data;
use Bitpay\Core\Model\Ipn;
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
     * @var Ipn
     */
    protected $ipn;

    /**
     * Index constructor.
     * @param Context $context
     * @param Data $helper
     * @param Ipn $ipn
     */
    public function __construct(Context $context, Data $helper, Ipn $ipn) {
        parent::__construct($context);

        $this->helper = $helper;
        $this->ipn = $ipn;
    }

    /**
     * @route bitpay/index/index?quote=n
     */
    public function execute()
    {
        $params  = $this->getRequest()->getParams();
        $quoteId = $params['quote'];

        $this->helper->logInfo(json_encode($params));
        $paid = $this->ipn->GetQuotePaid($quoteId);

        $this->_view->loadLayout();

        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody(json_encode(array('paid' => $paid)));
    }
}
