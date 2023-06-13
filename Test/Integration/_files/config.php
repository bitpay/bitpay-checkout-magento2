<?php

use Magento\Config\Model\Config\Factory;
use Magento\Framework\Encryption\EncryptorInterface;

$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();

$configFactory = $objectManager->create(Factory::class);
$encryptor = $objectManager->create(EncryptorInterface::class);
$configFactory->create([
        'data' => [
            'section' => 'bitpay_merchant_facade',
            'website' => null,
            'store' => null,
            'groups' => [
                'authenticate' => [
                    'fields' => [
                        'token_data' => [
                            'value' => $encryptor->encrypt('{"data":[{"token":"testToken","pairingCode":"1234"}]}')
                        ]
                    ]
                ]
            ]
        ]
    ])->save();
