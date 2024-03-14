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

namespace YesWiki\Shop;

use YesWiki\Core\YesWikiAction;

class HelloAssoDirectPaymentAction extends YesWikiAction
{
    public function formatArguments($arg)
    {
        $newArgs = [];
        return $newArgs;
    }

    protected function formatString(array $arg, string $key): string
    {
        return (!empty($arg[$key]) && is_scalar($arg[$key]))
            ? strval($arg[$key])
            : '';
    }

    public function run()
    {
        return $this->render('@templates/alert-message.twig', [
            'type' => 'warning',
            'message' => 'Action en cours de conception'
        ]);
    }
}
