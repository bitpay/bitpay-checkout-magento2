<?php
/**
 * @license Copyright 2011-2014 BitPay Inc., MIT License
 * 
 */

/**
 * This is used to display php extensions and if they are installed or not
 */
namespace Bitpay\Core\Block\Adminhtml\System\Config\Form\Field;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Data\Form\Element\AbstractElement;

class Extension extends \Magento\Config\Block\System\Config\Form\Field
{
    /**
     * Render element html
     *
     * @param AbstractElement $element
     * @return string
     * @throws \Exception
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        if (false === isset($element) || true === empty($element)) {
            /* @var $helper \Bitpay\Core\Helper\Data */
            $helper = ObjectManager::getInstance()->get('Bitpay\Core\Helper\Data');
            $message = $helper->logError('Missing or invalid $element parameter passed to function.', __METHOD__);
            throw new \Exception($message);
        }

        $config = $element->getFieldConfig();
        $phpExtension = isset($config['php_extension']) ? $config['php_extension'] : 'null';
       
        if (true === in_array($phpExtension, get_loaded_extensions())) {
            return 'Installed';
        }

        return 'Not Installed';
    }
}
