<?php

namespace MageSuite\StoreAccessRestriction\Observer;

class AddFieldsToStoreEditView implements \Magento\Framework\Event\ObserverInterface
{
    protected \Magento\Framework\Registry $registry;

    protected \MageSuite\StoreAccessRestriction\Service\CmsPagesProvider $cmsPagesProvider;

    public function __construct(
        \Magento\Framework\Registry $registry,
        \MageSuite\StoreAccessRestriction\Service\CmsPagesProvider $cmsPagesProvider
    ) {
        $this->registry = $registry;
        $this->cmsPagesProvider = $cmsPagesProvider;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Backend\Block\System\Store\Edit\AbstractForm $block */
        $block = $observer->getBlock();
        $storeModel = $this->registry->registry('store_data');

        /** @var \Magento\Framework\Data\Form $form */
        $form = $block->getForm();
        $fieldset = $form->getForm()->getElement('store_fieldset');
        if (empty($fieldset)) {
            return;
        }

        $fieldset->addField(
            'is_access_restricted',
            'select',
            [
                'name' => 'store[is_access_restricted]',
                'label' => __('Is Access Restricted'),
                'value' => $storeModel->getIsAccessRestricted(),
                'options' => [0 => __('No'), 1 => __('Yes')],
                'class' => 'cs-csfeature__logo',
                'note' => 'When this option is enabled the store view can be access only by user that have their IP on "Allowed IPs" or specific cookie in the browser.'
            ]
        );

        if ($storeModel->getIsAccessRestricted()) {
            $fieldset = $form->getForm()->getElement('store_fieldset');
            $fieldset->addField(
                'allowed_ips',
                'text',
                [
                    'name' => 'store[allowed_ips]',
                    'label' => __('Allowed IPs'),
                    'value' => $storeModel->getAllowedIps(),
                    'class' => 'cs-csfeature__logo',
                    'note' => 'Use a comma to separated list of IP addresses. E.g.: "52.30.230.10,91.21.56.87"'
                ]
            );

            $fieldset = $form->getForm()->getElement('store_fieldset');
            $fieldset->addField(
                'restriction_bypass_cookie_value',
                'text',
                [
                    'name' => 'store[restriction_bypass_cookie_value]',
                    'label' => __('Restriction Bypass Cookie Value'),
                    'value' => $storeModel->getRestrictionBypassCookieValue(),
                    'required' => false,
                    'class' => 'cs-csfeature__logo',
                ]
            );

            $cmsPages = $this->cmsPagesProvider->getCmsPages();
            $cmsPages = [0 => __('No Route Page')] + $cmsPages;

            $fieldset = $form->getForm()->getElement('store_fieldset');
            $fieldset->addField(
                'target_page_id',
                'select',
                [
                    'name' => 'store[target_page_id]',
                    'label' => __('Target Page'),
                    'value' => $storeModel->getTargetPageId(),
                    'options' => $cmsPages,
                    'required' => false,
                    'class' => 'cs-csfeature__logo',
                    'note' => 'The field allows for configuring the target page the customer will be redirected to when accessing a restricted store. \'No Route Page\' is the default location \noroute", which will be used when no other CMS page is selected.'
                ]
            );
        }
    }
}
