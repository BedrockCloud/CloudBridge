<?php

namespace bedrockcloud\cloudbridge\event\player;

use pocketmine\event\Event;

class PlayerJoinNetworkEvent extends Event {

    public function __construct(
        protected string $player = ""
    ) {}

    /**
     * @return string
     */
    public function getPlayer(): string
    {
        return $this->player;
    }
}