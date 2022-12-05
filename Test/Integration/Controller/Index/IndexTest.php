<?php
declare(strict_types=1);

namespace Bitpay\BPCheckout\Test\Integration\Controller\Index;

use Bitpay\BPCheckout\Controller\Index\Index;
use Magento\TestFramework\TestCase\AbstractController;

class IndexTest extends AbstractController
{
    public function testExecute(): void
    {
        $this->dispatch('bitpay-invoice/index/index');
        $responseBody = $this->getResponse()->getBody();
        $statusCode = $this->getResponse()->getStatusCode();
        $this->assertEquals('200', $statusCode);
        $this->assertStringContainsString('<h2 id="bitpay-header">Thank you for your purchase.</h2>', $responseBody);
    }

    public function testExecuteNotFound(): void
    {
        $this->dispatch('bitpay-invoice/index/index1');
        $this->assert404NotFound();
    }
}
