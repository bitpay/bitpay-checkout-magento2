<?php
declare(strict_types=1);

namespace Bitpay\BPCheckout\Test\Unit\Controller\Index;

use Bitpay\BPCheckout\Controller\Index\Index;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\View\Result\Page;
use PHPUnit\Framework\TestCase;

class IndexTest extends TestCase
{
    /**
     * @var Context|\PHPUnit\Framework\MockObject\MockObject
     */
    private $context;

    /**
     * @var PageFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $pageFactory;

    /**
     * @var Index $index
     */
    private $index;

    public function setUp(): void
    {
        $this->context = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();
        $this->pageFactory = $this->getMockBuilder(PageFactory::class)->disableOriginalConstructor()->getMock();
        $this->index = new Index(
            $this->context,
            $this->pageFactory
        );
    }

    public function testExecute(): void
    {
        $page = $this->getMockBuilder(Page::class)->disableOriginalConstructor()->getMock();
        $this->pageFactory->expects($this->once())->method('create')->willReturn($page);

        $this->assertInstanceOf(Page::class, $this->index->execute());
    }
}
