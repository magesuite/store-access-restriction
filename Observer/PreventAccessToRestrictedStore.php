<?php

namespace MageSuite\StoreAccessRestriction\Observer;

class PreventAccessToRestrictedStore implements \Magento\Framework\Event\ObserverInterface
{
    protected \Magento\Framework\UrlInterface $url;

    protected \Magento\Framework\App\ActionFlag $actionFlag;

    protected \Magento\Framework\App\Request\Http $request;

    protected \Magento\Store\Model\StoreManager $storeManager;

    protected \Magento\Framework\App\ResponseInterface $response;

    protected \MageSuite\StoreAccessRestriction\Service\StoreRestrictionValidator $storeRestrictionValidator;

    protected  \MageSuite\StoreAccessRestriction\Service\CmsPagesProvider $cmsPagesProvider;

    public function __construct(
        \Magento\Framework\UrlInterface $url,
        \Magento\Framework\App\ActionFlag $actionFlag,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Store\Model\StoreManager $storeManager,
        \Magento\Framework\App\ResponseInterface $response,
        \MageSuite\StoreAccessRestriction\Service\StoreRestrictionValidator $storeRestrictionValidator,
        \MageSuite\StoreAccessRestriction\Service\CmsPagesProvider $cmsPagesProvider
    ) {
        $this->url = $url;
        $this->actionFlag = $actionFlag;
        $this->request = $request;
        $this->storeManager = $storeManager;
        $this->response = $response;
        $this->storeRestrictionValidator = $storeRestrictionValidator;
        $this->cmsPagesProvider = $cmsPagesProvider;
    }

    public function execute(\Magento\Framework\Event\Observer $observer): void
    {
        if (!$this->storeRestrictionValidator->isStoreAccessRestrictionEnabled()) {
            return;
        }

        if ($this->storeRestrictionValidator->isRedirectToAnotherStore()) {
            return;
        }

        if ($this->storeRestrictionValidator->canAccessStore()) {
            $this->response->setNoCacheHeaders();
            return;
        }

        $currentStore = $this->storeManager->getStore();
        if ($currentStore->getTargetPageId() && ($currentStore->getTargetPageId() > 0)) {
            $this->redirectToCmsPage($currentStore);
        } else {
            $this->redirectTo404();
        }
    }

    protected function redirectTo404(): void
    {
        $noRoutePath = 'noroute';
        $pathInfo = $this->request->getPathInfo();

        if (trim($pathInfo, '/') == $noRoutePath) {
            return;
        }

        $url = $this->storeManager->getDefaultStoreView()->getBaseUrl() . $noRoutePath;
        $this->actionFlag->set('', \Magento\Framework\App\Action\Action::FLAG_NO_DISPATCH, true);
        $this->response->setRedirect($url)->sendResponse();
    }

    protected function redirectToCmsPage(\Magento\Store\Model\Store $currentStore): void
    {
        $cmsPageId = $currentStore->getTargetPageId();
        $cmsPage = $this->cmsPagesProvider->getCmsPage($cmsPageId);

        if (!$cmsPage) {
            $this->redirectTo404();
        }

        $targetStoreId = $this->getTargetStoreId($currentStore, $cmsPage);

        $url = $this->storeManager->getStore($targetStoreId)->getUrl($cmsPage->getIdentifier());
        $this->actionFlag->set('', \Magento\Framework\App\Action\Action::FLAG_NO_DISPATCH, true);
        $this->response->setRedirect($url)->sendResponse();
    }

    public function getTargetStoreId(\Magento\Store\Model\Store $currentStore, \Magento\Cms\Model\Page $cmsPage): int
    {
        $defaultStoreId = $this->storeManager->getDefaultStoreView()->getId();

        $cmsPageStores = $cmsPage->getStores();

        if (empty($cmsPageStores)) {
            return $defaultStoreId;
        }

        if (($cmsPageStores[0] == 0) || in_array($currentStore->getId(), $cmsPageStores)) {
            $storeId = $currentStore->getId();
        } else {
            $storeId = $cmsPageStores[0];
        }

        return $storeId;
    }
}
