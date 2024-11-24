<?php

namespace bedrockcloud\cloudbridge\network\packet\impl\normal;

use bedrockcloud\cloudbridge\api\CloudAPI;
use bedrockcloud\cloudbridge\api\object\player\CloudPlayer;
use bedrockcloud\cloudbridge\api\registry\Registry;
use bedrockcloud\cloudbridge\network\packet\CloudPacket;
use bedrockcloud\cloudbridge\network\packet\utils\PacketData;

class PlayerSyncPacket extends CloudPacket {

    public function __construct(
        private ?CloudPlayer $player = null,
        private bool $removal = false
    ) {}

    public function encodePayload(PacketData $packetData): void {
        $packetData->writePlayer($this->player);
        $packetData->write($this->removal);
    }

    public function decodePayload(PacketData $packetData): void {
        $this->player = $packetData->readPlayer();
        $this->removal = $packetData->readBool();
    }

    public function getPlayer(): ?CloudPlayer {
        return $this->player;
    }

    public function isRemoval(): bool {
        return $this->removal;
    }

    public function handle(): void {
        if (CloudAPI::playerProvider()->getPlayer($this->player->getName()) === null) {
            if (!$this->removal) Registry::registerPlayer($this->player);
        } else {
            if ($this->removal) {
                Registry::unregisterPlayer($this->player->getName());
            } else if ($this->player->getCurrentServer() !== null) {
                Registry::updatePlayer($this->player->getName(), $this->player->getCurrentServer()->getName());
            }
        }
    }
}