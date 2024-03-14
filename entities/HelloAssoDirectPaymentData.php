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

namespace YesWiki\Shop\Entity;

use ArrayAccess;
use JsonSerializable ;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherAwareInterface;

class HelloAssoDirectPaymentData implements PasswordHasherAwareInterface, ArrayAccess, JsonSerializable
{
    public const FIRST_LEVEL_KEYS = [
        'totalAmount',
        'itemName',
        'backUrl',
        'errorUrl',
        'returnUrl',
        'containsDonation',
        'meta'
    ];
    public const SECOND_LEVEL_KEYS_FOR_PAYER = [
        'firstName',
        'lastName',
        'email',
        'dateofBirth',
        'address',
        'city',
        'zipCode',
        'country'
    ];

    protected $container;

    public function __construct(
        string $totalAmount,
        string $itemName,
        string $backUrl,
        string $errorUrl,
        string $returnUrl,
        bool $containsDonation,
        string $payerfirstName,
        string $payerlastName,
        string $payeremail,
        string $payerdateofBirth,
        string $payeraddress,
        string $payercity,
        string $payerzipCode,
        string $payercountry,
        string $meta,
    ) {
        $this->container = [];
        foreach (self::FIRST_LEVEL_KEYS as $key) {
            $this->container[$key] = $$key;
        }
        $this->container['payer'] = [];
        foreach (self::SECOND_LEVEL_KEYS_FOR_PAYER as $key) {
            $this->container['payer'][$key] = ${"payer$key"};
        }
    }

    public function getPasswordHasherName(): ?string
    {
        return 'cookie';
    }

    /**
     * SETTER
     */
    // change return of this method to keep compatible with php 7.3 (mixed is not managed)
    #[\ReturnTypeWillChange]
    public function offsetSet($offset, $value)
    {
        // do nothing
    }

    // change return of this method to keep compatible with php 7.3 (mixed is not managed)
    #[\ReturnTypeWillChange]
    public function offsetUnset($offset)
    {
        // do nothing
    }

    /**
     * GETTER
     */

    public function offsetExists($offset): bool
    {
        return $offset === 'payer' || in_array($offset, self::FIRST_LEVEL_KEYS, true);
    }

    // change return of this method to keep compatible with php 7.3 (mixed is not managed)
    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return $this->offsetExists($offset) ? $this->container[$offset] : null;
    }

    /* === JsonSerializable interface === */
    // change return of this method to keep compatible with php 7.3 (mixed is not managed)
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return array_merge(
            $this->container,
            [
                'initialAmount' => $this->container['totalAmount'],
                'metadata' => json_decode($this->container['meta'], true) ?? null,
                'payer' => array_merge(
                    $this->container['payer'],
                    [
                        'country' => strtoupper(substr($this->container['payer']['country'], 0, 3))
                    ],
                )
            ]
        );
    }
    /* === END JsonSerializable interface === */
}
