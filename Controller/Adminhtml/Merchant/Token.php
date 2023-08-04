<?php
declare(strict_types=1);

namespace Bitpay\BPCheckout\Controller\Adminhtml\Merchant;

use Bitpay\BPCheckout\Exception\TokenCreationException;
use Bitpay\BPCheckout\Logger\Logger;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\App\Action\HttpPostActionInterface as HttpPostActionInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Config\Model\ResourceModel\Config;
use Magento\Framework\App\RequestInterface;
use Bitpay\BPCheckout\Model\Config as BitpayConfig;
use Magento\Config\Model\Config\Factory;

class Token implements HttpPostActionInterface
{
    protected EncryptorInterface $encryptor;
    protected JsonFactory $jsonFactory;
    protected RequestInterface $request;
    protected Logger $logger;
    protected BitpayConfig $bitpayConfig;
    protected Factory $configFactory;
    protected \Bitpay\BPCheckout\Model\Client\Token $token;

    public function __construct(
        EncryptorInterface $encryptor,
        JsonFactory $jsonFactory,
        RequestInterface $request,
        Logger $logger,
        BitpayConfig $bitpayConfig,
        Factory $configFactory,
        \Bitpay\BPCheckout\Model\Client\Token $token
    ) {
        $this->encryptor = $encryptor;
        $this->jsonFactory = $jsonFactory;
        $this->request = $request;
        $this->logger = $logger;
        $this->bitpayConfig = $bitpayConfig;
        $this->configFactory = $configFactory;
        $this->token = $token;
    }

    /**
     * Generate bitpay token action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        try {
            $tokenLabel = $this->request->getParam('token_label');
            $privateKeyPath = $this->bitpayConfig->getPrivateKeyPath();
            $password = $this->encryptor->decrypt($this->bitpayConfig->getMerchantFacadePassword());
            if (!$privateKeyPath || !$password) {
                throw new TokenCreationException('Please save password and private key path first');
            }

            $result = $this->token->create($privateKeyPath, $password, $tokenLabel);
            $pairingCode = $result['data'][0]['pairingCode'];
            $url = $this->bitpayConfig->getBitpayEnv() === 'test'
                ? 'https://' . BitpayConfig::API_HOST_DEV . '/' . BitpayConfig::BITPAY_API_TOKEN_PATH
                : 'https://' . BitpayConfig::API_HOST_PROD . '/' . BitpayConfig::BITPAY_API_TOKEN_PATH;

            $resultEncrypted = $this->encryptor->encrypt(json_encode($result));
            $configData = $this->prepareConfigData($resultEncrypted, $tokenLabel);

            /** @var Config $configModel */
            $configModel = $this->configFactory->create(['data' => $configData]);
            $configModel->save();

            return $this->jsonFactory->create()->setData(['pairingCode' => $pairingCode, 'url' => $url]);
            //phpcs:ignore
        } catch (TokenCreationException $creationException) {
            $this->logger->error('Error during generating token: ' . $creationException->getMessage());

            return $this->jsonFactory->create()->setData(
                ['error' => true, 'message' => $creationException->getMessage()]
            );

        } catch (\Exception $exception) {
            $this->logger->error('Error during generating token: ' . $exception->getMessage());

            return $this->jsonFactory->create()->setData(['error' => true, 'message' => $exception->getMessage()]);
        }
    }

    /**
     * Prepare core config bitpay token data
     *
     * @param string $resultEncrypted
     * @param string|null $tokenLabel
     * @return array
     */
    protected function prepareConfigData(string $resultEncrypted, ?string $tokenLabel): array
    {
        return [
            'section' => 'bitpay_merchant_facade',
            'website' => null,
            'store'   => null,
            'groups'  => [
                'authenticate' => [
                    'fields' => [
                        'token_label' => [
                            'value' => $tokenLabel,
                        ],
                        'token_data' => [
                            'value' => $resultEncrypted
                        ]
                    ],
                ],
            ]
        ];
    }
}
