<?php

namespace bedrockcloud\cloudbridge\network\packet\impl\normal;

use bedrockcloud\cloudbridge\network\packet\CloudPacket;
use bedrockcloud\cloudbridge\network\packet\utils\PacketData;
use bedrockcloud\cloudbridge\util\NotifyList;
use pocketmine\player\Player;
use pocketmine\Server;

class CloudNotifyPacket extends CloudPacket {

    public function __construct(private string $message = "") {}

    public function encodePayload(PacketData $packetData): void {
        $packetData->write($this->message);
    }

    public function decodePayload(PacketData $packetData): void {
        $this->message = $packetData->readString();
    }

    public function getMessage(): string {
        return $this->message;
    }

    public function handle(): void {
        foreach (array_filter(Server::getInstance()->getOnlinePlayers(), fn(Player $player) => $player->hasPermission("bedrockcloud.notify.receive") && NotifyList::exists($player)) as $player) {
            $player->sendMessage($this->message);
        }
    }
}