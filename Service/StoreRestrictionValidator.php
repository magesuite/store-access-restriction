<?php

namespace MageSuite\StoreAccessRestriction\Service;

class StoreRestrictionValidator
{
    public const RESTRICTION_BYPASS_COOKIE_NAME = 'STORE_RESTRICTION_BYPASS';

    /**
     * @var \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress
     */
    protected $remoteAddress;

    /**
     * @var \Magento\Framework\Stdlib\CookieManagerInterface
     */
    protected $cookieManager;

    /**
     * @var \Magento\Store\Model\StoreManager
     */
    protected $storeManager;

    /**
     * @var \Magento\Framework\App\Request\Http
     */
    protected $request;

    protected $currentStore = null;

    protected \Psr\Log\LoggerInterface $logger;

    public function __construct(
        \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress $remoteAddress,
        \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager,
        \Magento\Store\Model\StoreManager $storeManager,
        \Magento\Framework\App\Request\Http $request,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->remoteAddress = $remoteAddress;
        $this->cookieManager = $cookieManager;
        $this->storeManager = $storeManager;
        $this->request = $request;
        $this->logger = $logger;
    }

    public function isStoreAccessRestrictionEnabled(): bool
    {
        $currentStore = $this->getCurrentStore();
        return (bool)$currentStore->getIsAccessRestricted();
    }

    public function canAccessStore(): bool
    {
        return $this->isRequestFromAllowedIp() || $this->isRequestWithBypassParam() || $this->isRequestWithRestrictionBypassCookie();
    }

    protected function isRequestFromAllowedIp(): bool
    {
        $remoteAddress = $this->remoteAddress->getRemoteAddress();
        $allowedIps = explode(',', $this->getCurrentStore()->getAllowedIps());

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
