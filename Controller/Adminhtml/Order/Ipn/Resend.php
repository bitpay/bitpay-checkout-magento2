<?php

declare(strict_types=1);

namespace Bitpay\BPCheckout\Controller\Adminhtml\Order\Ipn;

use Bitpay\BPCheckout\Model\Ipn\IpnNotificationSender;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Raw;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\Message\Manager;
use Magento\Framework\Message\ManagerInterface;
use Symfony\Component\HttpFoundation\Response;

class Resend implements HttpPostActionInterface
{
    private RequestInterface $request;
    private IpnNotificationSender $ipnNotificationSender;
    private ManagerInterface $messageManager;
    private RawFactory $rawFactory;

    public function __construct(
        RequestInterface $request,
        IpnNotificationSender $ipnNotificationSender,
        ManagerInterface $messageManager,
        RawFactory $rawFactory
    ) {
        $this->request = $request;
        $this->ipnNotificationSender = $ipnNotificationSender;
        $this->messageManager = $messageManager;
        $this->rawFactory = $rawFactory;
    }

    /**
     * @throws \BitPaySDK\Exceptions\BitPayException
     */
    public function execute(): Raw
    {
        $result = $this->rawFactory->create();

        try {
            $orderId = $this->request->getParam('order_id', null);
            if (!$orderId) {
                throw new \RuntimeException('Wrong order id');
            }

            $this->ipnNotificationSender->execute($orderId);

            $this->messageManager->addSuccessMessage('IPN Request sent successfully');
            $result->setHttpResponseCode(Response::HTTP_NO_CONTENT);
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage('Unable to resend IPN request');
            $result->setHttpResponseCode(Response::HTTP_BAD_REQUEST);
        }

        return $result;
    }
}
