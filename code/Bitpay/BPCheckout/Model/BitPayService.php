<?php

namespace Bitpay\Core\Model;

use Bitpay\Client\Adapter\CurlAdapter;
use Bitpay\Client\Client;
use Bitpay\Network\Livenet;
use Bitpay\Network\Testnet;
use Bitpay\PrivateKey;
use Bitpay\PublicKey;
use Bitpay\Core\Helper\Data;
use Bitpay\SinKey;
use Bitpay\Token;

class BitPayService {

    const STORE_PRIVATE_KEY = 'payment/bitpay/private_key';

    const STORE_PUBLIC_KEY = 'payment/bitpay/public_key';

    /**
     * @var Data
     */
    protected $dataHelper;

    /**
     * @var MagentoStorage
     */
    protected $magentoStorage;

    /**
     * PairingService constructor.
     * @param Data $dataHelper
     * @param MagentoStorage $magentoStorage
     */
    public function __construct(Data $dataHelper, MagentoStorage $magentoStorage) {
        $this->dataHelper       = $dataHelper;
        $this->magentoStorage   = $magentoStorage;
    }

    /**
     * Generates keys and stored to the Storage which will be returned at the end.
     *
     * @return MagentoStorage
     */
    public function generateAndPersistKeys() {
        $privateKey = PrivateKey::create(self::STORE_PRIVATE_KEY)->generate();
        $publicKey  = new PublicKey(self::STORE_PUBLIC_KEY);

        $publicKey->generate($privateKey);

        $this->magentoStorage->persist($privateKey);
        $this->magentoStorage->persist($publicKey);

        return $this->magentoStorage;
    }

    /**
     * Returns BitPay Client.
     *
     * @return Client
     * @throws \Exception
     */
    public function getClient() {
        $client     = new Client();
        $adapter    = new CurlAdapter();
        $publicKey  = $this->magentoStorage->load(self::STORE_PUBLIC_KEY);
        $privateKey = $this->magentoStorage->load(self::STORE_PRIVATE_KEY);

        $network = $this->dataHelper->isLivenetNetwork()
            ? new Livenet()
            : new Testnet();

        $client->setNetwork($network);
        $client->setAdapter($adapter);
        $client->setPublicKey($publicKey);
        $client->setPrivateKey($privateKey);

        if($token = $this->dataHelper->getToken()) {
            $client->setToken( (new Token)->setToken($token) );
        }

        return $client;
    }

    /**
     * Returns generated token for the given pairing code.
     *
     * @param $pairingCode
     * @return \Bitpay\Token
     * @throws \Exception
     */
    public function createToken($pairingCode) {
        $this->dataHelper->logInfo('Loading storage', __METHOD__);

        $publicKey = $this->magentoStorage->load(self::STORE_PUBLIC_KEY);

        $sinKey = new SinKey();
        $sinKey->setPublicKey($publicKey);
        $sinKey->generate();

        try {
            return $this->getClient()->createToken(
                array(
                    'pairingCode' => $pairingCode,
                    'label'       => $this->dataHelper->getStoreNameAsLabel(),
                    'id'          => (string) $sinKey,
                )
            );
        } catch (\Exception $e) {
            $this->dataHelper->logError($e, __METHOD__);

            throw $e;
        }
    }

}
