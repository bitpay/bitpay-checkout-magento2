<?php
declare(strict_types=1);

namespace Bitpay\BPCheckout\Plugin\Onepage;

use Bitpay\BPCheckout\Model\BPRedirect;

class SuccessPlugin
{
    /** @var BPRedirect $bpRedirect */
    private $bpRedirect;

    /**
     * @param BPRedirect $BPRedirect
     */
    public function __construct(BPRedirect $BPRedirect)
    {
        $this->bpRedirect = $BPRedirect;
    }

    /**
     * Create invoice after order success action
     *
     * @param \Magento\Checkout\Controller\Onepage\Success $subject
     * @param \Magento\Framework\Controller\ResultInterface $result
     * @return \Magento\Framework\View\Result\Page|null
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function afterExecute(
        \Magento\Checkout\Controller\Onepage\Success $subject,
        \Magento\Framework\Controller\ResultInterface $result
    ) {
        return $this->bpRedirect->execute();
    }
}
