<?php

namespace bedrockcloud\cloudbridge\network\packet\impl\response;

use bedrockcloud\cloudbridge\network\packet\impl\types\ErrorReason;
use bedrockcloud\cloudbridge\network\packet\ResponsePacket;
use bedrockcloud\cloudbridge\network\packet\utils\PacketData;

class CloudServerStopResponsePacket extends ResponsePacket {

    public function __construct(private ?ErrorReason $errorReason = null) {}

    public function encodePayload(PacketData $packetData): void {
        $packetData->writeErrorReason($this->errorReason);
    }

    public function decodePayload(PacketData $packetData): void {
        $this->errorReason = $packetData->readErrorReason();
    }

    public function getErrorReason(): ?ErrorReason {
        return $this->errorReason;
    }

    public function handle(): void {}
}