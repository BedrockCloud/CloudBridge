<?php

namespace bedrockcloud\cloudbridge\network\packet;

use bedrockcloud\cloudbridge\network\packet\utils\PacketData;
use ReflectionClass;

abstract class CloudPacket {

    private bool $encoded = false;

    public function encode(PacketData $packetData): void {
        if (!$this->encoded) {
            $this->encoded = true;
            $packetData->write((new ReflectionClass($this))->getShortName());
            $this->encodePayload($packetData);
        }
    }

    public function decode(PacketData $packetData): void {
        $packetData->readString();
        $this->decodePayload($packetData);
    }

    public function encodePayload(PacketData $packetData): void {}

    public function decodePayload(PacketData $packetData): void {}

    abstract public function handle(): void;

    public function isEncoded(): bool {
        return $this->encoded;
    }
}