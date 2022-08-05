<?php

/*
 * This file is part of the YesWiki Extension Shop.
 * Firstly, this file was created in extension ComsChange by Jérémy Dufraisse
 * https://github.com/J9rem/yeswiki-extension-comschange/
 *
 * Authors : see README.md file that was distributed with this source code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
 
namespace YesWiki\Shop\Entity;

class Event
{
    protected $data ;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function getData(): array
    {
        return $this->data;
    }
}
