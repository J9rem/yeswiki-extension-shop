<?php

/*
 * This file is part of the YesWiki Extension Shop.
 *
 * Authors : see README.md file that was distributed with this source code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace YesWiki\Test\Shop\Service;

use YesWiki\Shop\Service\MangoPayService;
use YesWiki\Shop\Service\ShopService;
use YesWiki\Test\Core\YesWikiTestCase;
use YesWiki\Wiki;

require_once 'tests/YesWikiTestCase.php';

class ShopServiceTest extends YesWikiTestCase
{
    /**
     * @covers ShopService::__construct
     * @return Wiki
     */
    public function testShopServiceExisting(): Wiki
    {
        $wiki = $this->getWiki();
        $this->assertTrue($wiki->services->has(ShopService::class));
        return $wiki;
    }

    /**
     * @depends testShopServiceExisting
     * @covers ShopService::getServiceName
     * @param Wiki $wiki
     */
    public function testShopGetServiceName(Wiki $wiki)
    {
        $shopService = $wiki->services->get(ShopService::class);
        $serviceName = $shopService->getServiceName();
        $this->assertNotEmpty($serviceName);
        $this->assertIsString($serviceName);
        
        $serviceName = $shopService->getServiceName("wrong not allowed service name");
        $this->assertNotEmpty($serviceName);
        $this->assertIsString($serviceName);
        $this->assertEquals($serviceName, 'mangopay');
    }

    /**
     * @depends testShopServiceExisting
     * @depends testShopGetServiceName
     * @covers ShopService::getPaymentSystemService
     * @param Wiki $wiki
     */
    public function testShopGetPaymentSystemService(Wiki $wiki)
    {
        $shopService = $wiki->services->get(ShopService::class);
        $paymentSystemService = $shopService->getPaymentSystemService();
        // at this point 'mangopay' serviceName is forced by testShopGetServiceName
        $this->assertInstanceOf(MangoPayService::class, $paymentSystemService);
    }
}
