<?php

$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
$storeManager = $objectManager->get(\Magento\Store\Model\StoreManager::class);

$defaultStore = $storeManager->getStore();
$defaultStore->setData('is_access_restricted', 0);
$defaultStore->setData('restriction_bypass_cookie_value', null);
$defaultStore->setData('allowed_ips', null);
$defaultStore->save();
