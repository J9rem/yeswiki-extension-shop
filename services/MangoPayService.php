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
use MangoPay\UserNatural;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use YesWiki\Shop\Entity\UserDefinition;
use YesWiki\Shop\Exception\EmptyMangoPayParamException;
use YesWiki\Shop\PaymentSystemServiceInterface;

class MangoPayService implements PaymentSystemServiceInterface
{
    private const SANDBOX_MODE = true;
    public const MANGOPAY_PARAMS_NAMES = [
        'clientId' => 'SHOP_MANGOPAY_CLIENTID',
        'clientApiKey' => 'SHOP_MANGOPAY_CLIENTAPIKEY'
    ];

    private $mangoPayApi;
    protected $params;

    public function __construct(ParameterBagInterface $params)
    {
        $this->mangoPayApi = null;
        $this->params = $params;
    }


    public function loadApi()
    {
        // check curl and openssl library presence
        if (!function_exists('curl_version')) {
            throw new Exception("PHP library php_curl should be activated to use MangoPay!");
        }
        if (!defined('OPENSSL_VERSION_TEXT')) {
            throw new Exception("PHP library php_openssl should be activated to use MangoPay!");
        }

        // get parameters
        $mangoPayParams = $this->params->get('shop')['mangoPayParams'];
        foreach (MangoPayService::MANGOPAY_PARAMS_NAMES as $key => $value) {
            if (empty($mangoPayParams[$key])) {
                throw new EmptyMangoPayParamException("Param ['shop']['mangoPayParams'] should contain a not empty '$key' key!");
            }
        }

        // config

        $tmpFolder = sys_get_temp_dir(). DIRECTORY_SEPARATOR. (self::SANDBOX_MODE ? 'mangopay_sandbox' : 'mangopay_prod');
        if (!file_exists($tmpFolder) || !is_dir($tmpFolder)) {
            mkdir($tmpFolder);
        }

        $this->mangoPayApi = new MangoPayApi();
        $this->mangoPayApi->Config->ClientId = $mangoPayParams['clientId'];
        $this->mangoPayApi->Config->ClientPassword = $mangoPayParams['clientApiKey'];
        $this->mangoPayApi->Config->TemporaryFolder = $tmpFolder;
        $this->mangoPayApi->Config->BaseUrl = (self::SANDBOX_MODE) ? 'https://api.sandbox.mangopay.com' : 'https://api.mangopay.com';
    }

    /**
     * Create Mangopay User
     * @param UserDefinition $userDefinition
     *
     * @return MangopPayUser $mangoUser
     */
    public function getUser(UserDefinition $userDefinition)
    {
        $this->loadApi(); // lazzy load

        $mangoUser = new UserNatural();
        $mangoUser->PersonType = "NATURAL";
        $mangoUser->FirstName = $userDefinition->firstName;
        $mangoUser->LastName = $userDefinition->lastName;
        $mangoUser->Birthday = $userDefinition->birthday;
        $mangoUser->Nationality = $userDefinition->nationality;
        $mangoUser->CountryOfResidence = $userDefinition->countryOfResidence;
        $mangoUser->Email = $userDefinition->email;

        //Send the request
        $mangoUser = $this->mangoPayApi->Users->Create($mangoUser);

        return $mangoUser;
    }
}
