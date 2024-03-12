<?php

/*
 * This file is part of the YesWiki Extension Shop.
 *
 * Authors : see README.md file that was distributed with this source code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

return [

    // actions/EditConfigAction.php
    'EDIT_CONFIG_GROUP_SHOP' => '"Shop" extension',
    'EDIT_CONFIG_HINT_SHOP[SERVICENAME]' => 'Payment system name (helloasso) - IN TEST',
    'EDIT_CONFIG_HINT_SHOP[MANGOPAY][CLIENTID]' => 'MangoPay client ID',
    'EDIT_CONFIG_HINT_SHOP[MANGOPAY][CLIENTAPIKEY]' => 'MangoPay client\'s api key',
    'EDIT_CONFIG_HINT_SHOP[HELLOASSO][CLIENTID]' => 'HelloAsso client ID',
    'EDIT_CONFIG_HINT_SHOP[HELLOASSO][CLIENTAPIKEY]' => 'HelloAsso client\'s api key',
    'EDIT_CONFIG_HINT_SHOP[HELLOASSO][ORGANIZATIONSLUG]' => 'Organization\s slug dans les urlsin url',
    'EDIT_CONFIG_HINT_SHOP[HELLOASSO][POSTAPITOKEN]' => 'API\'s key for HelloAsso notification url to YesWiki',
    'EDIT_CONFIG_HINT_SHOP[HELLOASSO][MINTIMEBETWEENCALLLS]' => 'Time between to calls (positive integer)',
        
    // actions/HelloAssoDirectPaymentAction.php
    'SHOP_HELLOASSO_DIRECT_PAYMENT_ERROR' => 'An error occured. The payment was not done. You should start a new time the operation.',
    'SHOP_HELLOASSO_DIRECT_PAYMENT_FIXED_DATA' => 'Fixed data',
    'SHOP_HELLOASSO_DIRECT_PAYMENT_HEADER' => "You will pay %{sum} with HelloAsso website.\n"
        ."Some information is required to proceed to the payment.\n"
        ."Could you fill the form below ?.",
    'SHOP_HELLOASSO_DIRECT_PAYMENT_INPUT_ADDRESS' => 'Payer\'s address',
    'SHOP_HELLOASSO_DIRECT_PAYMENT_INPUT_BIRTHDATE' => 'Payer\'s birth date',
    'SHOP_HELLOASSO_DIRECT_PAYMENT_INPUT_COUNTRY' => 'Payer\'s country',
    'SHOP_HELLOASSO_DIRECT_PAYMENT_INPUT_CITY' => 'Payer\'s city',
    'SHOP_HELLOASSO_DIRECT_PAYMENT_INPUT_EMAIL' => 'Payer\'s email',
    'SHOP_HELLOASSO_DIRECT_PAYMENT_INPUT_FIRSTNAME' => 'Payer\'s first name',
    'SHOP_HELLOASSO_DIRECT_PAYMENT_INPUT_LASTNAME' => 'Payer\'s last name',
    'SHOP_HELLOASSO_DIRECT_PAYMENT_INPUT_ZIPCODE' => 'Payer\'s zipcode',
    'SHOP_HELLOASSO_DIRECT_PAYMENT_NOT_STORED_DATA' => 'Data not stored on this website',
    'SHOP_HELLOASSO_DIRECT_PAYMENT_PAY' => 'Pay',
];
