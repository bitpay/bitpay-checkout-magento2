<?php
declare(strict_types=1);

namespace Bitpay\BPCheckout\Plugin\Onepage;

use Bitpay\BPCheckout\Model\BPRedirect;

class SuccessPlugin
{
    private $bpRedirect;

    public function __construct(BPRedirect $BPRedirect)
    {
        $this->bpRedirect = $BPRedirect;
    }

    public function afterExecute(
        \Magento\Checkout\Controller\Onepage\Success $subject,
        \Magento\Framework\View\Result\Page $page
    ) {
        return $this->bpRedirect->execute($page);
    }
}
