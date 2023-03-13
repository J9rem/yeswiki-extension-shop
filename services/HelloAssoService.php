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
use Symfony\Component\HttpFoundation\Response;
use Throwable;
use YesWiki\Core\Service\TripleStore;
use YesWiki\Shop\Entity\Payment;
use YesWiki\Shop\Entity\User;
use YesWiki\Shop\Exception\EmptyHelloAssoParamException;
use YesWiki\Shop\Exception\NotUpdatedTripleException;
use YesWiki\Shop\HelloAssoPayments;
use YesWiki\Shop\PaymentsInterface;
use YesWiki\Shop\PaymentSystemServiceInterface;
use YesWiki\Wiki;

class HelloAssoService implements PaymentSystemServiceInterface
{
    private const SANDBOX_MODE = false;
    private const PARAMS_NAMES = ['clientId', 'clientApiKey'];
    public const TRIPLE_RESOURCE = 'helloAsso';
    public const TRIPLE_PROPERTY= 'https://yeswiki.net/vocabulary/helloassodata';

    protected $params;
    private $baseUrl;
    private $organizationSlug;
    private $token;
    private $tripleStore;
    private $wiki;

    public function __construct(ParameterBagInterface $params, TripleStore $tripleStore, Wiki $wiki)
    {
        $this->params = $params;
        $this->baseUrl = self::SANDBOX_MODE ? "https://api.helloasso-rc.com/" : "https://api.helloasso.com/";
        $this->organizationSlug = null;
        $this->token = null;
        $this->tripleStore = $tripleStore;
        $this->wiki = $wiki;
    }


    public function loadApi()
    {
        if (is_null($this->token)) {
            // check curl and openssl library presence
            if (!function_exists('curl_version')) {
                throw new Exception("PHP library php_curl should be activated to use HelloAsso!");
            }
            // get parameters
            $helloAssoParams = $this->params->get('shop')['helloAsso'];
            foreach (HelloAssoService::PARAMS_NAMES as $key) {
                if (empty($helloAssoParams[$key])) {
                    throw new EmptyHelloAssoParamException("Param ['shop']['helloAsso'] should contain a not empty '$key' key!");
                }
            }
            $this->token = $this->getApiToken();
        }
    }

    /**
     * Create HelloAsso User
     * @param User $user
     *
     * @return array []
     */
    public function getUser(User $user)
    {
        $this->loadApi(); // lazzy load

        return [];
    }

    /**
     * get Hello Asso route api
     * @param string $url
     * @param string $type
     * @param bool $isPost optionnal
     * @param array|string $postData optionnal
     * @return mixed $resul
     */
    private function getRouteApi(string $url, string $type, bool $isPost = false, $postData = [], bool $withBearer = true)
    {
        if ($withBearer) {
            $this->loadApi();
            $headers = [
                "Authorization: Bearer {$this->token['access_token']}",
            ];
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, $isPost);
        if ($isPost && !empty($postData) && (is_string($postData) || is_array($postData))) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        }
        if ($withBearer) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5); // connect timeout in seconds
        curl_setopt($ch, CURLOPT_TIMEOUT, 5); // total timeout in seconds
        $results = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        if (!empty($error)) {
            throw new Exception("Error when getting $type via API : $error (httpcode: $httpCode)");
        }
        try {
            $output = json_decode($results, true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable $th) {
            throw new Exception("Json Decode Error : {$th->getMessage()}".($th->getCode() == 4 ? " ; output : '".strval($results)."'": ''), $th->getCode(),$th);
        }
        if (is_null($output)){
            throw new Exception('Output is not json '.strval($results));
        }
        return $output;
    }

    /**
     * check if possible to call api
     * @return bool
     */
    private function canCheckApi(): bool
    {
        $shopParams = $this->params->get('shop');
        $minTimeBetweenCalls = (
            empty($shopParams['helloAsso']) ||
            empty($shopParams['helloAsso']['minTimeBetweenCalls'])
        ) ? 10
        : max(10, intval($this->params->get('shop')['helloAsso']['minTimeBetweenCalls']));
        $triple = $this->getTripleValue();
        return
            empty($triple) ||
            empty($triple['exists']) ||
            !$triple['exists'] ||
            empty($triple['value']['lastCallTimeStamp']) ||
            ($triple['value']['lastCallTimeStamp'] + $minTimeBetweenCalls) < time()
        ;
    }

    /**
     * get forms via hello asso
     * @return array $forms
     */
    public function getForms(): array
    {
        $this->loadApi();
        $url = $this->baseUrl."v5/organizations/{$this->getOrganizationSlug()}/forms";
        $forms = [];
        do {
            $results = $this->getRouteApi($url, "forms");
            foreach ($results['data'] as $form) {
                $forms[] = $form;
            }
            $pageIndex = $results['pagination']['pageIndex'];
            $totalPages = $results['pagination']['totalPages'];
            $continuationToken = $results['pagination']['continuationToken'];
            $url = $this->baseUrl."v5/organizations/{$this->getOrganizationSlug()}/forms?continuationToken={$continuationToken}";
        } while ($pageIndex < $totalPages);
        return $forms;
    }

    /**
     * get payments via hello asso
     * @param array $options
     * @return HelloAssoPayments|null $payments
     */
    public function getPayments(array $options): ?PaymentsInterface
    {
        if (!$this->canCheckApi()) {
            return null;
        }
        $options = array_merge(['states' => ["Authorized"]], $options);
        $this->loadApi();
        if (!empty($options['formType']) && !empty($options['formSlug'])) {
            $url = $this->baseUrl."v5/organizations/{$this->getOrganizationSlug()}/forms/{$options['formType']}/{$options['formSlug']}/payments";
        } else {
            $url = $this->baseUrl."v5/organizations/{$this->getOrganizationSlug()}/payments";
        }
        if (!empty($options['email'])) {
            $query = (isset($query) ? $query."&" : "?")."userSearchKey=".urlencode($options['email']);
        }
        if (!empty($options['states'])) {
            foreach ($options['states'] as $key => $value) {
                $query = (isset($query) ? $query."&" : "?")."states[$key]=$value";
            }
        }
        $this->updateLastCallTimeStamp();
        $results = $this->getRouteApi($url.($query ?? ""), "payments");
        
        $pageIndex = isset($results['pagination']) && isset($results['pagination']['pageIndex']) && is_scalar($results['pagination']['pageIndex'])
            ? intval($results['pagination']['pageIndex'])
            : 1;
        $totalPages = isset($results['pagination']) && isset($results['pagination']['totalPages']) && is_scalar($results['pagination']['totalPages'])
            ? intval($results['pagination']['totalPages'])
            : 1;
        $continuationToken = isset($results['pagination']) && isset($results['pagination']['continuationToken']) && is_scalar($results['pagination']['continuationToken'])
            ? strval($results['pagination']['continuationToken'])
            : "";

        $helloAssoPayments = new HelloAssoPayments($this->convertToPayments($results), [
            'nextPageToken' => ($pageIndex < $totalPages) ? $continuationToken : "",
        ]);
        return $helloAssoPayments;
    }

    /**
     * get token from api
     * @return array $token
     */
    private function getApiToken(): array
    {
        $triple = $this->getTripleValue();
        if (empty($triple['value']) ||
                empty($triple['value']['refreshToken']) ||
                empty($triple['value']['accessToken']) ||
                empty($triple['value']['refreshTokenExpireTimeStamp']) ||
                empty($triple['value']['accessTokenExpireTimeStamp']) ||
                $triple['value']['refreshTokenExpireTimeStamp'] < time()) {
            $url = $this->baseUrl."oauth2/token";
            $postData = "client_id={$this->params->get('shop')['helloAsso']['clientId']}".
                "&client_secret={$this->params->get('shop')['helloAsso']['clientApiKey']}".
                "&grant_type=client_credentials";
            $data = $this->getRouteApi($url, "api token", true, $postData, false);
            if (empty($data) || empty($data['access_token'] || empty($data['refresh_token']))) {
                throw new Exception("Token not generated");
            }
            $newValue = [
                'refreshToken' => strval($data['refresh_token']),
                'accessToken' => strval($data['access_token']),
                'accessTokenExpireTimeStamp' => time()+intval($data['expires_in'])-120, // margin of 2 minutes
                'refreshTokenExpireTimeStamp' => time()+29*24*3600, // 29 days
            ];
            $data = $this->saveTriple($triple, $newValue);
        } elseif ($triple['value']['accessTokenExpireTimeStamp'] < time()) {
            $url = $this->baseUrl."oauth2/token";
            $postData = "client_id={$this->params->get('shop')['helloAsso']['clientId']}".
                "&refresh_token={$triple['value']['refreshToken']}".
                "&grant_type=refresh_token";
            $data = $this->getRouteApi($url, "api refresh token", true, $postData, false);
            if (empty($data) || empty($data['access_token'] || empty($data['refresh_token']))) {
                throw new Exception("Token not generated");
            }
            $newValue = [
                'refreshToken' => strval($data['refresh_token']),
                'accessToken' => strval($data['access_token']),
                'accessTokenExpireTimeStamp' => time()+intval($data['expires_in'])-120, // margin of 2 minutes
                'refreshTokenExpireTimeStamp' => time()+29*24*3600, // 29 days
            ];
            $data = $this->saveTriple($triple, $newValue);
        } else {
            $data = $this->getExistingToken($triple, true);
        }
        return $data;
    }

    private function getExistingToken(array $triple, bool $test = false): array
    {
        $test = $test ? true :
        (
            !empty($triple['value']) &&
            !empty($triple['value']['refreshToken']) &&
            !empty($triple['value']['accessToken']) &&
            !empty($triple['value']['refreshTokenExpireTimeStamp']) &&
            !empty($triple['value']['accessTokenExpireTimeStamp']) &&
            $triple['value']['refreshTokenExpireTimeStamp'] > time() &&
            $triple['value']['accessTokenExpireTimeStamp'] > time()
        );
        return $test
            ? [
                'access_token' => $triple['value']['accessToken']
            ]
            : [] ;
    }

    private function saveTriple(array $triple, array $value): array
    {
        try {
            if ($triple['exists']) {
                if (!empty($triple['lastCallTimeStamp'])) {
                    $value['lastCallTimeStamp'] = $triple['lastCallTimeStamp'];
                }
                $this->updateTripleValue($triple['rawValue'], $value);
            } else {
                $this->createTripleValue($value);
            }
            return $this->getExistingToken(['value'=>$value], true);
        } catch (NotUpdatedTripleException $th) {
            $tokenData = $this->getTripleValue();
            if (!empty($tokenData)) {
                $data = $this->getExistingToken($tokenData, false);
                if (!empty($data['access_token'])) {
                    return $data;
                }
            }
            throw $th;
        }
    }

    /**
     * get organizationSlug otherwise retrieve via API (first organization)
     * @return string
     */
    private function getOrganizationSlug(): string
    {
        if (is_null($this->organizationSlug)) {
            $this->loadApi();
            if (!empty($this->params->get('shop')['helloAsso']['organizationSlug'])) {
                $this->organizationSlug = $this->params->get('shop')['helloAsso']['organizationSlug'];
            } else {
                $url = $this->baseUrl."v5/users/me/organizations";
                $organizations = $this->getRouteApi($url, "organizations");
                if (empty($organizations) || !is_array($organizations)) {
                    throw new Exception("Error when getting organizations");
                } else {
                    $this->organizationSlug = $organizations[0]['organizationSlug'];
                }
            }
        }
        return $this->organizationSlug;
    }

    private function convertToUser(array $data): ?User
    {
        $user = new User();
        $user->firstName = $data['firstName'] ?? null;
        $user->lastName = $data['lastName'] ?? null;
        $user->compagny = $data['company'] ?? null;
        $user->email = $data['email'] ?? null;
        $user->address = $data['address'] ?? null;
        $user->postalCode = $data['zipCode'] ?? null;
        $user->town = $data['city'] ?? null;
        $user->countryOfResidence = $data['firstName'] ?? null;
        return $user;
    }

    public function convertToPayments(array $data): array
    {
        $payments = [];
        foreach ($data['data'] as $payment) {
            $newData = [];
            $newData['id'] = $payment['id'];
            $newData['amount'] = floatval($payment['amount'])/100;
            $newData['date'] = $payment['date'];
            $newData['payer'] = $this->convertToUser($payment['payer']);
            $payments[] = new Payment($newData);
        }
        return $payments;
    }

    public function isAllowedProcessTrigger(string $token): bool
    {
        return (!empty($this->params->get('shop')['helloAsso'])
            && !empty($this->params->get('shop')['helloAsso']['postApiToken'])
            && $this->params->get('shop')['helloAsso']['postApiToken'] === $token);
    }

    private function getTripleValue(): array
    {
        $triple = $this->tripleStore->getOne(self::TRIPLE_RESOURCE, self::TRIPLE_PROPERTY, '', '');
        $value = empty($triple) ? null : json_decode($triple, true);
        return (!empty($value)  && is_array($value))
            ? ['exists' => true,'rawValue' => $triple,'value' => $value]
            : ['exists' => !empty($triple),'rawValue' => $triple,'value' => []];
    }

    private function updateLastCallTimeStamp()
    {
        $triple = $this->getTripleValue();
        if (!empty($triple['value'])) {
            $newValue = $triple['value'];
            $newValue['lastCallTimeStamp'] = time();
            $this->saveTriple($triple, $newValue);
        }
    }

    private function createTripleValue(array $value)
    {
        $result = $this->tripleStore->create(self::TRIPLE_RESOURCE, self::TRIPLE_PROPERTY, json_encode($value), '', '');
        switch ($result) {
            case 3:
                // already created do nothing
            case 0:
                // all right
                // do nothing
                break;
            case 1:
            default:
                // error
                throw new Exception("HelloAssoTriple not updated");
                break;
        }
    }

    private function updateTripleValue(?string $oldRawValue, array $value)
    {
        $result = $this->tripleStore->update(
            self::TRIPLE_RESOURCE,
            self::TRIPLE_PROPERTY,
            $oldRawValue,
            json_encode($value),
            '',
            ''
        );
        switch ($result) {
            case 2:
                throw new NotUpdatedTripleException("HelloAssoTriple not existing with oldvalue, so not updated");
                break;
            case 3:
                // already update, so not change done
            case 0:
                // all right
                // do nothing
                break;
            case 1:
            default:
                // error
                throw new Exception("HelloAssoTriple not updated");
                break;
        }
    }
}
