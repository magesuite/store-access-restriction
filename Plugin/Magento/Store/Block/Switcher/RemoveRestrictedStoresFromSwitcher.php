<?php

namespace MageSuite\StoreAccessRestriction\Plugin\Magento\Store\Block\Switcher;

class RemoveRestrictedStoresFromSwitcher
{
    protected $stores = [];

    public function afterGetRawStores(
        \Magento\Store\Block\Switcher $subject,
        $rawStores
    ) {
        if ($this->stores) {
            return $this->stores;
        }

        foreach ($rawStores as $groupId => $group) {
            foreach ($group as $storeId => $store) {
                if (!$store->getIsAccessRestricted()) {
                    $this->stores[$groupId][$storeId] = $store;
                }
            }
        }

        return $this->stores;
    }
}
