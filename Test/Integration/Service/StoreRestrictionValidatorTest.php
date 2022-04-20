<?php

namespace MageSuite\StoreAccessRestriction\Test\Integration\Service;

/**
 * @magentoDbIsolation enabled
 * @magentoAppIsolation enabled
 */
class StoreRestrictionValidatorTest extends \Magento\TestFramework\TestCase\AbstractController
{
    /**
     * @var \Magento\TestFramework\ObjectManager
     */
    protected $objectManager;

    /**
     * @var \Magento\Framework\Stdlib\CookieManagerInterface
     */
    protected $cookieManager;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress
     */
    protected $remoteAddress;

    public function setUp(): void
    {
        parent::setUp();
        $this->objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
        $this->cookieManager = $this->objectManager->create(\Magento\Framework\Stdlib\CookieManagerInterface::class);
        $this->storeManager = $this->objectManager->create(\Magento\Store\Model\StoreManagerInterface::class);
        $this->remoteAddress = $this->getMockBuilder(
            \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress::class
        )->disableOriginalConstructor()->getMock();
        $this->remoteAddress->method('getRemoteAddress')->willReturn('1.1.1.1');
        $this->objectManager->addSharedInstance($this->remoteAddress, \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress::class);
    }

    /**
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     */
    public function testItReturnsNotEmptyResponseBodyAnd200ResponseCodeForStoreViewWithDisabledAccessRestriction(): void
    {
        $this->dispatch('http://localhost/index.php');
        $this->assertNotEmptyResponseBodyAnd200ResponseCode();
    }

    /**
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     */
    public function testItReturnsNotEmptyResponseBodyAnd200ResponseCodeForStoreViewWithEnabledAccessRestrictionForWhitelistedIpAddress(): void
    {
        $this->setAccessRestrictedAnd2IpAddressesForStoreView();
        $this->dispatch('http://localhost/index.php');
        $this->assertNotEmptyResponseBodyAnd200ResponseCode();
    }

    /**
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     */
    public function testItReturnsEmptyResponseBodyAnd302ResponseCodeForStoreViewWithEnabledAccessRestrictionForNotWhitelistedIpAddress(): void
    {
        $defaultStore = $this->getStoreViewWithRestrictedAccess();
        $defaultStore->setData('allowed_ips', '3.3.3.3');
        $this->dispatch('http://localhost/index.php');
        $this->assertEmptyResponseBodyAnd302ResponseCode();
    }

    /**
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     */
    public function testItReturnsEmptyResponseBodyAnd302ResponseCodeForStoreViewWithEnabledAccessRestrictionAndWrongUriParam(): void
    {
        $this->setAccessRestrictedAndCookieParamForStoreView();
        $this->dispatch('http://localhost/index.php?bypass_store_restriction=invalid_stored_secret_value');
        $this->assertEmptyResponseBodyAnd302ResponseCode();
    }

    /**
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     */
    public function testItReturnsNotEmptyResponseBodyAnd200ResponseCodeeForStoreViewWithEnabledAccessRestrictionAndValidUriParam(): void
    {
        $this->setAccessRestrictedAndCookieParamForStoreView();
        $this->dispatch('http://localhost/index.php?bypass_store_restriction=stored_secret_value');
        $this->assertNotEmptyResponseBodyAnd200ResponseCode();
    }

    /**
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     */
    public function testItReturnsEmptyResponseBodyAnd302ResponseCodeForStoreViewWithEnabledAccessRestrictionAndMissingCookieParam(): void
    {
        $this->getStoreViewWithRestrictedAccess();
        $this->dispatch('http://localhost/index.php');
        $this->assertEmptyResponseBodyAnd302ResponseCode();
    }

    /**
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     */
    public function testItReturnsEmptyResponseBodyAnd302ResponseCodeForStoreViewWithEnabledAccessRestrictionAndWrongCookieParam(): void
    {
        $this->setAccessRestrictedAndCookieParamForStoreView();
        $this->cookieManager->setPublicCookie(
            \MageSuite\StoreAccessRestriction\Service\StoreRestrictionValidator::RESTRICTION_BYPASS_COOKIE_NAME,
            'invalid_stored_secret_value'
        );
        $this->dispatch('http://localhost/index.php');
        $this->assertEmptyResponseBodyAnd302ResponseCode();
    }

    /**
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     */
    public function testItReturnsNotEmptyResponseBodyAnd200ResponseCodeForStoreViewWithEnabledAccessRestrictionAndValidCookieParam(): void
    {
        $this->setAccessRestrictedAndCookieParamForStoreView();
        $this->cookieManager->setPublicCookie(
            \MageSuite\StoreAccessRestriction\Service\StoreRestrictionValidator::RESTRICTION_BYPASS_COOKIE_NAME,
            'stored_secret_value'
        );
        $this->dispatch('http://localhost/index.php');
        $this->assertNotEmptyResponseBodyAnd200ResponseCode();
    }

    protected function setAccessRestrictedAnd2IpAddressesForStoreView(): void
    {
        $defaultStore = $this->getStoreViewWithRestrictedAccess();
        $defaultStore->setData('allowed_ips', '1.1.1.1,2.2.2.2');
    }

    protected function setAccessRestrictedAndCookieParamForStoreView(): void
    {
        $defaultStore = $this->getStoreViewWithRestrictedAccess();
        $defaultStore->setData(
            'restriction_bypass_cookie_value',
            'stored_secret_value'
        );
    }

    protected function getStoreViewWithRestrictedAccess(): \Magento\Store\Api\Data\StoreInterface
    {
        $defaultStore = $this->storeManager->getStore(1);
        $defaultStore->setData(
            'is_access_restricted',
            1
        );
        return $defaultStore;
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
