<?php

/*
 * This file is part of the YesWiki Extension shop.
 *
 * Authors : see README.md file that was distributed with this source code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * Feature UUID : hpf-helloasso-payments-table
 */

namespace YesWiki\Shop\Controller;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Response;
use YesWiki\Core\ApiResponse;
use YesWiki\Core\YesWikiController;

class HelloAssoDirectPaymentController extends YesWikiController
{
    protected $params;

    public function __construct(
        ParameterBagInterface $params
    ) {
        $this->params = $params;
    }

    /**
     * return the response for api
     * @return ApiResponse
     * @throws Exception
     */
    public function postHelloAssoDirectPaymentGetFormUrl(): ApiResponse
    {
        return new ApiResponse(['status'=>false],Response::HTTP_NOT_FOUND);
    }
}
