<?php
declare(strict_types=1);

namespace Bitpay\BPCheckout\Model\Ipn;

class Validator
{
    /** @var array $errors */
    protected $errors = [];

    /**
     * @param \BitPaySDK\Model\Invoice\Invoice $invoice
     * @param array $ipnData
     */
    public function __construct(\BitPaySDK\Model\Invoice\Invoice $invoice, array $ipnData)
    {
        $name = $ipnData['buyerFields']['buyerName'];
        $email = $ipnData['buyerFields']['buyerEmail'];
        $address1 = $ipnData['buyerFields']['buyerAddress1'] ?? null;
        $address2 = $ipnData['buyerFields']['buyerAddress2'] ?? null;
        $amountPaid = $ipnData['amountPaid'];
        $invoiceBuyer = $invoice->getBuyer();

        if ($name !== $invoiceBuyerName = $invoiceBuyer->getName()) {
            $this->errors[] = "Name from IPN data ('{$name}') does not match with " .
                "name from invoice ('{$invoiceBuyerName}')";
        }

        if ($email !== $invoiceBuyerEmail = $invoiceBuyer->getEmail()) {
            $this->errors[] = "Email from IPN data ('{$email}') does not match with " .
                "email from invoice ('{$invoiceBuyerEmail}')";
        }

        if ((string)$address1 !== $invoiceBuyerAddress1 = (string)$invoiceBuyer->getAddress1()) {
            $this->errors[] = "Address1 from IPN data ('{$address1}') does not match with " .
                "address1 from invoice ('{$invoiceBuyerAddress1}')";
        }

        if ((string)$address2 !== $invoiceBuyerAddress2 = (string)$invoiceBuyer->getAddress2()) {
            $this->errors[] = "Address2 from IPN data ('{$address2}') does not match with " .
                "address2 from invoice ('{$invoiceBuyerAddress2}')";
        }

        if ((int)$amountPaid !== $invoiceAmountPaid = (int)$invoice->getAmountPaid()) {
            $this->errors[] = "Amount paid from IPN data ('{$amountPaid}') does not match with " .
                "amount paid from invoice ('{$invoiceAmountPaid}')";
        }
    }

    /**
     * Get errors
     *
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
