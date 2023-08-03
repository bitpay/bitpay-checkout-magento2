<?php

declare(strict_types=1);

namespace Bitpay\BPCheckout\Model\Ipn;

use Bitpay\BPCheckout\Model\BitpayInvoiceRepository;
use Bitpay\BPCheckout\Model\Client as ClientFactory;

class IpnNotificationSender
{
    private ClientFactory $clientFactory;
    private BitpayInvoiceRepository $bitpayInvoiceRepository;

    public function __construct(ClientFactory $clientFactory, BitpayInvoiceRepository $bitpayInvoiceRepository)
    {
        $this->clientFactory = $clientFactory;
        $this->bitpayInvoiceRepository = $bitpayInvoiceRepository;
    }

    /**
     * @throws \BitPaySDK\Exceptions\BitPayException
     */
    public function execute(string $orderId): void
    {
        $client = $this->clientFactory->initialize();
        $invoiceData = $this->bitpayInvoiceRepository->getByOrderId($orderId);
        if (!$invoiceData || !isset($invoiceData['invoice_id'])) {
            throw new \RuntimeException('Wrong BitPay Invoice');
        }

        $client->requestInvoiceNotification($invoiceData['invoice_id']);
    }
}
