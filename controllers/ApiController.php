<?php

/*
 * This file is part of the YesWiki Extension Shop.
 *
 * Authors : see README.md file that was distributed with this source code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace YesWiki\Shop\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use YesWiki\Core\ApiResponse;
use YesWiki\Core\YesWikiController;
use YesWiki\Shop\Controller\HelloAssoDirectPaymentController;
use YesWiki\Shop\Service\EventDispatcher;
use YesWiki\Shop\Service\HelloAssoService;

class ApiController extends YesWikiController
{
    /**
     * @Route("/api/shop/helloasso/{token}", methods={"POST"},options={"acl":{"public"}})
     */
    public function postHelloAsso($token)
    {
        if (!$this->getService(HelloAssoService::class)->isAllowedProcessTrigger($token)) {
            return new ApiResponse(['error' => 'not allowed token'], Response::HTTP_UNAUTHORIZED);
        }
        $this->getService(EventDispatcher::class)->dispatch('shop.helloasso.api.called', ['post' => $_POST]);
        return new ApiResponse(null, Response::HTTP_OK);
    }

    
    /**
     * @Route("/api/shop/helloasso/directpayment/getformurl", methods={"POST"},options={"acl":{"public","+"}})
     * Feature UUID : hpf-helloasso-payments-table
     */
    public function postHelloAssoDirectPaymentGetFormUrl()
    {
        return $this->getService(HelloAssoDirectPaymentController::class)->postHelloAssoDirectPaymentGetFormUrl();
    }

    /**
     * Display Shop api documentation
     *
     * @return string
     */
    public function getDocumentation()
    {
        $output = '<h2>Shop</h2>' . "\n";

        $output .= '
        <p>
        <b><code>POST ' . $this->wiki->href('', 'api/shop/helloasso') . '</code></b><br />
        Process actions on trigger from hello asso.
        </p>';

        return $output;
    }
}
