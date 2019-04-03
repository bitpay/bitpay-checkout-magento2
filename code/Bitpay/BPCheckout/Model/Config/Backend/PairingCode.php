<?php
/**
 * @license Copyright 2011-2014 BitPay Inc., MIT License
 * 
 */

namespace Bitpay\Core\Model\Config\Backend;

use Bitpay\Core\Helper\Data;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Value;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;

/**
 * This class will take the pairing code the merchant entered and pair it with
 * BitPay's API.
 */
class PairingCode extends Value
{
    /**
     * @var ManagerInterface
     */
    private $messageManager;

    /**
     * @var Data
     */
    protected $helper;

    /**
     * PairingCode constructor.
     * @param Context $context
     * @param Registry $registry
     * @param ScopeConfigInterface $config
     * @param TypeListInterface $cacheTypeList
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param ManagerInterface $messageManager
     * @param Data $helper
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        ManagerInterface $messageManager,
        Data $helper,
        array $data = [])
    {
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);

        $this->messageManager   = $messageManager;
        $this->helper           = $helper;
    }
    
    /**
     * @inheritdoc
     */
    public function save()
    {
        /**
         * If the user has put a paring code into the text field, we want to
         * pair the magento store to the stores keys. If the merchant is just
         * updating a configuration setting, we could care less about the
         * pairing code.
         */
        $pairingCode = trim( (string) $this->getValue());

        if ($pairingCode === '') {
            return;
        }

        $this->helper->logInfo('Attempting to pair with BitPay with pairing code ' . $pairingCode, __METHOD__);
        $this->helper->logInfo('Attempting to pair with BitPay using the network \'' . $this->helper->getNetwork() . '\'', __METHOD__);
        
        try {
            $pairingService = $this->helper->getBitPayService();
            $pairingService->generateAndPersistKeys();

            $token = $pairingService->createToken($pairingCode);
            $state = $this->helper->getConfig()->saveConfig('payment/bitpay/token', $token->getToken(), ScopeConfigInterface::SCOPE_TYPE_DEFAULT, 0);

        } catch (\Exception $e) {
            $this->helper->logError($e, __METHOD__);
            $this->messageManager->addErrorMessage('There was an error while trying to pair with BitPay using the pairing code '.$pairingCode.'. Please try again or enable debug mode and send the "payment_bitpay.log" file to support@bitpay.com for more help.');

            return;
        }

        if($state) {
            $this->helper->logInfo('Token saved to database.', __METHOD__);
        } else {
            $this->helper->logInfo('Token could not be saved to database.', __METHOD__);

            throw new \Exception('In ' . __METHOD__ . ': token could not be saved to database.');
        }

        $this->messageManager->addSuccessMessage('Pairing with BitPay was successful.');
    }
}
