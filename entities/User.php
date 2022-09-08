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

class User implements JsonSerializable
{
    public $firstName;
    public $lastName;
    public $compagny;
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
        $this->compagny = null;
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

    /* === JsonSerializable interface === */
    // change return of this method to keep compatible with php 7.3 (mixed is not managed)
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return [
            'firstName' => $this->firstName,
            'lastName' => $this->lastName,
            'compagny' => $this->compagny,
            'email' => $this->email,
            'phoneNumber' => $this->phoneNumber,
            'nationality' => $this->nationality,
            'address' => $this->address,
            'addressComplement' => $this->addressComplement,
            'postalCode' => $this->postalCode,
            'town' => $this->town,
            'countryOfResidence' => $this->countryOfResidence,
            'birthday' => $this->birthday,
            ];
    }
    /* === === */
}
