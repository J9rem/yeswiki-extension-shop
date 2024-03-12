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

use Exception;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Response;
use YesWiki\Core\ApiResponse;
use YesWiki\Core\YesWikiController;
use YesWiki\Shop\Service\HelloAssoDirectPaymentService;

class HelloAssoDirectPaymentController extends YesWikiController
{
    protected $helloAssoDirectPaymentService;
    protected $params;

    public function __construct(
        HelloAssoDirectPaymentService $helloAssoDirectPaymentService,
        ParameterBagInterface $params
    ) {
        $this->helloAssoDirectPaymentService = $helloAssoDirectPaymentService;
        $this->params = $params;
    }

    /**
     * return the response for api
     * @return ApiResponse
     * @throws Exception
     */
    public function postHelloAssoDirectPaymentGetFormUrl(): ApiResponse
    {
        $data = $this->getDataFromPost();
        if (!$this->checkToken($data)){
            return new ApiResponse(['error'=>'Data have been corrupted !'],Response::HTTP_FORBIDDEN);
        }

        return new ApiResponse(['status'=>false],Response::HTTP_NOT_FOUND);
    }

    /**
     * get data from $_POST
     * @return array
     * @throws Exception
     */
    protected function getDataFromPost(): array
    {
        $data = [];
        foreach (['token','itemName','backUrl','errorUrl','returnUrl','containsDonation','totalAmount'] as $key) {
            if (empty($_POST[$key]) || !is_string($_POST[$key])){
                throw new Exception("\$_POST['$key'] should not be empty !");
            }
            $data[$key] = strval($_POST[$key]);
        }
        if (!in_array($data['containsDonation'],[true,false,'true','false',1,0],true)){
            throw new Exception("\$_POST['containsDonation'] should be a boolean !");
        }
        $data['containsDonation'] = in_array($data['containsDonation'],[true,'true',1],true);
        if (empty($_POST['payer']) || !is_array($_POST['payer'])){
            throw new Exception("\$_POST['payer'] should be an array !");
        }
        $data['payer'] = [];
        foreach ([
            'address',
            'birthDate',
            'city',
            'country',
            'email',
            'firstName',
            'lastName',
            'zipCode'
        ] as $key) {
            if (empty($_POST['payer'][$key]) || !is_scalar($_POST['payer'][$key])){
                throw new Exception("\$_POST['payer'][$key] should not be empty !");
            }
            $data['payer'][$key] = strval($_POST['payer'][$key]);
        }
        if (isset($_POST['meta'])){
            $data['meta'] = $_POST['meta'];
            foreach ($data['meta'] as $key => $value) {
                if (preg_match('/InCents$/',$key)){
                    $data['meta'][$key] = intval($value);
                }
            }
        }
        return $data;
    }

    /**
     * check token using service
     * @param array $data
     * @return bool
     */
    protected function checkToken(array $data):bool
    {
        $token = $data['token'];
        $args = [
            'email' => $data['payer']['email']
        ];
        foreach(['itemName','containsDonation','meta','totalAmount'] as $key){
            $args[$key] = $data[$key];
        }
        foreach(['backUrl','errorUrl','returnUrl'] as $key){
            $args['shop '.$key] = $data[$key];
        }
        return $this->helloAssoDirectPaymentService->checkToken($args,$token);
    }
}
