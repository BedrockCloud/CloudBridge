<?php

namespace bedrockcloud\cloudbridge\network\packet\impl\request;

use bedrockcloud\cloudbridge\network\packet\RequestPacket;
use bedrockcloud\cloudbridge\network\packet\utils\PacketData;

class CloudServerStartRequestPacket extends RequestPacket {

    public function __construct(
        private string $template = "",
        private int $count = 0
    ) {}

    public function encodePayload(PacketData $packetData): void {
        $packetData->write($this->template);
        $packetData->write($this->count);
    }

    public function decodePayload(PacketData $packetData): void {
        $this->template = $packetData->readString();
        $this->count = $packetData->readInt();
    }

    public function getTemplate(): string {
        return $this->template;
    }

    public function getCount(): int {
        return $this->count;
    }
}