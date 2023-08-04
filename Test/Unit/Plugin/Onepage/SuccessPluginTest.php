<?php

namespace Bitpay\BPCheckout\Test\Unit\Plugin\Onepage;

use Bitpay\BPCheckout\Plugin\Onepage\SuccessPlugin;
use Bitpay\BPCheckout\Model\BPRedirect;
use Magento\Checkout\Controller\Onepage\Success;
use Magento\Framework\Controller\ResultInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SuccessPluginTest extends TestCase
{
    public function testAfterExecute(): void
    {
        $bpRedirect = $this->getMockBuilder(BPRedirect::class)->disableOriginalConstructor()->getMock();
        $subject = $this->getMockBuilder(Success::class)->disableOriginalConstructor()->getMock();
        $result = $this->getMockBuilder(ResultInterface::class)->disableOriginalConstructor()->getMock();
        $testedClass = new SuccessPlugin($bpRedirect);

        $bpRedirect->expects(self::once())->method('execute');

        $testedClass->afterExecute($subject, $result);
    }
}
