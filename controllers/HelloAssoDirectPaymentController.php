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

use DateTimeImmutable;
use Exception;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Response;
use YesWiki\Core\ApiResponse;
use YesWiki\Core\YesWikiController;
use YesWiki\Shop\Entity\HelloAssoDirectPaymentData;
use YesWiki\Shop\Service\HelloAssoService;
use YesWiki\Shop\Service\HelloAssoDirectPaymentService;

class HelloAssoDirectPaymentController extends YesWikiController
{
    protected $helloAssoService;
    protected $helloAssoDirectPaymentService;
    protected $params;

    public function __construct(
        HelloAssoService $helloAssoService,
        HelloAssoDirectPaymentService $helloAssoDirectPaymentService,
        ParameterBagInterface $params
    ) {
        $this->helloAssoService = $helloAssoService;
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
        list('data' => $data, 'token' => $token) = $this->getDataFromPost();
        if (!$this->helloAssoDirectPaymentService->checkToken($data, $token)) {
            return new ApiResponse(['error'=>'Data have been corrupted !'], Response::HTTP_FORBIDDEN);
        }

        list(
            'id' => $id,
            'redirectUrl' => $redirectUrl,
        ) = $this->helloAssoService->initACheckout($data);

        return new ApiResponse(
            [
                'redirectUrl' => $redirectUrl
            ],
            Response::HTTP_OK
        );
    }

    /**
     * get data from $_POST
     * @return [HelloAssoDirectPaymentData $data, string $token]
     * @throws Exception
     */
    protected function getDataFromPost(): array
    {
        foreach (['token','itemName','backUrl','errorUrl','returnUrl','containsDonation','totalAmount', 'meta'] as $key) {
            if (empty($_POST[$key]) || !is_string($_POST[$key])) {
                throw new Exception("\$_POST['$key'] should not be empty !");
            }
        }
        if (!in_array($_POST['containsDonation'], [true,false,'true','false',1,0], true)) {
            throw new Exception("\$_POST['containsDonation'] should be a boolean !");
        }
        if (empty($_POST['payer']) || !is_array($_POST['payer'])) {
            throw new Exception("\$_POST['payer'] should be an array !");
        }
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
            if (empty($_POST['payer'][$key]) || !is_scalar($_POST['payer'][$key])) {
                throw new Exception("\$_POST['payer'][$key] should not be empty !");
            }
        }
        $birthDate = DateTimeImmutable::createFromFormat('d/m/Y', $_POST['payer']['birthDate']);
        if ($birthDate === false) {
            throw new Exception("\$_POST['payer']['birthDate'] is not a date ({$_POST['payer']['birthDate']}) !");
        }
        
        return [
            'data' => new HelloAssoDirectPaymentData(
                strval($_POST['totalAmount']),
                strval($_POST['itemName']),
                strval($_POST['backUrl']),
                strval($_POST['errorUrl']),
                strval($_POST['returnUrl']),
                in_array($_POST['containsDonation'], [true,'true',1], true),
                strval($_POST['payer']['firstName']),
                strval($_POST['payer']['lastName']),
                strval($_POST['payer']['email']),
                $birthDate->format('c'),
                strval($_POST['payer']['address']),
                strval($_POST['payer']['city']),
                strval($_POST['payer']['zipCode']),
                strval($_POST['payer']['country']),
                strval($_POST['meta'])
            ),
            'token' => strval($_POST['token'])
        ];
    }
}
