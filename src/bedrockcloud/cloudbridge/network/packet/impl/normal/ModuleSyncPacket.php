<?php

namespace bedrockcloud\cloudbridge\network\packet\impl\normal;

use bedrockcloud\cloudbridge\module\ModuleManager;
use bedrockcloud\cloudbridge\network\packet\CloudPacket;
use bedrockcloud\cloudbridge\network\packet\utils\PacketData;
use bedrockcloud\cloudbridge\util\ModuleSettings;

class ModuleSyncPacket extends CloudPacket {

    private array $data = [];

    public function __construct() {}

    public function encodePayload(PacketData $packetData): void {
        $packetData->write($this->data);
    }

    public function decodePayload(PacketData $packetData): void {
        $this->data = $packetData->readArray();
    }

    public function getData(): array {
        return $this->data;
    }

    public function handle(): void {
        ModuleSettings::sync($this->data);
        ModuleManager::getInstance()->syncModuleStates();
    }
}