<?php
declare(strict_types=1);

namespace Bitpay\BPCheckout\Block;

use Bitpay\BPCheckout\Model\Config;
use Magento\Framework\View\Element\Template;

class Index extends Template
{
    /** @var Config $config */
    private $config;

    /**
     * @param Template\Context $context
     * @param Config $config
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        Config $config,
        array $data = []
    ) {
        $this->config = $config;
        parent::__construct($context, $data);
    }

    /**
     * Get base secure URL
     *
     * @return string
     */
    public function getBaseSecureUrl()
    {
        return $this->_scopeConfig->getValue('web/secure/base_url');
    }

    /**
     * Get environment
     *
     * @return string|null
     */
    public function getBitpayEnv(): ?string
    {
        return $this->config->getBitpayEnv();
    }

    /**
     * Get modal param
     *
     * @return int
     */
    public function getModalParam(): int
    {
        return (int) $this->getRequest()->getParam('m');
    }

    /**
     * Get order id
     *
     * @return string
     */
    public function getOrderId(): string
    {
        return (string) $this->getRequest()->getParam('order_id');
    }
}
