<?php

declare(strict_types=1);

namespace MageSuite\StoreAccessRestriction\Observer;

class SaveCookieFromParam implements \Magento\Framework\Event\ObserverInterface
{
    protected \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager;
    protected \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory $cookieMetadataFactory;

    public function __construct(
        \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager,
        \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory $cookieMetadataFactory
    ) {
        $this->cookieManager = $cookieManager;
        $this->cookieMetadataFactory = $cookieMetadataFactory;
    }

    public function execute(\Magento\Framework\Event\Observer $observer): void
    {
        method_exists($observer->getControllerAction(), 'getRequest')
        && is_callable([$observer->getControllerAction(), 'getRequest']) ?
            $cookieParamValue = $observer->getControllerAction()->getRequest()->getParam('bypass_store_restriction') :
            $cookieParamValue = null;

        if ($cookieParamValue !== null) {
            $this->setBypassRestrictionCookie($cookieParamValue);
        }
    }

    protected function setBypassRestrictionCookie(string $value): void
    {
        $publicCookieMetadata = $this->cookieMetadataFactory->createPublicCookieMetadata();
        $publicCookieMetadata->setDurationOneYear();
        $publicCookieMetadata->setPath('/');
        $publicCookieMetadata->setHttpOnly(false);

        $this->cookieManager->setPublicCookie(
            \MageSuite\StoreAccessRestriction\Service\StoreRestrictionValidator::RESTRICTION_BYPASS_COOKIE_NAME,
            $value,
            $publicCookieMetadata
        );
    }
}
