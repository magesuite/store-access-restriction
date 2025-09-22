<?php

declare(strict_types=1);

namespace MageSuite\StoreAccessRestriction\Service;

class StoreRestrictionValidator
{
    public const RESTRICTION_BYPASS_COOKIE_NAME = 'STORE_RESTRICTION_BYPASS';
    public const CMS_PAGE_ACTION = 'cms_index_index';

    protected \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress $remoteAddress;
    protected \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager;
    protected \Magento\Store\Model\StoreManager $storeManager;
    protected \Magento\Framework\App\Request\Http $request;
    protected \Psr\Log\LoggerInterface $logger;
    protected \MageSuite\StoreAccessRestriction\Service\CmsPagesProvider $cmsPagesProvider;

    protected ?\Magento\Store\Api\Data\StoreInterface $currentStore = null;

    public function __construct(
        \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress $remoteAddress,
        \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager,
        \Magento\Store\Model\StoreManager $storeManager,
        \Magento\Framework\App\Request\Http $request,
        \Psr\Log\LoggerInterface $logger,
        \MageSuite\StoreAccessRestriction\Service\CmsPagesProvider $cmsPagesProvider
    ) {
        $this->remoteAddress = $remoteAddress;
        $this->cookieManager = $cookieManager;
        $this->storeManager = $storeManager;
        $this->request = $request;
        $this->logger = $logger;
        $this->cmsPagesProvider = $cmsPagesProvider;
    }

    public function isStoreAccessRestrictionEnabled(): bool
    {
        $currentStore = $this->getCurrentStore();

        return (bool)$currentStore->getIsAccessRestricted();
    }

    public function canAccessStore(): bool
    {
        return $this->isRequestFromAllowedIp() || $this->isRequestWithBypassParam() || $this->isRequestWithRestrictionBypassCookie() || $this->isTargetPage();
    }

    protected function isRequestFromAllowedIp(): bool
    {
        $remoteAddress = $this->remoteAddress->getRemoteAddress();
        $allowedIps = explode(',', (string)$this->getCurrentStore()->getAllowedIps());

        return in_array($remoteAddress, $allowedIps, true);
    }

    protected function isRequestWithBypassParam(): bool
    {
        $bypassParamValue = $this->request->getParam('bypass_store_restriction');
        if ($bypassParamValue === null) {
            return false;
        }

        return $this->getCurrentStore()->getRestrictionBypassCookieValue() == $bypassParamValue;
    }

    protected function isRequestWithRestrictionBypassCookie(): bool
    {
        $cookie = $this->cookieManager->getCookie(self::RESTRICTION_BYPASS_COOKIE_NAME);
        if (empty($cookie)) {
            return false;
        }

        $expectedCookieValue = $this->getCurrentStore()->getRestrictionBypassCookieValue();
        return $cookie == $expectedCookieValue;
    }

    protected function isTargetPage(): bool
    {
        $currentStore = $this->getCurrentStore();
        $cmsPageId = $currentStore->getTargetPageId();
        if (!$cmsPageId || ($cmsPageId == 0)) {
            return false;
        }

        $cmsPage = $this->cmsPagesProvider->getCmsPage((int)$cmsPageId);

        if (!$cmsPage) {
            return false;
        }

        $originalPathInfo = $this->request->getOriginalPathInfo() ?? '';
        $cmsPageIdentifier = $cmsPage->getIdentifier() ?? '';

        if (trim($originalPathInfo, '/') != trim($cmsPageIdentifier, '/')) {
            return false;
        }

        return true;
    }

    public function isRedirectToAnotherStore(): bool
    {
        $currentStore = $this->getCurrentStore();
        $storeParam = $this->request->getParam('___store');

        if ($storeParam && ($storeParam != $currentStore->getCode())) {
            return true;
        }

        return false;
    }

    protected function getCurrentStore(): \Magento\Store\Api\Data\StoreInterface
    {
        if (!$this->currentStore) {
            try {
                $this->currentStore = $this->storeManager->getStore();
            } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                $this->logger->error($e->getMessage());
            }
        }

        return $this->currentStore;
    }
}
