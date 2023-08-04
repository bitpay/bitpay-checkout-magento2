<?php

declare(strict_types=1);

namespace Bitpay\BPCheckout\Test\Unit\Controller\Adminhtml\Order\Ipn;

use Bitpay\BPCheckout\Controller\Adminhtml\Order\Ipn\Resend;
use Bitpay\BPCheckout\Model\Ipn\IpnNotificationSender;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\Message\ManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class ResendTest extends TestCase
{
    public function testCorrectSendIpnNotification(): void
    {
        $orderId = '1';
        $request = $this->createMock(RequestInterface::class);
        $ipnNotificationSender = $this->createMock(IpnNotificationSender::class);
        $messageManager = $this->createMock(ManagerInterface::class);
        $raw = $this->getRaw();
        $request->method('getParam')->willReturn($orderId);
        $class = $this->getTestedClass($request, $ipnNotificationSender, $messageManager, $raw);

        $ipnNotificationSender->expects(self::once())->method('execute')->with($orderId);
        $messageManager->expects(self::once())->method('addSuccessMessage');
        $raw->expects(self::once())->method('setHttpResponseCode')->with(Response::HTTP_NO_CONTENT);

        $class->execute();
    }

    public function testIncorrectSendIpnNotification(): void
    {
        $request = $this->createMock(RequestInterface::class);
        $ipnNotificationSender = $this->createMock(IpnNotificationSender::class);
        $messageManager = $this->createMock(ManagerInterface::class);
        $raw = $this->getRaw();
        $request->method('getParam')->willReturn(null);
        $class = $this->getTestedClass($request, $ipnNotificationSender, $messageManager, $raw);

        $ipnNotificationSender->expects(self::never())->method('execute');
        $messageManager->expects(self::once())->method('addErrorMessage');
        $raw->expects(self::once())->method('setHttpResponseCode')->with(Response::HTTP_BAD_REQUEST);

        $class->execute();
    }

    private function getTestedClass(
        RequestInterface $request,
        IpnNotificationSender $ipnNotificationSender,
        ManagerInterface $messageManager,
        \Magento\Framework\Controller\Result\Raw $raw
    ): Resend {
        $rawFactory = $this->createMock(RawFactory::class);
        $rawFactory->method('create')->willReturn($raw);

        return new Resend($request, $ipnNotificationSender, $messageManager, $rawFactory);
    }

    /**
     * @return \Magento\Framework\Controller\Result\Raw|\PHPUnit\Framework\MockObject\MockObject
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    private function getRaw(): \Magento\Framework\Controller\Result\Raw|\PHPUnit\Framework\MockObject\MockObject
    {
        return $this->createMock(\Magento\Framework\Controller\Result\Raw::class);
    }
}
