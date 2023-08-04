<?php
declare(strict_types=1);

namespace Bitpay\BPCheckout\Test\Unit\Model;

use Bitpay\BPCheckout\Model\TransactionRepository;
use PHPUnit\Framework\MockObject\MockObject;
use Bitpay\BPCheckout\Model\ResourceModel\Transaction as TransactionResource;
use PHPUnit\Framework\TestCase;

class TransactionRepositoryTest extends TestCase
{
    /**
     * @var TransactionRepository $transactionRepository
     */
    private $transactionRepository;

    /**
     * @var TransactionResource|MockObject $transactionResource
     */
    private $transactionResource;

    public function setUp(): void
    {
        $this->transactionResource = $this->getMockBuilder(TransactionResource::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->transactionRepository = new TransactionRepository(
            $this->transactionResource
        );
    }

    public function testAdd(): void
    {
        $incrementId = '0000000122';
        $invoiceId = '23';
        $status = 'new';

        $this->transactionResource->expects(self::once())
            ->method('add')->with($incrementId, $invoiceId, $status);

        $this->transactionRepository->add($incrementId, $invoiceId, $status);
    }

    public function testUpdate(): void
    {
        $field = 'status';
        $value = 'pending';
        $where = ['order_id' => '0000000122'];

        $this->transactionResource->expects(self::once())
            ->method('update')->with($field, $value, $where);

        $this->transactionRepository->update($field, $value, $where);
    }

    public function testFindBy(): void
    {
        $orderId = '000000222';
        $orderInvoiceId = '22';

        $this->transactionResource->expects(self::once())
            ->method('findBy')->with($orderId, $orderInvoiceId);

        $this->transactionRepository->findBy($orderId, $orderInvoiceId);
    }
}
