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
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use YesWiki\Shop\Entity\UserDefinition;
use YesWiki\Shop\Exception\EmptyHelloAssoParamException;
use YesWiki\Shop\PaymentSystemServiceInterface;

class HelloAssoService implements PaymentSystemServiceInterface
{
    private const SANDBOX_MODE = true;
    public const HELLOASSO_PARAMS_NAMES = [
        'clientId' => 'SHOP_HELLOASSO_CLIENTID',
        'clientApiKey' => 'SHOP_HELLOASSO_CLIENTAPIKEY'
    ];

    protected $params;

    public function __construct(ParameterBagInterface $params)
    {
        $this->params = $params;
    }


    public function loadApi()
    {
        // get parameters
        $helloAssoParams = $this->params->get('shop')['helloAssoParams'];
        foreach (HelloAssoService::HELLOASSO_PARAMS_NAMES as $key => $value) {
            if (empty($helloAssoParams[$key])) {
                throw new EmptyHelloAssoParamException("Param ['shop']['helloAssoParams'] should contain a not empty '$key' key!");
            }
        }
    }

    /**
     * Create HelloAsso User
     * @param UserDefinition $userDefinition
     *
     * @return array []
     */
    public function getUser(UserDefinition $userDefinition)
    {
        $this->loadApi(); // lazzy load

        return [];
    }
}
