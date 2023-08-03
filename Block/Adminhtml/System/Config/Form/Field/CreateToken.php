<?php
declare(strict_types=1);

namespace Bitpay\BPCheckout\Block\Adminhtml\System\Config\Form\Field;

use Bitpay\BPCheckout\Model\Config;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Store\Model\ScopeInterface;

class CreateToken extends Field
{
    protected Config $config;
    protected EncryptorInterface $encryptor;

    public function __construct(
        Context $context,
        Config $config,
        EncryptorInterface $encryptor,
        array $data = []
    ) {
        $this->config = $config;
        $this->encryptor = $encryptor;
        parent::__construct($context, $data);
    }

    /**
     * Get pairing code from token data
     *
     * @return string|null
     */
    public function getPairingCode(): ?string
    {
        $tokenData = $this->config->getMerchantTokenData();
        if (!$tokenData) {
            return null;
        }
        $tokenData = $this->encryptor->decrypt($tokenData);
        $tokenData = json_decode($tokenData, true);

        return $tokenData['data'][0]['pairingCode'];
    }

    /**
     * Get url to generate token
     *
     * @return string
     */
    public function getTokenUrl(): string
    {
        return $this->getUrl('bitpay/merchant/token');
    }

    /**
     * Get message after token creation
     *
     * @return string|null
     */
    public function getMessage(): ?string
    {
        $url = $this->config->getBitpayEnv() === 'test'
            ? 'https://' . Config::API_HOST_DEV . '/' . Config::BITPAY_API_TOKEN_PATH
            : 'https://' . Config::API_HOST_PROD . '/' . Config::BITPAY_API_TOKEN_PATH;

        return"Claim your pairing code on <a href=\"{$url}\">Bitpay</a>";
    }

    /**
     * Prepare layout
     *
     * @return $this
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        if (!$this->getTemplate()) {
            $this->setTemplate('system/config/fieldset/create_token.phtml');
        }

        return $this;
    }

    /**
     * Get the Html for the element
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return mixed
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        return $this->_toHtml();
    }
}
