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
                            //phpcs:ignore
                            'value' => $encryptor->encrypt(('{"data":[{"policies":[{"policy":"id","method":"inactive","params":["Tf16z41ysnWHB9J2oAPr4EA6QBgZC48DTro"]}],"token":"HK4huiR44343ByCLfxwN95wNJXVv3HUU3ZRcTwZh51FtCXij","facade":"merchant","label":"test","dateCreated":1680615924418,"pairingExpiration":1680702324418,"pairingCode":"5Vt432zcwh"}]}'))
                        ]
                    ]
                ]
            ]
        ]
    ])->save();
