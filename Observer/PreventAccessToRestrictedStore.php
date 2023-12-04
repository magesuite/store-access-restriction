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

    public function __construct(
        \Magento\Framework\UrlInterface $url,
        \Magento\Framework\App\ActionFlag $actionFlag,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Store\Model\StoreManager $storeManager,
        \Magento\Framework\App\ResponseInterface $response,
        \MageSuite\StoreAccessRestriction\Service\StoreRestrictionValidator $storeRestrictionValidator
    ) {
        $this->url = $url;
        $this->actionFlag = $actionFlag;
        $this->request = $request;
        $this->storeManager = $storeManager;
        $this->response = $response;
        $this->storeRestrictionValidator = $storeRestrictionValidator;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (!$this->storeRestrictionValidator->isStoreAccessRestrictionEnabled()) {
            return;
        }

        if ($this->storeRestrictionValidator->canAccessStore()) {
            $this->response->setNoCacheHeaders();
            return;
        }

        $this->redirectTo404();
    }

    protected function redirectTo404()
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
}
