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

namespace YesWiki\Shop;

use YesWiki\Core\YesWikiAction;
use YesWiki\Shop\Entity\HelloAssoDirectPaymentData;
use YesWiki\Shop\Service\HelloAssoDirectPaymentService;

class HelloAssoDirectPaymentAction extends YesWikiAction
{
    public function formatArguments($arg)
    {
        $newArgs = [];
        foreach([
            'totalAmount',
            'itemName',
            'shop backUrl',
            'shop errorUrl',
            'shop returnUrl',
            'firstName',
            'lastName',
            'email',
            'birthDate',
            'address',
            'city',
            'zipCode',
            'country',
            'meta',
        ] as $key) {
            $newArgs[$key] = $this->formatString($arg, $key);
        }
        $newArgs['containsDonation'] = $this->formatBoolean($arg, false, 'containsDonation');
        return $newArgs;
    }

    protected function formatString(array $arg, string $key): string
    {
        return (!empty($arg[$key]) && is_scalar($arg[$key]))
            ? strval($arg[$key])
            : '';
    }

    public function run()
    {
        $data = new HelloAssoDirectPaymentData(
            $this->arguments['totalAmount'],
            $this->arguments['itemName'],
            $this->arguments['shop backUrl'],
            $this->arguments['shop errorUrl'],
            $this->arguments['shop returnUrl'],
            $this->arguments['containsDonation'],
            $this->arguments['firstName'],
            $this->arguments['lastName'],
            $this->arguments['email'],
            $this->arguments['birthDate'],
            $this->arguments['address'],
            $this->arguments['city'],
            $this->arguments['zipCode'],
            $this->arguments['country'],
            $this->arguments['meta']
        );
        return $this->render('@shop/hello-asso-direct-payment-action.twig', [
            'args' => $this->arguments,
            'token' => $this->getService(HelloAssoDirectPaymentService::class)->getUpdatedToken($data)
        ]);
    }
}
