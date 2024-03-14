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

namespace YesWiki\Shop\Service;

use Symfony\Component\PasswordHasher\PasswordHasherInterface;
use YesWiki\Core\Service\PasswordHasherFactory;
use YesWiki\Shop\Entity\HelloAssoDirectPaymentData;

class HelloAssoDirectPaymentService
{
    protected $passwordHasherFactory;

    public function __construct(
        PasswordHasherFactory $passwordHasherFactory
    ) {
        $this->passwordHasherFactory = $passwordHasherFactory;
    }

    /**
     * generate a new token and save it in sessions
     * clean to old tokens (more than 10 minutes)
     * @param HelloAssoDirectPaymentData $data
     * @return string $token
     */
    public function getUpdatedToken(HelloAssoDirectPaymentData $data): string
    {
        $this->cleanPreviousTokens();
        $passwordHasher = $this->getPasswordHasher($data);
        $plainText = $this->getPlainTextFromArgs($data);
        $timeStamp = time();
        $hash = $passwordHasher->hash($plainText.$timeStamp);
        if (empty($_SESSION['helloAssoDirectPaymentToken'])) {
            $_SESSION['helloAssoDirectPaymentToken'] = [];
        }
        $_SESSION['helloAssoDirectPaymentToken'][$hash] = $timeStamp;
        return $hash;
    }
    /**
     * check token
     * clean to old tokens (more than 10 minutes)
     * @param HelloAssoDirectPaymentData $data
     * @param string $token
     * @return bool
     */
    public function checkToken(HelloAssoDirectPaymentData $data, string $token): bool
    {
        $this->cleanPreviousTokens();
        if (empty($_SESSION['helloAssoDirectPaymentToken'][$token])) {
            return false;
        }
        
        $passwordHasher = $this->getPasswordHasher($data);
        $plainText = $this->getPlainTextFromArgs($data);
        $regiteredTimeStamp = $_SESSION['helloAssoDirectPaymentToken'][$token];
        return $passwordHasher->verify($token, $plainText.$regiteredTimeStamp);
    }

    /**
     * get password hasher
     * @param HelloAssoDirectPaymentData $data
     * @return PasswordHasherInterface
     */
    protected function getPasswordHasher(HelloAssoDirectPaymentData $data):PasswordHasherInterface
    {
        return $this->passwordHasherFactory->getPasswordHasher($data);
    }

    /**
     * extract plain Text data from args
     * @param HelloAssoDirectPaymentData $incomingData
     * @return string
     */
    protected function getPlainTextFromArgs(HelloAssoDirectPaymentData $incomingData): string
    {
        $data = [];
        foreach([
            'email',
            'totalAmount',
            'containsDonation',
            'itemName',
            'backUrl',
            'errorUrl',
            'returnUrl',
            'meta'
        ] as $key) {
            $data[$key] = $incomingData[$key] ?? '';
        }
        return json_encode($data);
    }

    /**
     * clean previous tokens too old (more than 10 minutes)
     */
    protected function cleanPreviousTokens()
    {
        $tokens = (empty($_SESSION['helloAssoDirectPaymentToken'])
            || !is_array($_SESSION['helloAssoDirectPaymentToken']))
            ? []
            : $_SESSION['helloAssoDirectPaymentToken'];
        
        $currentTimeStamp = time();

        $tokens = array_filter(
            $tokens,
            function ($timestamp, $token) use ($currentTimeStamp) {
                return !empty($token)
                    && is_string($token)
                    && is_int($timestamp) && ($timestamp > ($currentTimeStamp - 600));
            },
            ARRAY_FILTER_USE_BOTH
        );

        if (empty($tokens) && isset($_SESSION['helloAssoDirectPaymentToken'])) {
            unset($_SESSION['helloAssoDirectPaymentToken']);
        } else {
            $_SESSION['helloAssoDirectPaymentToken'] = $tokens;
        }
    }

}
