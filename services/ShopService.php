<?php

/*
 * This file is part of the YesWiki Extension Shop.
 *
 * Authors : see README.md file that was distributed with this source code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace YesWiki\Shop\Service;

use Exception;
use MangoPay\MangoPayApi;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use YesWiki\Shop\PaymentSystemServiceInterface;
use YesWiki\Shop\Service\MangoPayService;
use YesWiki\Wiki;

class ShopService
{
protected const AUTHORIZED_SERVICE_NAMES = ['mangopay']; /* for future usage ,'mollie','stripes'*/

    protected $params;
    protected $paymentSystemService;
    protected $serviceName;
    protected $wiki;

    public function __construct(
        ParameterBagInterface $params,
        Wiki $wiki
    ) {
        $this->params = $params;
        $this->paymentSystemService = null;
        $wantedServiceName = $this->params->get('shop')['serviceName'] ;
        $this->serviceName = $this->getServiceName($wantedServiceName);
        $this->wiki = $wiki;
    }

    public function getPaymentSystemService(): PaymentSystemServiceInterface
    {
        if (is_null($this->paymentSystemService)) {
            switch ($this->serviceName) {
                case 'mangopay':
                    return $this->wiki->services->get(MangoPayService::class);
                    break;
                
                default:
                    throw new Exception("Service name '{$this->serviceName}' is not already supported !");
                    break;
            }
        }
        return $this->paymentSystemService;
    }

    public function getServiceName(string $wantedServiceName = ""): string
    {
        if (!empty($wantedServiceName)){
            $this->serviceName = in_array($wantedServiceName, self::AUTHORIZED_SERVICE_NAMES, true) ? $wantedServiceName: self::AUTHORIZED_SERVICE_NAMES[0];
        }
        return $this->serviceName;
    }
}
