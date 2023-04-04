<?php
declare(strict_types=1);

namespace Bitpay\BPCheckout\Controller\Customer;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\HttpGetActionInterface as HttpGetActionInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;

class Data implements HttpGetActionInterface
{
    /** @var Session $checkoutSession */
    private Session $checkoutSession;

    /** @var JsonFactory $jsonFactory */
    private JsonFactory $jsonFactory;

    /**
     * @param Session $checkoutSession
     * @param JsonFactory $jsonFactory
     */
    public function __construct(
        Session $checkoutSession,
        JsonFactory $jsonFactory
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->jsonFactory = $jsonFactory;
    }

    /**
     * Get checkout customer info
     *
     * @return Json
     */
    public function execute(): Json
    {
        $result = $this->jsonFactory->create();
        $customerData = $this->checkoutSession->getData('customer_info');
        if (!$customerData) {
            return $result->setData(null);
        }

        return $result->setData($customerData);
    }
}
