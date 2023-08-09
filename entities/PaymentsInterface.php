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

use Iterator;
use JsonSerializable;
use YesWiki\Shop\Entity\Payment;
use YesWiki\Shop\Entity\User;

interface PaymentsInterface extends Iterator, JsonSerializable
{
    /**
     * @param Payment[] $payments
     * @param array $otherData
     */
    public function __construct(array $payments, array $otherData);

    /**
     * get next token to retrive the following results if pagintation
     *
     * @return string $nextPageToken
     */
    public function getNextPageToken():string;

    /**
     * Iterator on payments only !
     * @return Payment[]
     */
    public function getPayments():array;
}
