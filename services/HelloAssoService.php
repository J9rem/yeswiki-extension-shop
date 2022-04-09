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
use YesWiki\Shop\Entity\ApiData;
use YesWiki\Shop\Entity\Payment;
use YesWiki\Shop\Entity\User;
use YesWiki\Shop\Exception\EmptyHelloAssoParamException;
use YesWiki\Shop\HelloAssoPayments;
use YesWiki\Shop\PaymentSystemServiceInterface;
use YesWiki\Wiki;

class HelloAssoService implements PaymentSystemServiceInterface
{
    private const SANDBOX_MODE = true;
    private const PARAMS_NAMES = ['clientId', 'clientApiKey'];

    protected $params;
    private $baseUrl;
    private $organizationSlug;
    private $token;
    private $wiki;

    public function __construct(ParameterBagInterface $params, Wiki $wiki)
    {
        $this->params = $params;
        $this->baseUrl = self::SANDBOX_MODE ? "https://api.helloasso.com/" : "https://api.helloasso.com/";
        $this->organizationSlug = null;
        $this->token = null;
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
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $results = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        if (!empty($error)) {
            throw new Exception("Error when getting $type via API : $error (httpcode: $httpCode)");
        }
        return json_decode($results, true);
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
    public function getPayments(array $options): ?HelloAssoPayments
    {
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
        $url = $this->baseUrl."oauth2/token";
        $data = "client_id={$this->params->get('shop')['helloAsso']['clientId']}".
            "&client_secret={$this->params->get('shop')['helloAsso']['clientApiKey']}".
            "&grant_type=client_credentials";
        return $this->getRouteApi($url, "api token", true, $data, false);
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

    private function convertToPayments(array $data): array
    {
        $payments = [];
        foreach ($data['data'] as $payment) {
            $newData = [];
            $newData['id'] = $payment['id'];
            $newData['amount'] = $payment['amount'];
            $newData['date'] = $payment['date'];
            $newData['payer'] = $this->convertToUser($payment['payer']);
            $payments[] = new Payment($newData);
        }
        return $payments;
    }

    /**
     * process trigger when called via api
     * @param array $postNotSanitized
     * @return ApiData $returnData
     */
    public function processTrigger(array $postNotSanitized): ApiData
    {
        $results = [];
        foreach ($this->getMethodsToTrigger() as $index => $methodData) {
            $rawResult = call_user_func(
                [
                    $methodData['object'],
                    $methodData['methodName'],
                ],
                $postNotSanitized,
                $index
            );
            if (is_array($rawResult)) {
                $results = array_merge($results, $rawResult);
            }
        }
        return new ApiData(Response::HTTP_OK, $results);
    }

    /**
     * get methods to trigger from params finishing by '_shopHelloAssoTrigger'
     * @return array
     */
    private function getMethodsToTrigger(): array
    {
        $filteredParams = array_filter(
            $this->params->all(),
            function ($paramKey) {
                $postfix = '_shopHelloAssoTrigger';
                return substr($paramKey, -strlen($postfix)) == $postfix;
            },
            ARRAY_FILTER_USE_KEY
        );
        $methods = [];
        if (!empty($filteredParams)) {
            foreach ($filteredParams as $paramKey => $data) {
                if (is_array($data)) {
                    foreach ($data as $serviceName => $methodName) {
                        if ($this->wiki->services->has($serviceName)) {
                            $object =  $this->wiki->services->get($serviceName);
                            if (method_exists($object, $methodName)) {
                                $methods[] = [
                                    'object' => $object,
                                    'methodName' => $methodName
                                ];
                            }
                        }
                    }
                }
            }
        }
        return $methods;
    }

    public function isAllowedProcessTrigger(string $token): bool
    {
        return (!empty($this->params->get('shop')['helloAsso'])
            && !empty($this->params->get('shop')['helloAsso']['postApiToken'])
            && $this->params->get('shop')['helloAsso']['postApiToken'] === $token);
    }
}
