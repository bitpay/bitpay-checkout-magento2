<?php
declare(strict_types=1);

namespace Bitpay\BPCheckout\Test\Unit\Model;

use Bitpay\BPCheckout\Model\SupportPackage;
use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Module\Dir as ModuleDir;
use Magento\Framework\Module\FullModuleList;
use Magento\Framework\Module\PackageInfo;
use Magento\Framework\Module\ResourceInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\UrlInterface;
use Magento\Framework\Xml\Parser as XmlParser;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ZipArchive;

class SupportPackageTest extends TestCase
{
    /**
     * @var SupportPackage
     */
    private $supportPackage;

    /**
     * @var FullModuleList|MockObject
     */
    private $fullModuleListMock;

    /**
     * @var ResourceInterface|MockObject
     */
    private $moduleResourceMock;

    /**
     * @var PackageInfo|MockObject
     */
    private $packageInfoMock;

    /**
     * @var DeploymentConfig|MockObject
     */
    private $deploymentConfigMock;

    /**
     * @var ResourceConnection|MockObject
     */
    private $resourceConnectionMock;

    /**
     * @var XmlParser|MockObject
     */
    private $xmlParserMock;

    /**
     * @var ModuleDir|MockObject
     */
    private $moduleDirMock;

    /**
     * @var UrlInterface|MockObject
     */
    private $urlBuilderMock;

    /**
     * @var ProductMetadataInterface|MockObject
     */
    private $productMetadataMock;

    /**
     * @var DirectoryList|MockObject
     */
    private $directoryListMock;

    /**
     * @var Json|MockObject
     */
    private $jsonSerializerMock;

    /**
     * @var File|MockObject
     */
    private $fileDriverMock;

    /**
     * @var ZipArchive|MockObject
     */
    private $zipArchiveMock;

    protected function setUp(): void
    {
        /**
         * @var FullModuleList
         */
        $this->fullModuleListMock = $this->createMock(FullModuleList::class);
        /**
         * @var ResourceInterface
         */
        $this->moduleResourceMock = $this->createMock(ResourceInterface::class);
        /**
         * @var PackageInfo
         */
        $this->packageInfoMock = $this->createMock(PackageInfo::class);
        /**
         * @var DeploymentConfig
         */
        $this->deploymentConfigMock = $this->createMock(DeploymentConfig::class);
        /**
         * @var ResourceConnection
         */
        $this->resourceConnectionMock = $this->createMock(ResourceConnection::class);
        /**
         * @var XmlParser
         */
        $this->xmlParserMock = $this->createMock(XmlParser::class);
        /**
         * @var ModuleDir
         */
        $this->moduleDirMock = $this->createMock(ModuleDir::class);
        /**
         * @var UrlInterface
         */
        $this->urlBuilderMock = $this->createMock(UrlInterface::class);
        /**
         * @var ProductMetadataInterface
         */
        $this->productMetadataMock = $this->createMock(ProductMetadataInterface::class);
        /**
         * @var DirectoryList
         */
        $this->directoryListMock = $this->createMock(DirectoryList::class);
        /**
         * @var Json
         */
        $this->jsonSerializerMock = $this->createMock(Json::class);
        /**
         * @var File
         */
        $this->fileDriverMock = $this->createMock(File::class);
        /**
         * @var ZipArchive
         */
        $this->zipArchiveMock = $this->createMock(ZipArchive::class);

        $this->supportPackage = new SupportPackage(
            $this->fullModuleListMock,
            $this->moduleResourceMock,
            $this->packageInfoMock,
            $this->deploymentConfigMock,
            $this->resourceConnectionMock,
            $this->xmlParserMock,
            $this->moduleDirMock,
            $this->urlBuilderMock,
            $this->productMetadataMock,
            $this->directoryListMock,
            $this->jsonSerializerMock,
            $this->fileDriverMock,
            $this->zipArchiveMock
        );
    }

    public function testPrepareDownloadArchive()
    {
        $configData = [
            'db' => [
                'connection' => [
                    'default' => [
                        'model' => 'mysql'
                    ]
                ]
            ]
        ];
        $this->deploymentConfigMock->method('getConfigData')->willReturn($configData);

        $modules = [
            ['name' => 'Module_One'],
            ['name' => 'Module_Two']
        ];

        $this->fullModuleListMock->method('getAll')
            ->willReturn($modules);

        $tmpPath = '/tmp';
        $logPath = '/log/bitpay.log';
        $archivePath = $tmpPath . '/bitpay-support.zip';

        $this->directoryListMock->method('getPath')
            ->will($this->returnValueMap([
                [DirectoryList::TMP, $tmpPath],
                [DirectoryList::LOG, '/log']
            ]));

        $this->fileDriverMock->expects($this->exactly(2))
            ->method('isExists')
            ->willReturn(true);

        $this->jsonSerializerMock->method('serialize')
            ->willReturn('{"key":"value"}');

        $connectionMock = $this->createMock(\Magento\Framework\DB\Adapter\Pdo\Mysql::class);
        $this->resourceConnectionMock->method('getConnection')
            ->willReturn($connectionMock);

        $connectionMock->method('fetchOne')
            ->willReturn('5.7.32');
        $connectionMock->method('fetchRow')
            ->willReturn(['Value' => 'utf8_general_ci']);

        $this->xmlParserMock->method('load')
            ->willReturnSelf();
        $this->xmlParserMock->method('xmlToArray')
            ->willReturn([
                'schema' => [
                    '_value' => [
                        'table' => []
                    ]
                ]
            ]);

        $_SERVER['SERVER_SOFTWARE'] = 'Apache/2.4.41';

        $this->zipArchiveMock->expects($this->once())
            ->method('open')
            ->with($archivePath, ZipArchive::CREATE | ZipArchive::OVERWRITE)
            ->willReturn(true);
        $this->zipArchiveMock->expects($this->once())
            ->method('addFromString')
            ->with('bitpay-support.json', '{"key":"value"}');
        $this->zipArchiveMock->expects($this->once())
            ->method('addFile')
            ->with($logPath, 'bitpay.log');
        $this->zipArchiveMock->expects($this->once())
            ->method('close');

        $this->supportPackage->prepareDownloadArchive();
    }

    public function testPrepareSupportDetails()
    {
        $configData = [
            'db' => [
                'connection' => [
                    'default' => [
                        'model' => 'mysql'
                    ]
                ]
            ]
        ];
        $this->deploymentConfigMock->method('getConfigData')
            ->willReturn($configData);

        $modules = [
            ['name' => 'Module_One'],
            ['name' => 'Module_Two']
        ];

        $this->fullModuleListMock->method('getAll')
            ->willReturn($modules);

        $connectionMock = $this->createMock(\Magento\Framework\DB\Adapter\Pdo\Mysql::class);
        $this->resourceConnectionMock->method('getConnection')
            ->willReturn($connectionMock);

        $connectionMock->method('fetchOne')
            ->willReturn('5.7.32');
        $connectionMock->method('fetchRow')
            ->willReturn(['Value' => 'utf8_general_ci']);

        $this->xmlParserMock->method('load')
            ->willReturnSelf();
        $this->xmlParserMock->method('xmlToArray')
            ->willReturn([
                'schema' => [
                    '_value' => [
                        'table' => []
                    ]
                ]
            ]);

        $details = $this->supportPackage->prepareSupportDetails();
        $this->assertIsArray($details);
    }

    public function testGetBitpayModuleVersion()
    {
        $this->packageInfoMock->method('getVersion')
            ->with('Bitpay_BPCheckout')
            ->willReturn('1.0.0');

        $version = $this->supportPackage->getBitpayModuleVersion();
        $this->assertEquals('1.0.0', $version);
    }

    public function testGetModuleList()
    {
        $modules = [
            ['name' => 'Module_One'],
            ['name' => 'Module_Two']
        ];

        $this->fullModuleListMock->method('getAll')
            ->willReturn($modules);

        $this->moduleResourceMock->method('getDbVersion')
            ->willReturn('1.0.0');
        $this->moduleResourceMock->method('getDataVersion')
            ->willReturn('1.0.0');

        $moduleList = $this->supportPackage->getModuleList();
        $this->assertCount(2, $moduleList);
    }

    public function testGetDbDetails()
    {
        $configData = [
            'db' => [
                'connection' => [
                    'default' => [
                        'model' => 'mysql'
                    ]
                ]
            ]
        ];

        $this->deploymentConfigMock->method('getConfigData')
            ->willReturn($configData);

        $connectionMock = $this->createMock(\Magento\Framework\DB\Adapter\Pdo\Mysql::class);
        $this->resourceConnectionMock->method('getConnection')
            ->willReturn($connectionMock);

        $connectionMock->method('fetchOne')
            ->willReturn('5.7.32');
        $connectionMock->method('fetchRow')
            ->willReturn(['Value' => 'utf8_general_ci']);

        $this->xmlParserMock->method('load')
            ->willReturnSelf();
        $this->xmlParserMock->method('xmlToArray')
            ->willReturn([
                'schema' => [
                    '_value' => [
                        'table' => []
                    ]
                ]
            ]);

        $dbDetails = $this->supportPackage->getDbDetails();
        $this->assertIsArray($dbDetails);
    }

    public function testGetMagentoDetails()
    {
        $this->urlBuilderMock->method('getBaseUrl')
            ->willReturn('http://example.com');
        $this->productMetadataMock->method('getVersion')
            ->willReturn('2.4.2');

        $magentoDetails = $this->supportPackage->getMagentoDetails();
        $this->assertIsArray($magentoDetails);
    }

    public function testGetServerDetails()
    {
        $_SERVER['SERVER_SOFTWARE'] = 'Apache/2.4.41';

        $this->directoryListMock->method('getRoot')
            ->willReturn('/var/www/html');

        $serverDetails = $this->supportPackage->getServerDetails();
        $this->assertIsArray($serverDetails);
    }

    public function testGetPhpDetails()
    {
        $phpDetails = $this->supportPackage->getPhpDetails();
        $this->assertIsArray($phpDetails);
    }
}
