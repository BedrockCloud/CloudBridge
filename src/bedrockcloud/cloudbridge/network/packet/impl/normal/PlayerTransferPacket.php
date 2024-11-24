<?php

namespace bedrockcloud\cloudbridge\network\packet\impl\normal;

use bedrockcloud\cloudbridge\network\packet\CloudPacket;
use bedrockcloud\cloudbridge\network\packet\utils\PacketData;

class PlayerTransferPacket extends CloudPacket {

    public function __construct(
        private string $player = "",
        private string $server = ""
    ) {}

    public function encodePayload(PacketData $packetData): void {
        $packetData->write($this->player)->write($this->server);
    }

    public function decodePayload(PacketData $packetData): void {
        $this->player = $packetData->readString();
        $this->server = $packetData->readString();
    }

    public function handle(): void {}
}