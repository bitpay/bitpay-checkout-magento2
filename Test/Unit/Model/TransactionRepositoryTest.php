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
        $this->transactionRepository->add('0000000122', '23', 'new');
    }

    public function testUpdate(): void
    {
        $this->transactionRepository->update('status', 'pending', ['order_id' => '0000000122']);
    }

    public function testFindBy(): void
    {
        $this->transactionRepository->findBy('000000222', '22');
    }
}
