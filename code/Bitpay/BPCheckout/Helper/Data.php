<?php
/**
 * @license Copyright 2011-2014 BitPay Inc., MIT License
 * 
 */

namespace Bitpay\Core\Helper;

use Bitpay\Core\Logger\Logger;
use Bitpay\Core\Model\BitPayFactory;
use Bitpay\Core\Model\BitPayService;
use Magento\Config\Model\ResourceModel\Config;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ObjectManager;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Framework\App\ProductMetadataInterface;
use Exception;

class Data extends AbstractHelper
{


    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;
    
    /**
     * @var ModuleListInterface
     */
    protected $_moduleList;
    
    /**
     * @var ProductMetadataInterface
     */
    protected $productMetadata;

    /**
     * Data constructor.
     * @param Context $context
     * @param Logger $logger
     * @param Config $config
     * @param StoreManagerInterface $storeManager
     */
	public function __construct(Context $context, Logger $logger, Config $config, StoreManagerInterface $storeManager, ModuleListInterface $moduleList, ProductMetadataInterface $productMetadata)
	{
        parent::__construct($context);

        $this->_moduleList = $moduleList;
        $this->productMetadata = $productMetadata;
        $this->logger = $logger;
        $this->config = $config;
        $this->storeManager = $storeManager;
	}

    /**
     * Log message as info.
     *
     * @param string|Exception $message
     * @param string|null $method Method or function name
     * @return string Completed message
     */
	public function logInfo($message, $method = null) {
	    return $this->debugData('info', $message, $method);
    }

    /**
     * Log message as error.
     *
     * @param string|Exception $message
     * @param string|null $method Method or function name
     * @return string Completed message
     */
    public function logError($message, $method = null) {
        return $this->debugData('error', $message, $method);
    }

    /**
     * Log message.
     *
     * @param string $type
     * @param string|Exception $message
     * @param string|null $method Method or function name
     * @return string Completed message
     */
    public function debugData($type, $message, $method = null) {
        //log information about the environment
        $phpVersion = explode('-', phpversion())[0];
        $extendedDebugData = array(
            '[PHP version] ' . $phpVersion,
            '[Magento version] ' . $this->getMagentoVersion(),
            '[BitPay plugin version] ' . $this->getExtensionVersion(), 
        );
        foreach($extendedDebugData as &$param)
        {
            $param = PHP_EOL . "\t\t" . $param;
        }
        
        $type = strtoupper($type);

        if($message instanceof Exception) {
            $message = 'Exception thrown with message: ' . $message->getMessage();
        }

        $message = ucfirst($message);

        if( is_string($method) ) {
            $message = sprintf('In %s: %s', $method, $message);
        }

        $debugLine = sprintf('[%s] %s', $type, $message);

        if($this->isDebug() && $message) {
            $this->logger->debug(sprintf('%s', implode('', $extendedDebugData)));
            $this->logger->debug($debugLine);
        }
        
        return $debugLine;
    }

    /**
     * @return boolean
     */
    public function isDebug()
    {
        return (boolean) $this->scopeConfig->getValue('payment/bitpay/debug');
    }

    /**
     * Returns Transaction Speed value.
     *
     * @return string
     */
    public function getTransactionSpeed()
    {
        return $this->scopeConfig->getValue('payment/bitpay/speed');
    }

    /**
     * Returns true if Transaction Speed has been configured
     *
     * @return boolean
     */
    public function hasTransactionSpeed()
    {
        $speed = $this->getTransactionSpeed();

        return !empty($speed);
    }

    /**
     * Returns the network name.
     *
     * @return mixed
     */
    public function getNetwork() {
        return $this->scopeConfig->getValue('payment/bitpay/network');
    }

    /**
     * Returns the token.
     *
     * @return string
     */
    public function getToken() {
        return (string) $this->scopeConfig->getValue('payment/bitpay/token');
    }

    /**
     * Determines if the full screen option is enabled.
     *
     * @return bool
     */
    public function isFullScreen() {
        return (bool) $this->scopeConfig->getValue('payment/bitpay/fullscreen');
    }

    /**
     * Determines if the Network is Testnet.
     *
     * @return bool
     */
    public function isTestnetNetwork() {
        return $this->getNetwork() === 'testnet';
    }

    /**
     * Determines if the Network is Livenet.
     *
     * @return bool
     */
    public function isLivenetNetwork() {
        return $this->getNetwork() === 'livenet';
    }

    /**
     * Returns BitPay Service instance.
     *
     * @return BitPayService
     */
    public function getBitPayService() {
        return ObjectManager::getInstance()->get('\Bitpay\Core\Model\BitPayService');
    }

    /**
     * Returns BitPay Factory instance.
     *
     * @return BitPayFactory
     */
    public function getBitPayFactory() {
        return ObjectManager::getInstance()->get('\Bitpay\Core\Model\BitPayFactory');
    }

    /**
     * Returns Magento Config instance.
     *
     * @return Config
     */
    public function getConfig() {
        return $this->config;
    }

    /**
     * Returns the URL where the IPN's are sent
     *
     * @return string
     */
    public function getNotificationUrl()
    {
        return $this->storeManager->getStore()->getUrl($this->scopeConfig->getValue('payment/bitpay/notification_url'));
    }

    /**
     * Returns the URL where customers are redirected
     *
     * @return string
     */
    public function getRedirectUrl()
    {
        return $this->storeManager->getStore()->getUrl($this->scopeConfig->getValue('payment/bitpay/redirect_url'));
    }

    /**
     * Returns the store name as label for BitPay client.
     *
     * @return string
     */
    public function getStoreNameAsLabel() {
        /* @var $store \Magento\Store\Model\StoreManagerInterface */
        $store = ObjectManager::getInstance()->get('\Magento\Store\Model\StoreManagerInterface');
        $label = preg_replace('/[^a-zA-Z0-9 ]/', '', $store->getStore()->getName());

        return (string) substr('Magento ' . $label, 0, 59);
    }
    
    /**
     * Returns the extension version.
     *
     * @return string
     */
    public function getExtensionVersion()
    {
        $moduleCode = 'Bitpay_Core';
        $moduleInfo = $this->_moduleList->getOne($moduleCode);
        return $moduleInfo['setup_version'];
    }
    
    /**
     * Returns the Magento version.
     *
     * @return string
     */
    public function getMagentoVersion()
    {
         return $this->productMetadata->getVersion();
    }
}
