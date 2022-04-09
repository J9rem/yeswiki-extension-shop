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

class ApiData implements JsonSerializable
{
    private $code;
    private $response;

    public function __construct(string $code, array $response)
    {
        $this->code = $code;
        $this->response = $response;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getResponse(): array
    {
        return $this->response;
    }

    /* === JsonSerializable interface === */
    public function jsonSerialize()
    {
        return [
            'code' => $this->getCode(),
            'response' => $this->getResponse(),
            ];
    }
    /* === === */
}
