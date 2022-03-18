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

use YesWiki\Shop\Exception\EmptyMangoPayParamException;
use YesWiki\Shop\Service\MangoPayService;
use YesWiki\Test\Core\YesWikiTestCase;
use YesWiki\Wiki;

require_once 'tests/YesWikiTestCase.php';

class MangoPayServiceTest extends YesWikiTestCase
{
    /**
     * @covers MangoPayService::__construct
     * @return Wiki
     */
    public function testMangoPayServiceExisting(): Wiki
    {
        $wiki = $this->getWiki();
        $this->assertTrue($wiki->services->has(MangoPayService::class));
        return $wiki;
    }

    /**
     * @depends testMangoPayServiceExisting
     * @covers MangoPayService::loadApi
     * @param Wiki $wiki
     */
    public function testMangoPayServiceLoadApi(Wiki $wiki)
    {
        $mangoPayService = $wiki->services->get(MangoPayService::class);
        // $this->expectException(EmptyMangoPayParamException::class);
        $mangoPayService->loadApi();
        $this->assertTrue(true); // all is fine if no exception
    }
}
