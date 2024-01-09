<?php

$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
$pageFactory = $objectManager->create(\Magento\Cms\Model\PageFactory::class);

$page = $pageFactory->create();
$page->setTitle('Target CMS Page')
    ->setIdentifier('target-cms-page')
    ->setIsActive(true)
    ->setPageLayout('1column')
    ->setStores(array(0))
    ->setContent('Lorem ipsum dolor sit amet, consectetur adipiscing elit.')
    ->save();
