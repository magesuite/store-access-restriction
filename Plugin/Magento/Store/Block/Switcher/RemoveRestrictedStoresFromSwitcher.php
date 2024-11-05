<?php

namespace MageSuite\StoreAccessRestriction\Plugin\Magento\Store\Block\Switcher;

class RemoveRestrictedStoresFromSwitcher
{
    protected $stores = [];

    public function afterGetRawStores(
        \Magento\Store\Block\Switcher $subject,
        $rawStores
    ) {
        $subjectClass = get_class($subject);

        if (isset($this->stores[$subjectClass])) {
            return $this->stores[$subjectClass];
        }

        $this->stores[$subjectClass] = [];

        foreach ($rawStores as $groupId => $group) {
            foreach ($group as $storeId => $store) {
                if ($store->getIsAccessRestricted()) {
                    continue;
                }

                $this->stores[$subjectClass][$groupId][$storeId] = $store;
            }
        }

        return $this->stores[$subjectClass];
    }
}
