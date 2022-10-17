<?php
declare(strict_types=1);

namespace Bitpay\BPCheckout\Model\Checkout;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Asset\Repository;
use Bitpay\BPCheckout\Model\Config;
use Magento\Framework\View\Asset\Source;
use Magento\Framework\View\Asset\File;
use Magento\Payment\Helper\Data as PaymentHelper;

class ConfigProvider implements ConfigProviderInterface
{
    protected $assetSource;
    private $assetRepository;
    private $paymentHelper;

    public function __construct(
        PaymentHelper $paymentHelper,
        Repository $assetRepository,
        Source $assetSource
    ) {
        $this->paymentHelper = $paymentHelper;
        $this->assetRepository = $assetRepository;
        $this->assetSource = $assetSource;
    }

    /**
     * @return array
     * @throws LocalizedException
     */
    public function getConfig(): array
    {
        $config = [];
        $bitpayMethod = $this->paymentHelper->getMethodInstance(Config::BITPAY_PAYMENT_METHOD_NAME);
        if (!$bitpayMethod->isAvailable()) {
            return $config;
        }

        /** @var File $file */
        $asset = $this->assetRepository->createAsset(
            Config::BITPAY_MODULE_NAME . "::" . Config::BITPAY_PAYMENT_DIR_IMAGES . DIRECTORY_SEPARATOR . Config::BITPAY_PAYMENT_ICON,
            []);
        $filePath = $this->assetSource->findSource($asset);
        if (!$filePath) {
            return $config;
        }

        $config['payment'][Config::BITPAY_PAYMENT_METHOD_NAME]['paymentIcon'] = $asset->getUrl();

        return $config;
    }
}
