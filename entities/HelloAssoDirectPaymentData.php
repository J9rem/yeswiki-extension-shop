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

use Symfony\Component\PasswordHasher\Hasher\PasswordHasherAwareInterface;

class HelloAssoDirectPaymentData implements PasswordHasherAwareInterface
{

    public function __construct()
    {
    }

    public function getPasswordHasherName(): ?string
    {
        return 'cookie';
    }
}
