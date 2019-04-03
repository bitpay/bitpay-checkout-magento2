<?php
/**
 * @license Copyright 2011-2014 BitPay Inc., MIT License
 * 
 */
namespace Bitpay\Core\Model\Config\Source;

/**
 * Used to display bitcoin networks
 */
class Network implements \Magento\Framework\Option\ArrayInterface
{
    const NETWORK_LIVENET = 'livenet';
    const NETWORK_TESTNET = 'testnet';

    /**
     * Possible environment types
     * 
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => self::NETWORK_LIVENET,
                'label' => ucwords(self::NETWORK_LIVENET),
            ],
            [
                'value' => self::NETWORK_TESTNET,
                'label' => ucwords(self::NETWORK_TESTNET)
            ]
        ];
    }
}
