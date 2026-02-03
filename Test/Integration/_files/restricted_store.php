<?php

$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
$storeManager = $objectManager->get(\Magento\Store\Model\StoreManager::class);

$defaultStore = $storeManager->getStore();
$defaultStore->setData('is_access_restricted', 1);
$defaultStore->setData('restriction_bypass_cookie_value', 'stored_secret_value');
$defaultStore->setData('allowed_ips', '1.1.1.1,2.2.2.2');
$defaultStore->save();
