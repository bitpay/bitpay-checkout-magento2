<?php

#host/cartfix/cartfix?order_id = order_id
namespace BitpayCheckout\BPCheckout\Controller\Cartfix;

class Index extends \Magento\Framework\App\Action\Action
{

    protected $resultPageFactory;

    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Action\Context  $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory
        #\Magento\Framework\App\Response\RedirectInterface $redirect
    ) {
        $this->resultPageFactory = $resultPageFactory;
        parent:: __construct($context);
    }

    /**
     * Execute view action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
       
        $order_id       = $this->getRequest()->getParam('order_id');
        $_objectManager = \Magento\Framework\App\ObjectManager::getInstance();
 
        $order = $this->_objectManager->create('Magento\Sales\Model\Order');
 
        $order ->load($order_id);
        $checkoutSession = $_objectManager->get('Magento\Checkout\Model\Session');

        $cart = $_objectManager->get('Magento\Checkout\Model\Cart');

        $items = $order->getItemsCollection();
        foreach($items as $item): 
            try {
               
                $options = $item->getProductOptions();
                $product = $item->getProduct();
                if (isset($options['info_buyRequest'])) {
                    #update the quantity
                    $options['info_buyRequest']['qty'] = $item['qty_ordered'];
                
                $cart->addProduct($product, $options['info_buyRequest']);
                
                }else{
                    $cart->addOrderItem($product);
                }
                }
            
            catch (\Exception $e) {
            }
        endforeach;
        $cart->save();
        $registry = $_objectManager->get('Magento\Framework\Registry');

        $registry->register('isSecureArea','true');
        $order->delete();
        $registry->unregister('isSecureArea'); 
        header('Location: /checkout/cart/');

        die();
        //endforeach
    }
}
