<?php

/*
 * This file is part of the YesWiki Extension Shop.
 *
 * Authors : see README.md file that was distributed with this source code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace YesWiki\Shop;

use Exception;
use YesWiki\Shop\Entity\User;

interface PaymentSystemServiceInterface
{
    /**
     * loads the api for the current payment system
     *
     * @throws Exception if missing parameters to construct the API
     */
    public function loadApi();

    /**
     * get a User , create it if not already existing
     *
     * @param User
     *
     * @return mixed
     */
    public function getUser(User $user);
}
