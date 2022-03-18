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

class UserDefinition
{
    public $firstName;
    public $lastName;
    public $email;
    public $phoneNumber;
    public $nationality;
    public $address;
    public $addressComplement;
    public $postalCode;
    public $town;
    public $countryOfResidence;
    public $birthday; // UNIX timestamp

    public function __construct()
    {
        $this->firstName = null;
        $this->lastName = null;
        $this->email = null;
        $this->phoneNumber = null;
        $this->nationality = null;
        $this->address = null;
        $this->addressComplement = null;
        $this->postalCode = null;
        $this->town = null;
        $this->countryOfResidence = null;
        $this->birthday = null;
    }
}
