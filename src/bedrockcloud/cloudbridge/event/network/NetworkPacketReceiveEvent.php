<?php

namespace bedrockcloud\cloudbridge\event\network;

use bedrockcloud\cloudbridge\network\packet\CloudPacket;
use pocketmine\event\Event;
use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;

class NetworkPacketReceiveEvent extends Event implements Cancellable {
    use CancellableTrait;

    public function __construct(private readonly CloudPacket $packet) {}

    public function getPacket(): CloudPacket {
        return $this->packet;
    }
}