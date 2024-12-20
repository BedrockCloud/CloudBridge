<?php

namespace bedrockcloud\cloudbridge\event\network;

use bedrockcloud\cloudbridge\util\Address;
use pocketmine\event\Event;

class NetworkConnectEvent extends Event {

    public function __construct(private readonly Address $address) {}

    public function getAddress(): Address {
        return $this->address;
    }
}