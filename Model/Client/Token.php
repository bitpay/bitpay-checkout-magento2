<?php
declare(strict_types=1);

namespace Bitpay\BPCheckout\Model\Client;

use Bitpay\BPCheckout\Model\Config;
use Bitpay\BPCheckout\Model\Config as BitpayConfig;
use BitPayKeyUtils\KeyHelper\PrivateKey;
use BitPayKeyUtils\Storage\EncryptedFilesystemStorage;

class Token
{
    protected $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * @param string $privateKeyPath
     * @param string $password
     * @param string|null $tokenLabel
     * @return array
     */
    public function create(string $privateKeyPath, string $password, ?string $tokenLabel): array
    {
        $privateKey = new PrivateKey($privateKeyPath);
        $privateKey = $privateKey->generate();
        $storageEngine = new EncryptedFilesystemStorage($password);
        $storageEngine->persist($privateKey);

        return $this->tokenRequest($privateKey, $tokenLabel);
    }

    /**
     * @param PrivateKey $privateKey
     * @param string|null $tokenLabel
     * @return array
     */
    protected function tokenRequest(PrivateKey $privateKey, ?string $tokenLabel): array
    {
        $facade      = BitpayConfig::BITPAY_MERCHANT_FACADE;
        $publicKey   = $privateKey->getPublicKey();
        $resourceUrl = $this->config->getBitpayEnv() === 'test'
            ? BitpayConfig::BITPAY_DEV_TOKEN_URL
            : BitpayConfig::BITPAY_PROD_TOKEN_URL;
        $sin         = $publicKey->getSin()->__toString();

        $postData = [
            'id' => $sin,
            'facade' => $facade
        ];

        if ($tokenLabel) {
            $postData['label'] = $tokenLabel;
        }

        $postData = json_encode($postData);
        $curlCli = curl_init($resourceUrl);
        $xSignature = $privateKey->sign($resourceUrl . $postData);

        curl_setopt($curlCli, CURLOPT_HTTPHEADER, [
            'x-accept-version: 2.0.0',
            'Content-Type: application/json',
            'x-identity' => $publicKey->__toString(),
            'x-signature' => $xSignature
        ]);

        curl_setopt($curlCli, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($curlCli, CURLOPT_POSTFIELDS, stripslashes($postData));
        curl_setopt($curlCli, CURLOPT_RETURNTRANSFER, true);

        $result = curl_exec($curlCli);
        $resultData = json_decode($result, TRUE);

        curl_close($curlCli);

        return $resultData;
    }
}
