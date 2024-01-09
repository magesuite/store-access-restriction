<?php

namespace MageSuite\StoreAccessRestriction\Service;

class CmsPagesProvider
{
    protected \Magento\Cms\Model\ResourceModel\Page\CollectionFactory $pageCollectionFactory;

    public function __construct(
        \Magento\Cms\Model\ResourceModel\Page\CollectionFactory $pageCollectionFactory
    ) {
        $this->pageCollectionFactory = $pageCollectionFactory;
    }

    public function getCmsPage(?int $id): ?\Magento\Cms\Model\Page
    {
        if (!$id) {
            return null;
        }

        $collection = $this->pageCollectionFactory->create();
        $collection->addFieldToFilter('page_id', ['eq' => $id]);

        if ($cmsPage = $collection->getFirstItem()) {
            return $cmsPage;
        }

        return null;
    }

    public function getCmsPages(): array
    {
        $collection = $this->pageCollectionFactory->create();
        $collection->addFieldToFilter('is_active', ['eq' => 1]);

        $cmsPages = [];
        foreach ($collection->getItems() as $cmsPage) {
            $cmsPages[$cmsPage->getId()] = $cmsPage->getTitle();
        }

        return $cmsPages;
    }
}
