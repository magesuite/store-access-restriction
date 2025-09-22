<?php

declare(strict_types=1);

namespace MageSuite\StoreAccessRestriction\Plugin\MageSuite\SeoHreflang\ViewModel\Hreflang;

class DisableHreflangForRestrictedStore
{
    public function afterIsApplicable(
        \MageSuite\SeoHreflang\ViewModel\Hreflang $subject,
        bool $result,
        \MageSuite\SeoHreflang\Model\Entity\EntityInterface $entity,
        \Magento\Store\Api\Data\StoreInterface $store
    ) {
        if (!$result) {
            return $result;
        }

        if ($store->getIsAccessRestricted()) {
            return false;
        }

        return $result;
    }
}
