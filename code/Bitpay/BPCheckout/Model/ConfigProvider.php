<?php
/**
 * @license Copyright 2011-2014 BitPay Inc., MIT License
 * 
 */

namespace Bitpay\Core\Model;

use Bitpay\Core\Model\Method\Bitcoin;
use Magento\Checkout\Model\ConfigProviderInterface;

class ConfigProvider implements ConfigProviderInterface
{

    /**
     * @var Config
     */
    protected $config;

    /**
     * ConfigProvider constructor.
     * @param Config $config
     */
    public function __construct(Config $config) {
        $this->config = $config;
    }

    /**
     * @return array
     */
    public function getConfig() {
        return [
            'payment' => [
                Bitcoin::CODE => [
                    'isActive'      => $this->config->isActive(),
                    'isDebug'       => $this->config->isDebug(),
                    'redirectUrl'   => $this->config->getRedirectUrl(),
                    'isFullScreen'  => $this->config->isFullScreen(),
                ],
            ],
        ];
    }

}