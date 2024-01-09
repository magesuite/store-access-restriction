<?php

$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();

$stores = [
    [
        'code' => 'it',
        'website_id' => '1',
        'group_id' => '1',
        'name' => 'IT Store',
        'sort_order' => '0',
        'is_active' => '1'
    ]
];

foreach ($stores as $storeData) {
    /** @var $store \Magento\Store\Model\Store */
    $store = $objectManager->create(\Magento\Store\Model\Store::class);
    if (!$store->load($storeData['code'], 'code')->getId()) {
        $store->setData($storeData);
        $store->save();
    }
}
