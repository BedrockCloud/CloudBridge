<?php

namespace bedrockcloud\cloudbridge\network\packet\impl\response;

use bedrockcloud\cloudbridge\network\packet\ResponsePacket;
use bedrockcloud\cloudbridge\network\packet\utils\PacketData;

class CheckPlayerMaintenanceResponsePacket extends ResponsePacket {

    public function __construct(private bool $value = false) {}

    public function encodePayload(PacketData $packetData): void {
        $packetData->write($this->value);
    }

    public function decodePayload(PacketData $packetData): void {
        $this->value = $packetData->readBool();
    }

    public function getValue(): bool {
        return $this->value;
    }

    public function handle(): void {}
}