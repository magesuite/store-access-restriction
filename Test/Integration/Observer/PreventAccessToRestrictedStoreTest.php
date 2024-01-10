<?php

namespace MageSuite\StoreAccessRestriction\Test\Integration\Observer;

class PreventAccessToRestrictedStoreTest extends \Magento\TestFramework\TestCase\AbstractController
{
    protected $objectManager;

    protected $storeManager;

    protected $searchCriteriaBuilder;

    protected $pageRepositoryInterface;

    public function setUp(): void
    {
        parent::setUp();
        $this->objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
        $this->storeManager = $this->objectManager->create(\Magento\Store\Model\StoreManagerInterface::class);
        $this->searchCriteriaBuilder = $this->objectManager->create(\Magento\Framework\Api\SearchCriteriaBuilder::class);
        $this->pageRepositoryInterface = $this->objectManager->create( \Magento\Cms\Api\PageRepositoryInterface::class);
    }

    /**
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     * @magentoDataFixture loadCmsPageFixture
     */
    public function testItRedirectsToTheChosenCmsPageOnRestrictedStore()
    {
        $currentStore = $this->storeManager->getStore();

        $cmsPage = $this->getCmsPage();
        $currentStore->setTargetPageId($cmsPage->getId());
        $currentStore->setIsAccessRestricted(1);
        $currentStore->save();

        $this->dispatch('/');
        $this->assertEquals(302, $this->getResponse()->getStatusCode());
        $this->assertEquals(
            'http://localhost/index.php/target-cms-page/',
            $this->getResponse()->getHeaders()->get('Location')->getUri()
        );
    }

    /**
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     * @magentoDataFixture loadCmsPageFixture
     * @magentoDataFixture loadStoresFixture
     */
    public function testItRedirectsToTheChosenCmsPageOnAnotherStore()
    {
        $currentStore = $this->storeManager->getStore();
        $itStore = $this->storeManager->getStore('it');

        $cmsPage = $this->getCmsPage();
        $cmsPage->setStoreId($itStore->getId());
        $cmsPage->save();

        $currentStore->setTargetPageId($cmsPage->getId());
        $currentStore->setIsAccessRestricted(1);
        $currentStore->save();

        $this->dispatch('/');
        $this->assertEquals(302, $this->getResponse()->getStatusCode());
        $this->assertEquals(
            'http://localhost/index.php/target-cms-page/?___store=it',
            $this->getResponse()->getHeaders()->get('Location')->getUri()
        );
    }

    public function getCmsPage()
    {
        $searchCriteria = $this->searchCriteriaBuilder->addFilter('identifier', 'target-cms-page','eq')->create();
        $pages = $this->pageRepositoryInterface->getList($searchCriteria)->getItems();

        return array_pop($pages);
    }

    public static function loadStoresFixture()
    {
        include __DIR__ . "/../_files/stores.php";
    }

    public static function loadCmsPageFixture()
    {
        include __DIR__ . "/../_files/cms_page.php";
    }
}
