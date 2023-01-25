<?php
declare(strict_types=1);

namespace Bitpay\BPCheckout\Model;

use BitPaySDK\Env;
use BitPaySDK\Tokens;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Serialize\Serializer\Json;

class Client
{
    protected $config;
    protected $encryptor;
    protected $serializer;

    public function __construct(
        Config $config,
        EncryptorInterface $encryptor,
        Json $serializer
    ) {
        $this->config = $config;
        $this->encryptor = $encryptor;
        $this->serializer = $serializer;
    }
    public function initialize()
    {
        $env = $this->config->getBitpayEnv() === 'test' ? Env::Test : Env::Prod;
        $privateKeyPath = $this->config->getPrivateKeyPath();
        $password = $this->encryptor->decrypt($this->config->getMerchantFacadePassword());
        $tokenData = $this->encryptor->decrypt($this->config->getMerchantTokenData());
        $serializedTokenData = $this->serializer->unserialize($tokenData);
        $merchantToken = $serializedTokenData['data'][0]['token'];
        $tokens = new Tokens($merchantToken);

        return \BitPaySDK\Client::create()->withData($env, $privateKeyPath, $tokens, $password);
    }
}