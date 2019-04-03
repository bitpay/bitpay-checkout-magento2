<?php
namespace Bitpay\Core\Model;

use Bitpay\Core\Helper\Data;
use Bitpay\Crypto\OpenSSLExtension;
use Bitpay\Key;
use Bitpay\KeyInterface;
use Bitpay\Storage\StorageInterface;
use Magento\Config\Model\ResourceModel\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\DeploymentConfig\Reader;
use Magento\Framework\Config\File\ConfigFilePool;

class MagentoStorage implements StorageInterface {

    /**
     * @var OpenSSLExtension
     */
	protected $crypt;

    /**
     * @var Config
     */
	protected $config;

    /**
     * @var Data
     */
	protected $helper;

    /**
     * @var KeyInterface[]
     */
	protected $storedKeys = [];

    /**
     * Initialization Vector
     */
    const IV = '1234567890123456';

    /**
     * MagentoStorage constructor.
     */
	public function __construct() {
	    $this->crypt = new OpenSSLExtension();
    }

    /**
     * Returns Crypt Key from Magento environment. It will be truncated to the 24 chars.
     *
     * @return string
     */
    protected function getKey() {
	    /* @var $reader Reader */
        $reader = ObjectManager::getInstance()->get('\Magento\Framework\App\DeploymentConfig\Reader');

        return (string) substr($reader->load(ConfigFilePool::APP_ENV)['crypt']['key'], 0, 24);
    }

    /**
	 * @inheritdoc
	 */
	public function persist(KeyInterface $key) {
        $this->config   = ObjectManager::getInstance()->get('\Magento\Config\Model\ResourceModel\Config');
        $this->helper   = ObjectManager::getInstance()->get('\Bitpay\Core\Helper\Data');

	    /* @var $key Key */
		$data           = base64_encode(serialize($key));
		$encryptedData  = $this->crypt->encrypt($data, $this->getKey(), self::IV);

        $this->storedKeys[ $key->getId() ] = $key;

		try {
            $this->config->saveConfig($key->getId(), $encryptedData, ScopeConfigInterface::SCOPE_TYPE_DEFAULT, 0);

            $this->helper->logInfo('Key ' . $key->getId() . ' has been stored.', __METHOD__);
        }
        catch(\Exception $e) {
            $this->helper->logError($e, __METHOD__);

            throw $e;
        }
	}

	/**
	 * @inheritdoc
	 */
	public function load($id) {
	    if( array_key_exists($id, $this->storedKeys) ) {
	        return $this->storedKeys[$id];
        }

        $this->config   = ObjectManager::getInstance()->get('\Magento\Framework\App\Config\ScopeConfigInterface');
        $this->helper   = ObjectManager::getInstance()->get('\Bitpay\Core\Helper\Data');

        $entity = $this->config->getValue($id);

        $this->helper->logInfo('ID: ' . $id . ', entity: ' . $entity);

		/**
		 * Not in database
		 */
		if (false === isset($entity) || true === empty($entity)) {
            $debugLine = sprintf('The id of %s did not return the store config parameter because it was not found in the database.', $id);

            $this->helper->logInfo($debugLine, __METHOD__);

			throw new \Exception($debugLine);
		}

		$decodedEntity = $this->crypt->decrypt($entity, $this->getKey(), self::IV);

		if (false === isset($decodedEntity) || true === empty($decodedEntity)) {
            $debugLine = sprintf('The id of %s could not decrypt & unserialize the entity %s', $id, $entity);

            $this->helper->logInfo($debugLine, __METHOD__);

            throw new \Exception($debugLine);
		}

		$unSerialized   = unserialize(base64_decode($decodedEntity));
        $debugLine      = sprintf('The id of %s successfully decrypted & unserialized the entity %s', $id, $entity);

        $this->helper->logInfo($debugLine, __METHOD__);

		return $unSerialized;
	}

}
