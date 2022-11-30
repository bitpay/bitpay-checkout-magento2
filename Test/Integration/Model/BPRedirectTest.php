<?php
declare(strict_types=1);

namespace Bitpay\BPCheckout\Test\Integration\Model;

use Bitpay\BPCheckout\Model\BPRedirect;
use Magento\Checkout\Model\Session;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Flag;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Phrase;
use Magento\Sales\Model\Order;
use Bitpay\BPCheckout\Logger\Logger;
use Bitpay\BPCheckout\Model\Config;
use Bitpay\BPCheckout\Model\Invoice;
use Bitpay\BPCheckout\Model\TransactionRepository;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\ActionFlag;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Bitpay\BPCheckout\Model\Ipn\BPCItem;
use Magento\Framework\Message\Manager;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\UrlInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Framework\App\ResponseFactory;
use Magento\Framework\View\Result\PageFactory;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class BPRedirectTest extends TestCase
{
    /**
     * @var BPRedirect $bpRedirect
     */
    private $bpRedirect;

    /**
     * @var ObjectManagerInterface $objectManager
     */
    private $objectManager;

    /**
     * @var Session $checkoutSession
     */
    private $checkoutSession;

    /**
     * @var Flag $actionFlag
     */
    private $actionFlag;

    /**
     * @var RedirectInterface $redirect
     */
    private $redirect;

    /**
     * @var ResponseInterface $response
     */
    private $response;

    /**
     * @var OrderInterface $orderInterface
     */
    private $orderInterface;

    /**
     * @var Config $config
     */
    private $config;

    /**
     * @var TransactionRepository $transactionRepository
     */
    private $transactionRepository;

    /**
     * @var ResponseFactory $responseFactory
     */
    private $responseFactory;

    /**
     * @var Invoice|MockObject $invoice
     */
    private $invoice;

    /**
     * @var Manager $messageManager
     */
    private $messageManager;

    /**
     * @var Registry $registry
     */
    private $registry;

    /**
     * @var UrlInterface $url
     */
    private $url;

    /**
     * @var Logger $logger
     */
    private $logger;

    /**
     * @var PageFactory $resultPageFactory
     */
    private $resultPageFactory;

    public function setUp(): void
    {
        $this->objectManager =  Bootstrap::getObjectManager();
        $this->checkoutSession = $this->objectManager->get(Session::class);
        $this->actionFlag = $this->objectManager->get(\Magento\Framework\App\ActionFlag::class);
        $this->redirect = $this->objectManager->get(RedirectInterface::class);
        $this->response = $this->objectManager->get(ResponseInterface::class);
        $this->orderInterface = $this->objectManager->get(OrderInterface::class);
        $this->config = $this->objectManager->get(Config::class);
        $this->transactionRepository = $this->objectManager->get(TransactionRepository::class);
        $this->responseFactory = $this->objectManager->get(ResponseFactory::class);
        $this->invoice = $this->getMockBuilder(Invoice::class)->disableOriginalConstructor()->getMock();
        $this->messageManager = $this->objectManager->get(Manager::class);
        $this->registry = $this->objectManager->get(Registry::class);
        $this->url = $this->objectManager->get(UrlInterface::class);
        $this->logger = $this->objectManager->get(Logger::class);
        $this->resultPageFactory = $this->objectManager->get(PageFactory::class);

        $this->bpRedirect = new BPRedirect(
            $this->checkoutSession,
            $this->actionFlag,
            $this->redirect,
            $this->response,
            $this->orderInterface,
            $this->config,
            $this->transactionRepository,
            $this->responseFactory,
            $this->invoice,
            $this->messageManager,
            $this->registry,
            $this->url,
            $this->logger,
            $this->resultPageFactory
        );
    }

    /**
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @magentoDataFixture Bitpay_BPCheckout::Test/Integration/_files/order.php
     * @magentoConfigFixture current_store payment/bpcheckout/bitpay_devtoken AMLTTY9x9TGXFPcsnLLjem1CaDJL3mRMWupBrm9baacy
     * @magentoConfigFixture current_store payment/bpcheckout/bitpay_endpoint test
     * @magentoConfigFixture current_store payment/bpcheckout/bitpay_ux redirect
     */
    public function testExecute(): void
    {
        /** @var Order $order */
        $order = $this->objectManager->get(Order::class);
        $session = $this->objectManager->get(Session::class);
        $baseUrl = $this->config->getBaseUrl();
        $order = $order->loadByIncrementId('100000001');
        $orderId = $order->getId();
        $session->setLastOrderId($orderId);
        $methodCode = $order->getPayment()->getMethodInstance()->getCode();
        $bitpayMethodCode = Config::BITPAY_PAYMENT_METHOD_NAME;
        $this->invoice->expects($this->once())->method('BPCCreateInvoice')
            ->willReturn(['data' => ['id' => 'VjvZbvsW56tzYX65ZXk4xq', 'url' => 'https://localhost/bpcheckout/index']]);

        $this->bpRedirect->execute();

        $customerInfo = $this->checkoutSession->getCustomerInfo();

        $this->assertEquals('customer@example.com', $customerInfo['email']);
        $this->assertEquals('100000001', $customerInfo['incrementId']);
        $this->assertEquals('firstname', $customerInfo['billingAddress']['firstname']);
        $this->assertEquals('lastname', $customerInfo['billingAddress']['lastname']);

        $result = $this->transactionRepository->findBy('100000001', 'VjvZbvsW56tzYX65ZXk4xq');
        $this->assertEquals('VjvZbvsW56tzYX65ZXk4xq', $result[0]['transaction_id']);
        $this->assertEquals('100000001', $result[0]['order_id']);
        $this->assertEquals('new', $result[0]['transaction_status']);
        $this->assertEquals('test', $this->config->getBitpayEnv());
        $this->assertEquals('redirect', $this->config->getBitpayUx());
        $this->assertEquals($bitpayMethodCode, $methodCode);
    }

    /**
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @magentoDataFixture Bitpay_BPCheckout::Test/Integration/_files/order.php
     * @magentoConfigFixture current_store payment/bpcheckout/bitpay_devtoken AMLTTY9x9TGXFPcsnLLjem1CaDJL3mRMWupBrm9baacy
     * @magentoConfigFixture current_store payment/bpcheckout/bitpay_endpoint test
     * @magentoConfigFixture current_store payment/bpcheckout/bitpay_ux redirect
     */
    public function testExecuteException(): void
    {
        $order = $this->objectManager->get(Order::class);
        $session = $this->objectManager->get(Session::class);
        $baseUrl = $this->config->getBaseUrl();
        $order = $order->loadByIncrementId('100000001');
        $orderId = $order->getId();
        $session->setLastOrderId($orderId);

        $this->invoice->expects($this->once())->method('BPCCreateInvoice')
            ->willThrowException(new LocalizedException(new Phrase('Invalid token')));

        $this->bpRedirect->execute();
        $this->assertEquals(
            'We are unable to place your Order at this time',
            $this->messageManager->getMessages()->getLastAddedMessage()->getText()
        );
    }
}
