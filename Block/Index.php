<?php
declare(strict_types=1);

namespace Bitpay\BPCheckout\Block;

use Bitpay\BPCheckout\Model\Config;
use Magento\Framework\View\Element\Template;

class Index extends Template
{
    private $config;

    public function __construct(
        Template\Context $context,
        Config $config,
        array $data = []
    ) {
        $this->config = $config;
        parent::__construct($context, $data);
    }

    public function getBaseSecureUrl()
    {
        return $this->_scopeConfig->getValue('web/secure/base_url');
    }

    public function getBitpayEnv(): ?string
    {
        return $this->config->getBitpayEnv();
    }

    public function getModalParam(): int
    {
        return (int) $this->getRequest()->getParam('m');
    }

    public function getOrderId(): string
    {
        return (string) $this->getRequest()->getParam('order_id');
    }
}
