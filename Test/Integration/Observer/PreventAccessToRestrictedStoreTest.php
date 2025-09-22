<?php

declare(strict_types=1);

namespace MageSuite\StoreAccessRestriction\Test\Integration\Observer;

class PreventAccessToRestrictedStoreTest extends \Magento\TestFramework\TestCase\AbstractController
{
    protected ?\Magento\Framework\ObjectManagerInterface $objectManager;
    protected ?\Magento\Store\Model\StoreManagerInterface $storeManager;
    protected ?\Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder;
    protected ?\Magento\Cms\Api\PageRepositoryInterface $pageRepositoryInterface;

    public function setUp(): void
    {
        parent::setUp();

        $this->objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
        $this->storeManager = $this->objectManager->create(\Magento\Store\Model\StoreManagerInterface::class);
        $this->searchCriteriaBuilder = $this->objectManager->create(\Magento\Framework\Api\SearchCriteriaBuilder::class);
        $this->pageRepositoryInterface = $this->objectManager->create(\Magento\Cms\Api\PageRepositoryInterface::class);
    }

    /**
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     * @magentoDataFixture MageSuite_StoreAccessRestriction::Test/Integration/_files/cms_page.php
     */
    public function testItRedirectsToTheChosenCmsPageOnRestrictedStore(): void
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
     * @magentoDataFixture MageSuite_StoreAccessRestriction::Test/Integration/_files/cms_page.php
     * @magentoDataFixture MageSuite_StoreAccessRestriction::Test/Integration/_files/stores.php
     */
    public function testItRedirectsToTheChosenCmsPageOnAnotherStore(): void
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

    public function getCmsPage(): \Magento\Cms\Api\Data\PageInterface
    {
        $searchCriteria = $this->searchCriteriaBuilder->addFilter('identifier', 'target-cms-page', 'eq')->create();
        $pages = $this->pageRepositoryInterface->getList($searchCriteria)->getItems();

        return array_pop($pages);
    }
}
