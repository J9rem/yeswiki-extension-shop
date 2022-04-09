<?php

namespace YesWiki\Shop\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use YesWiki\Core\ApiResponse;
use YesWiki\Core\YesWikiController;
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
        $data = $this->getService(HelloAssoService::class)->processTrigger($_POST);
        $response = $data->getResponse();
        return new ApiResponse(empty($response) ? null : $response, $data->getCode());
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
