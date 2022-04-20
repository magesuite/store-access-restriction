<?php

namespace MageSuite\StoreAccessRestriction\Observer;

class PreventAccessToRestrictedStore implements \Magento\Framework\Event\ObserverInterface
{
    const COOKIE_PARAM_NAME = 'bypass_store_restriction';

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $url;

    /**
     * @var \Magento\Framework\App\ActionFlag
     */
    protected $actionFlag;

    /**
     * @var \Magento\Store\Model\StoreManager
     */
    protected $storeManager;

    /**
     * @var \MageSuite\StoreAccessRestriction\Service\StoreRestrictionValidator
     */
    protected $storeRestrictionValidator;

    public function __construct(
        \Magento\Framework\UrlInterface $url,
        \Magento\Framework\App\ActionFlag $actionFlag,
        \Magento\Store\Model\StoreManager $storeManager,
        \MageSuite\StoreAccessRestriction\Service\StoreRestrictionValidator $storeRestrictionValidator
    ) {
        $this->url = $url;
        $this->actionFlag = $actionFlag;
        $this->storeManager = $storeManager;
        $this->storeRestrictionValidator = $storeRestrictionValidator;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (!$this->storeRestrictionValidator->isStoreAccessRestrictionEnabled()) {
            return;
        }

        /** @var \Magento\Framework\App\Response\Http $response */
        $response = $observer->getControllerAction()->getResponse();
        if ($this->storeRestrictionValidator->canAccessStore()) {
            $response->setNoCacheHeaders();
            return;
        }

        $this->redirectTo404($response);
    }

    protected function redirectTo404($response)
    {
        $url = $this->storeManager->getDefaultStoreView()->getBaseUrl() . 'noroute';
        $this->actionFlag->set('', \Magento\Framework\App\Action\Action::FLAG_NO_DISPATCH, true);
        $response->setRedirect($url)->sendResponse();
    }
}
