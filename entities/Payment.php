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

use JsonSerializable;

class Payment implements JsonSerializable
{
    public $id;
    public $payer;
    public $amount;
    public $date;

    public function __construct(array $values)
    {
        foreach (['id','payer','amount','date'] as $key) {
            if (!isset($values[$key])) {
                throw new Exception("\$values[{$key}] should be set to construct a payment!");
            }
        }
        foreach (['id','amount','date'] as $key) {
            if (!is_scalar($values[$key])) {
                throw new Exception("\$values[{$key}] should be a scalar to construct a payment!");
            }
        }
        if (!$values['payer'] instanceof User) {
            throw new Exception("\$values['payer'] should be an instance of ".User::class." to construct a payment!");
        }

        $this->id = strval($values['id']);
        $this->payer = $values['payer'];
        $this->amount = floatval($values['amount']);
        $this->date = strval($values['date']);
    }

    /* === JsonSerializable interface === */
    public function jsonSerialize()
    {
        return [
            'id' => $this->id,
            'payer' => $this->payer,
            'amount' => $this->amount,
            'date' => $this->date,
            ];
    }
    /* === === */
}
