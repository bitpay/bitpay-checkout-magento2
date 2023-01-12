<?php

use Bitpay\BPCheckout\Model\Transaction;
use Magento\TestFramework\Helper\Bootstrap;

$objectManager = Bootstrap::getObjectManager();

/** @var Transaction $transaction */
$transaction = $objectManager->get(Transaction::class);

$transaction->setData([
    'order_id' => '100000001',
    'transaction_id' => 'VjvZuvsWT36tzYX65ZXk4xq',
    'transaction_status' => 'new'
]);
$transaction->save();
