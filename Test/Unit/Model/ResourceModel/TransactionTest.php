<?php
declare(strict_types=1);

namespace Bitpay\BPCheckout\Test\Unit\Model\ResourceModel;

use Bitpay\BPCheckout\Model\ResourceModel\Transaction;
use Magento\Framework\Model\ResourceModel\Db\Context;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Magento\Framework\DB\Select;
use PHPUnit\Framework\TestCase;

class TransactionTest extends TestCase
{
    /**
     * @var Transaction $transaction
     */
    private $transaction;

    /**
     * @var AdapterInterface|MockObject $adapter
     */
    private $adapter;

    public function setUp(): void
    {
        $this->prepareContext();
        $this->transaction = new Transaction($this->contex);
    }

    public function testAdd(): void
    {
        $incrementId = '000012121';
        $invoiceID = '12';
        $status = 'mew';

        $this->prepareTableName();
        $this->adapter->expects($this->once())->method('insertForce')->willReturn(1);

        $this->transaction->add($incrementId, $invoiceID, $status);
    }

    public function testFindBy(): void
    {
        $orderId = '12';
        $orderInvoiceId = '33';
        $select = $this->getMockBuilder(Select::class)->disableOriginalConstructor()->getMock();
        $this->prepareTableName();
        $this->adapter->expects($this->once())->method('select')->willReturn($select);
        $select->expects($this->any())->method('from')->with('bitpay_transactions')->willReturn($select);
        $select->expects($this->any())->method('where')->willReturn($select);
        $select->expects($this->any())->method('where')->willReturn($select);
        $this->adapter->expects($this->once())->method('fetchAll')
            ->with($select)
            ->willReturn(
                [
                    'order_id' => 000000003, 'transaction_id' => 'VjvZuvsWT6tzYX65ZXk4xq', 'transaction_status' => 'new'
                ]
            );

        $this->transaction->findBy($orderId, $orderInvoiceId);
    }

    public function testNotFound(): void
    {
        $orderInvoiceId = '11';
        $orderId = '33';
        $this->prepareTableName();
        $select = $this->getMockBuilder(Select::class)->disableOriginalConstructor()->getMock();
        $this->adapter->expects($this->once())->method('select')->willReturn($select);
        $select->expects($this->any())->method('from')->with('bitpay_transactions')->willReturn($select);
        $select->expects($this->any())->method('where')->willReturn($select);
        $select->expects($this->any())->method('where')->willReturn($select);
        $this->adapter->expects($this->once())->method('fetchAll')
            ->with($select)
            ->willReturn([]);

        $this->transaction->findBy($orderId, $orderInvoiceId);
    }

    public function testUpdate(): void
    {
        $field = 'status';
        $value = 'pending';
        $where = ['order_id = ?' => 12, 'transaction_id = ?' => 22,];
        $this->prepareTableName();

        $this->transaction->update($field, $value, $where);
    }

    private function prepareContext(): void
    {
        $resourceConnection = $this->getMockBuilder(ResourceConnection::class)->disableOriginalConstructor()->getMock();
        $this->adapter = $this->getMockBuilder(AdapterInterface::class)->disableOriginalConstructor()->getMock();
        $resourceConnection->expects($this->once())->method('getConnection')->willReturn($this->adapter);
        $this->contex = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();
        $this->contex->expects($this->once())->method('getResources')->willReturn($resourceConnection);
    }

    private function prepareTableName(): void
    {
        $this->adapter->expects($this->once())
            ->method('getTableName')
            ->with('bitpay_transactions')
            ->willReturn('bitpay_transactions');
    }
}
