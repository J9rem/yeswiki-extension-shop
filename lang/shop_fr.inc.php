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
    'EDIT_CONFIG_GROUP_SHOP' => 'Extension "shop"',
    'EDIT_CONFIG_HINT_SHOP[SERVICENAME]' => 'Nom du système de paiement (helloasso) - EN TEST',
    'EDIT_CONFIG_HINT_SHOP[MANGOPAY][CLIENTID]' => 'Identifiant client MangoPay',
    'EDIT_CONFIG_HINT_SHOP[MANGOPAY][CLIENTAPIKEY]' => 'Clé api du client MangoPay',
    'EDIT_CONFIG_HINT_SHOP[HELLOASSO][CLIENTID]' => 'Identifiant client HelloAsso',
    'EDIT_CONFIG_HINT_SHOP[HELLOASSO][CLIENTAPIKEY]' => 'Clé api du client HelloAsso',
    'EDIT_CONFIG_HINT_SHOP[HELLOASSO][ORGANIZATIONSLUG]' => 'Identifiant de l\'organisation dans les urls',
    'EDIT_CONFIG_HINT_SHOP[HELLOASSO][POSTAPITOKEN]' => 'Clé api à fournir à HelloAsso pour l\'url de notification YesWiki',
    'EDIT_CONFIG_HINT_SHOP[HELLOASSO][MINTIMEBETWEENCALLLS]' => 'Temps entre deux appels (entier positif)',
    
    // actions/HelloAssoDirectPaymentAction.php
    'SHOP_HELLOASSO_DIRECT_PAYMENT_ERROR' => 'Une erreur est survenue. Le paiment n\'a pas été réalisé. Veuillez refaire la démarche.',
    'SHOP_HELLOASSO_DIRECT_PAYMENT_FIXED_DATA' => 'Donnée non modifiable',
    'SHOP_HELLOASSO_DIRECT_PAYMENT_HEADER' => "Vous allez payer %{sum} à l'aide du site de HelloAsso.\n"
        ."Certaines informations sont nécessaires pour procéder au paiement avec ce site.\n"
        ."Veuillez compléter les informations manquantes ci-dessous.",
    'SHOP_HELLOASSO_DIRECT_PAYMENT_INPUT_ADDRESS' => 'Adresse du payeur',
    'SHOP_HELLOASSO_DIRECT_PAYMENT_INPUT_BIRTHDATE' => 'Date de naissance du payeur',
    'SHOP_HELLOASSO_DIRECT_PAYMENT_INPUT_CITY' => 'Ville du payeur',
    'SHOP_HELLOASSO_DIRECT_PAYMENT_INPUT_COUNTRY' => 'Pays du payeur',
    'SHOP_HELLOASSO_DIRECT_PAYMENT_INPUT_EMAIL' => 'E-mail du payeur',
    'SHOP_HELLOASSO_DIRECT_PAYMENT_INPUT_FIRSTNAME' => 'Prénom du payeur',
    'SHOP_HELLOASSO_DIRECT_PAYMENT_INPUT_LASTNAME' => 'Nom du payeur',
    'SHOP_HELLOASSO_DIRECT_PAYMENT_INPUT_ZIPCODE' => 'Code postal du payeur',
    'SHOP_HELLOASSO_DIRECT_PAYMENT_NOT_STORED_DATA' => 'Donnée non sauvegardée sur ce site',
    'SHOP_HELLOASSO_DIRECT_PAYMENT_PAY' => 'Payer',
];
