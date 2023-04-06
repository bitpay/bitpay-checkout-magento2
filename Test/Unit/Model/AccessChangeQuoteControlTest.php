<?php
declare(strict_types=1);

namespace Bitpay\BPCheckout\Test\Unit\Model;

use Bitpay\BPCheckout\Model\AccessChangeQuoteControl;
use Magento\Quote\Api\ChangeQuoteControlInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AccessChangeQuoteControlTest extends TestCase
{
    /**
     * @var ChangeQuoteControlInterface|MockObject $quoteAccessChangeControl
     */
    private $quoteAccessChangeControl;

    /**
     * @var AccessChangeQuoteControl
     */
    private $accessChangeQuoteControl;

    public function setUp(): void
    {
        $this->quoteAccessChangeControl = $this->getMockBuilder(ChangeQuoteControlInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->accessChangeQuoteControl = new AccessChangeQuoteControl($this->quoteAccessChangeControl);
    }

    public function testBeforeSave(): void
    {
        $cart = $this->getMockBuilder(CartInterface::class)->disableOriginalConstructor()->getMock();
        $cartRepository = $this->getMockBuilder(CartRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->accessChangeQuoteControl->beforeSave($cartRepository, $cart);
    }
}
