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
    /**
     * @var SuccessPlugin $successPlugin
     */
    private $successPlugin;

    /**
     * @var BPRedirect|MockObject $bpRedirect
     */
    private $bpRedirect;

    public function setUp(): void
    {
        $this->bpRedirect = $this->getMockBuilder(BPRedirect::class)->disableOriginalConstructor()->getMock();
        $this->successPlugin = new SuccessPlugin($this->bpRedirect);
    }

    public function testAfterExecute(): void
    {
        $subject = $this->getMockBuilder(Success::class)->disableOriginalConstructor()->getMock();
        $result = $this->getMockBuilder(ResultInterface::class)->disableOriginalConstructor()->getMock();

        $this->successPlugin->afterExecute($subject, $result);
    }
}
