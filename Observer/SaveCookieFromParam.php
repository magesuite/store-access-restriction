<?php

namespace MageSuite\StoreAccessRestriction\Observer;

class SaveCookieFromParam implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Magento\Framework\Stdlib\CookieManagerInterface
     */
    protected $cookieManager;

    /**
     * @var \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory
     */
    protected $cookieMetadataFactory;

    public function __construct(
        \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager,
        \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory $cookieMetadataFactory
    ) {
        $this->cookieManager = $cookieManager;
        $this->cookieMetadataFactory = $cookieMetadataFactory;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $cookieParamValue = $observer->getControllerAction()->getRequest()->getParam('bypass_store_restriction');
        if ($cookieParamValue !== null) {
            $this->setBypassRestrictionCookie($cookieParamValue);
        }
    }

    protected function setBypassRestrictionCookie($value)
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
