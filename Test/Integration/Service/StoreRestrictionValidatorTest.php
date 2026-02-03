<?php

declare(strict_types=1);

namespace MageSuite\StoreAccessRestriction\Test\Integration\Service;

class StoreRestrictionValidatorTest extends \Magento\TestFramework\TestCase\AbstractController
{
    protected ?\Magento\TestFramework\ObjectManager $objectManager;
    protected ?\Magento\Framework\Stdlib\CookieManagerInterface $cookieManager;
    protected ?\Magento\Store\Model\StoreManagerInterface $storeManager;
    protected ?\Magento\Framework\HTTP\PhpEnvironment\RemoteAddress $remoteAddress;

    public function setUp(): void
    {
        parent::setUp();

        $this->objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
        $this->cookieManager = $this->objectManager->create(\Magento\Framework\Stdlib\CookieManagerInterface::class);
        $this->storeManager = $this->objectManager->create(\Magento\Store\Model\StoreManagerInterface::class);

        $this->remoteAddress = $this->getMockBuilder(\Magento\Framework\HTTP\PhpEnvironment\RemoteAddress::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testItReturnsNotEmptyResponseBodyAnd200ResponseCodeForStoreViewWithDisabledAccessRestriction(): void
    {
        $this->dispatch('/');
        $this->assertNotEmptyResponseBodyAnd200ResponseCode();
    }

    /**
     * @magentoDataFixture MageSuite_StoreAccessRestriction::Test/Integration/_files/restricted_store.php
     */
    public function testItReturnsNotEmptyResponseBodyAnd200ResponseCodeForStoreViewWithEnabledAccessRestrictionForWhitelistedIpAddress(): void
    {
        $this->remoteAddress->method('getRemoteAddress')->willReturn('1.1.1.1');
        $this->objectManager->addSharedInstance($this->remoteAddress, \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress::class);

        $this->dispatch('/');
        $this->assertNotEmptyResponseBodyAnd200ResponseCode();
    }

    /**
     * @magentoDataFixture MageSuite_StoreAccessRestriction::Test/Integration/_files/restricted_store.php
     */
    public function testItReturnsEmptyResponseBodyAnd302ResponseCodeForStoreViewWithEnabledAccessRestrictionForNotWhitelistedIpAddress(): void
    {
        $this->remoteAddress->method('getRemoteAddress')->willReturn('3.3.3.3');
        $this->objectManager->addSharedInstance($this->remoteAddress, \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress::class);

        $this->dispatch('/');
        $this->assertEmptyResponseBodyAnd302ResponseCode();
    }

    /**
     * @magentoDataFixture MageSuite_StoreAccessRestriction::Test/Integration/_files/restricted_store.php
     */
    public function testItReturnsEmptyResponseBodyAnd302ResponseCodeForStoreViewWithEnabledAccessRestrictionAndWrongUriParam(): void
    {
        $this->dispatch('/?bypass_store_restriction=invalid_stored_secret_value');
        $this->assertEmptyResponseBodyAnd302ResponseCode();
    }

    /**
     * @magentoDataFixture MageSuite_StoreAccessRestriction::Test/Integration/_files/restricted_store.php
     */
    public function testItReturnsNotEmptyResponseBodyAnd200ResponseCodeForStoreViewWithEnabledAccessRestrictionAndValidUriParam(): void
    {
        $this->dispatch('/?bypass_store_restriction=stored_secret_value');
        $this->assertNotEmptyResponseBodyAnd200ResponseCode();
    }

    /**
     * @magentoDataFixture MageSuite_StoreAccessRestriction::Test/Integration/_files/restricted_store.php
     */
    public function testItReturnsEmptyResponseBodyAnd302ResponseCodeForStoreViewWithEnabledAccessRestrictionAndMissingCookieParam(): void
    {
        $this->dispatch('/index.php');
        $this->assertEmptyResponseBodyAnd302ResponseCode();
    }

    /**
     * @magentoDataFixture MageSuite_StoreAccessRestriction::Test/Integration/_files/restricted_store.php
     */
    public function testItReturnsEmptyResponseBodyAnd302ResponseCodeForStoreViewWithEnabledAccessRestrictionAndWrongCookieParam(): void
    {
        $this->cookieManager->setPublicCookie(
            \MageSuite\StoreAccessRestriction\Service\StoreRestrictionValidator::RESTRICTION_BYPASS_COOKIE_NAME,
            'invalid_stored_secret_value'
        );
        $this->dispatch('');
        $this->assertEmptyResponseBodyAnd302ResponseCode();
    }

    /**
     * @magentoDataFixture MageSuite_StoreAccessRestriction::Test/Integration/_files/restricted_store.php
     */
    public function testItReturnsNotEmptyResponseBodyAnd200ResponseCodeForStoreViewWithEnabledAccessRestrictionAndValidCookieParam(): void
    {
        $this->cookieManager->setPublicCookie(
            \MageSuite\StoreAccessRestriction\Service\StoreRestrictionValidator::RESTRICTION_BYPASS_COOKIE_NAME,
            'stored_secret_value'
        );
        $this->dispatch('/');
        $this->assertNotEmptyResponseBodyAnd200ResponseCode();
    }

    protected function assertNotEmptyResponseBodyAnd200ResponseCode(): void
    {
        $this->assertNotEmpty($this->getResponse()->getContent());
        $this->assertEquals(200, $this->getResponse()->getStatusCode());
    }

    protected function assertEmptyResponseBodyAnd302ResponseCode(): void
    {
        $this->assertEmpty($this->getResponse()->getContent());
        $this->assertEquals(302, $this->getResponse()->getStatusCode());
        $this->assertEquals(
            'http://localhost/index.php/noroute',
            $this->getResponse()->getHeaders()->get('Location')->getUri()
        );
    }
}
