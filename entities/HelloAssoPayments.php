<?php

/*
 * This file is part of the YesWiki Extension Shop.
 *
 * Authors : see README.md file that was distributed with this source code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace YesWiki\Shop\Entity;

use Exception;
use Iterator;
use YesWiki\Shop\Entity\Payment;
use YesWiki\Shop\Entity\User;
use YesWiki\Shop\Entity\PaymentsInterface;

class HelloAssoPayments implements PaymentsInterface
{
    private $nextPageToken;
    private $payments;
    private $position;

    /**
     * @param Payment[] $payments
     * @param array $otherData
     */
    public function __construct(array $payments, array $otherData)
    {
        if (count(array_filter($payments, function ($payment) {
            return !($payment instanceof Payment);
        })) > 0) {
            throw new Exception("\$payments should be an array of Payment");
        }
        $this->payments = array_values($payments);
        $this->position = 0;
        $this->nextPageToken = isset($otherData['nextPageToken']) && is_string($otherData['nextPageToken']) ? $otherData['nextPageToken'] : "";
    }

    /**
     * get next token to retrive the following results if pagintation
     *
     * @return string $nextPageToken
     */
    public function getNextPageToken(): string
    {
        return $this->nextPageToken;
    }

    /**
     * Iterator on payments only !
     * @return Payment[]
     */
    public function getPayments(): array
    {
        return $this->payments;
    }

    /* === Iterator interface === */
    // change return of this method to keep compatible with php 7.3 (mixed is not managed)
    #[\ReturnTypeWillChange]
    public function current()
    {
        return $this->payments[$this->position];
    }

    // change return of this method to keep compatible with php 7.3 (mixed is not managed)
    #[\ReturnTypeWillChange]
    public function key()
    {
        return $this->position;
    }

    public function next(): void
    {
        ++$this->position;
    }

    public function rewind(): void
    {
        $this->position = 0;
    }

    public function valid(): bool
    {
        return isset($this->payments[$this->position]);
    }

    /* === === */


    /* === JsonSerializable interface === */
    // change return of this method to keep compatible with php 7.3 (mixed is not managed)
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return [
            'payments' => $this->getPayments(),
            'nextPageToken' => $this->getNextPageToken(),
            ];
    }
    /* === === */
}
