<?php

namespace Bitpay\BPCheckout\Test\Integration\Model\ResourceModel;

use Bitpay\BPCheckout\Model\ResourceModel\Transaction;
use Magento\Framework\Model\ResourceModel\Db\Context;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

class TransactionTest extends TestCase
{
    /**
     * @var ObjectManagerInterface $objectManager
     */
    private $objectManager;

    /**
     * @var Transaction $transactionResource
     */
    private $transactionResource;

    public function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->context = $this->objectManager->get(Context::class);
        $this->transactionResource = new Transaction(
            $this->context
        );
    }

    public function testAdd(): void
    {
        $this->assertEquals(null, $this->transactionResource->add('0000000001222', '22', 'new'));
    }

    /**
     * @magentoDataFixture Bitpay_BPCheckout::Test/Integration/_files/transaction.php
     */
    public function testFindBy(): void
    {
        $result = $this->transactionResource->findBy('100000001', 'VjvZuvsWT36tzYX65ZXk4xq');
        $this->assertEquals('100000001', $result[0]['order_id']);
        $this->assertEquals('new', $result[0]['transaction_status']);
        $this->assertEquals('VjvZuvsWT36tzYX65ZXk4xq', $result[0]['transaction_id']);
    }

    /**
     * @magentoDataFixture Bitpay_BPCheckout::Test/Integration/_files/transaction.php
     */
    public function testUpdate(): void
    {
        $transactionStatus = 'pending';
        $this->transactionResource->update('transaction_status', $transactionStatus, ['id' => '100000001']);
        $result = $this->transactionResource->findBy('100000001', 'VjvZuvsWT36tzYX65ZXk4xq');
        $this->assertEquals($transactionStatus, $result[0]['transaction_status']);
    }
}
