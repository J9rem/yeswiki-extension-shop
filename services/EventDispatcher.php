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

namespace YesWiki\Shop\Service;

use Symfony\Component\EventDispatcher\EventDispatcher as SymfonyEventDispatcher;
use Throwable;
use YesWiki\Shop\Entity\Event;
use YesWiki\Wiki;

class EventDispatcher
{
    protected $eventDispatcher;
    protected $wiki;

    public function __construct(
        Wiki $wiki
    ) {
        $this->eventDispatcher = new SymfonyEventDispatcher();
        $this->wiki = $wiki;
    }

    /**
     * @param string $eventName
     * @param $callback
     * @param int $priority
     */
    public function addListener(string $eventName, $callback, int $priority = 0)
    {
        $this->eventDispatcher->addListener($eventName, $callback, $priority);
    }

    /**
     * @param string $eventName
     * @param array $data
     * @param array $errors
     */
    public function dispatch(string $eventName, array $data = []): array
    {
        try {
            $this->eventDispatcher->dispatch(new Event($data), $eventName);
            return [];
        } catch (Throwable $th) {
            $errors = ($this->wiki->userIsAdmin()) ? ['exception' => [
                'message' => $th->getMessage(),
                'file' => $th->getFile(),
                'line' => $th->getLine(),
                'trace' => $th->getTraceAsString()
            ]]: [];
            return $errors;
        }
    }
}
