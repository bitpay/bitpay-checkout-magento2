<?php
declare(strict_types=1);

namespace Bitpay\BPCheckout\Test\Integration\Model\Checkout;

use Bitpay\BPCheckout\Model\Checkout\ConfigProvider;
use Bitpay\BPCheckout\Model\Config;
use Magento\Framework\View\Asset\Repository;
use Magento\Framework\View\Asset\Source;
use Magento\Framework\ObjectManagerInterface;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

class ConfigProviderTest extends TestCase
{
    /**
     * @var Source $assetSource
     */
    private $assetSource;

    /**
     * @var Repository $assetRepository
     */
    private $assetRepository;

    /**
     * @var PaymentHelper $paymentHelper
     */
    private $paymentHelper;

    /**
     * @var ObjectManagerInterface $objectManager
     */
    private $objectManager;

    /**
     * @var ConfigProvider $configProvider
     */
    private $configProvider;

    public function setUp(): void
    {
        $this->objectManager =  Bootstrap::getObjectManager();
        $this->assetSource =  $this->objectManager->get(Source::class);
        $this->assetRepository =  $this->objectManager->get(Repository::class);
        $this->paymentHelper =  $this->objectManager->get(PaymentHelper::class);
        $this->configProvider = new ConfigProvider(
            $this->paymentHelper,
            $this->assetRepository,
            $this->assetSource,
        );
    }

    /**
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     * @magentoConfigFixture current_store payment/bpcheckout/active 1
     * @magentoConfigFixture current_store payment/bpcheckout/bitpay_devtoken AMLTTY9x9TGXFPcsnLLjem1CaDJL3mRMWupBrm9ba
     * @magentoConfigFixture current_store payment/bpcheckout/bitpay_endpoint test
     * @magentoAppArea frontend
     */
    public function testGetConfig(): void
    {
        $bitpayMethod = $this->paymentHelper->getMethodInstance(Config::BITPAY_PAYMENT_METHOD_NAME);
        $this->assertEquals(true, $bitpayMethod->isAvailable());

        $asset = $this->assetRepository->createAsset(
            Config::BITPAY_MODULE_NAME . "::" . Config::BITPAY_PAYMENT_DIR_IMAGES
            . DIRECTORY_SEPARATOR . Config::BITPAY_PAYMENT_ICON,
            []
        );
        $filePath = $this->assetSource->findSource($asset);
        $config = $this->configProvider->getConfig();
        if ($filePath) {
            $this->assertEquals(
                $config['payment'][Config::BITPAY_PAYMENT_METHOD_NAME]['paymentIcon'],
                $asset->getUrl()
            );
        }

        $this->assertTrue(is_array($config));
    }

    /**
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     * @magentoConfigFixture current_store payment/bpcheckout/active 0
     */
    public function testGetConfigMethodNotAvailable(): void
    {
        $bitpayMethod = $this->paymentHelper->getMethodInstance(Config::BITPAY_PAYMENT_METHOD_NAME);
        $this->assertEquals(false, $bitpayMethod->isAvailable());

        $this->assertEquals([], $this->configProvider->getConfig());
    }
}
