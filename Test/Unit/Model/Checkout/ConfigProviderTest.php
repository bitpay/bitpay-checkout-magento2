<?php
declare(strict_types=1);

namespace Bitpay\BPCheckout\Unit\Test\Model\Checkout;

use Bitpay\BPCheckout\Model\Checkout\ConfigProvider;
use Magento\Framework\View\Asset\Repository;
use Magento\Framework\View\Asset\Source;
use Bitpay\BPCheckout\Model\Config;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Payment\Model\MethodInterface;
use Magento\Framework\View\Asset\File;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ConfigProviderTest extends TestCase
{
    /**
     * @var ConfigProvider $configProvider
     */
    private $configProvider;

    /**
     * @var PaymentHelper|MockObject
     */
    private $paymentHelper;

    /**
     * @var Source|MockObject $assetSource
     */
    private $assetSource;

    /**
     * @var Repository|MockObject $assetRepository
     */
    private $assetRepository;

    public function setUp(): void
    {
        $this->paymentHelper = $this->getMockBuilder(PaymentHelper::class)->disableOriginalConstructor()->getMock();
        $this->assetSource = $this->getMockBuilder(Source::class)->disableOriginalConstructor()->getMock();
        $this->assetRepository = $this->getMockBuilder(Repository::class)->disableOriginalConstructor()->getMock();
        $this->configProvider = new ConfigProvider(
            $this->paymentHelper,
            $this->assetRepository,
            $this->assetSource
        );
    }

    public function testGetConfig(): void
    {
        $fileID = $this->getFileId();
        $isAvailable = true;
        $assetUrl = 'http://localhost/pub/image/test.png';
        $method = $this->getMockBuilder(MethodInterface::class)->getMock();
        $asset = $this->getMockBuilder(File::class)->disableOriginalConstructor()->getMock();
        $method->expects($this->once())->method('isAvailable')->willReturn($isAvailable);
        $this->paymentHelper->expects($this->once())
            ->method('getMethodInstance')
            ->with(Config::BITPAY_PAYMENT_METHOD_NAME)
            ->willReturn($method);
        $this->assetRepository->expects($this->once())
            ->method('createAsset')
            ->with($fileID, [])->willReturn($asset);
        $this->assetSource->expects($this->once())
            ->method('findSource')
            ->with($asset)
            ->willReturn('/var/www/html/pub/image/test.png');
        $asset->expects($this->once())->method('getUrl')->willReturn($assetUrl);

        $configData = $this->configProvider->getConfig();

        $this->assertEquals($assetUrl, $configData['payment'][Config::BITPAY_PAYMENT_METHOD_NAME]['paymentIcon']);
        $this->assertTrue(is_array($configData));
    }

    public function testGetConfigMethodNotAvailable(): void
    {
        $isAvailable = false;
        $fileID = $this->getFileId();
        $method = $this->getMockBuilder(MethodInterface::class)->getMock();
        $method->expects($this->once())->method('isAvailable')->willReturn($isAvailable);
        $this->paymentHelper->expects($this->once())
            ->method('getMethodInstance')
            ->with(Config::BITPAY_PAYMENT_METHOD_NAME)
            ->willReturn($method);

        $this->assertTrue(is_array($this->configProvider->getConfig()));
    }

    public function testGetConfigNoFile(): void
    {
        $fileID = $this->getFileId();
        $isAvailable = true;
        $assetUrl = 'http://localhost/pub/image/test.png';
        $method = $this->getMockBuilder(MethodInterface::class)->getMock();
        $asset = $this->getMockBuilder(File::class)->disableOriginalConstructor()->getMock();
        $method->expects($this->once())->method('isAvailable')->willReturn($isAvailable);
        $this->paymentHelper->expects($this->once())
            ->method('getMethodInstance')
            ->with(Config::BITPAY_PAYMENT_METHOD_NAME)
            ->willReturn($method);
        $this->assetRepository->expects($this->once())
            ->method('createAsset')
            ->with($fileID, [])->willReturn($asset);
        $this->assetSource->expects($this->once())
            ->method('findSource')
            ->with($asset)
            ->willReturn(false);

        $this->configProvider->getConfig();
    }

    private function getFileId(): string
    {
        return Config::BITPAY_MODULE_NAME . "::" . Config::BITPAY_PAYMENT_DIR_IMAGES
            . DIRECTORY_SEPARATOR
            . Config::BITPAY_PAYMENT_ICON;
    }
}
