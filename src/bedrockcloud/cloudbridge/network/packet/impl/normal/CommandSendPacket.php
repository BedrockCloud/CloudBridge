<?php

namespace bedrockcloud\cloudbridge\network\packet\impl\normal;

use bedrockcloud\cloudbridge\network\Network;
use bedrockcloud\cloudbridge\network\packet\CloudPacket;
use bedrockcloud\cloudbridge\network\packet\impl\types\CommandExecutionResult;
use bedrockcloud\cloudbridge\network\packet\utils\PacketData;
use bedrockcloud\cloudbridge\util\CloudCommandSender;
use pocketmine\Server;

class CommandSendPacket extends CloudPacket {

    public function __construct(private string $commandLine = "") {}

    public function encodePayload(PacketData $packetData): void {
        $packetData->write($this->commandLine);
    }

    public function decodePayload(PacketData $packetData): void {
        $this->commandLine = $packetData->readString();
    }

    public function getCommandLine(): string {
        return $this->commandLine;
    }

    public function handle(): void {
        Server::getInstance()->dispatchCommand($sender = new CloudCommandSender(), $this->commandLine);
        Network::getInstance()->sendPacket(new CommandSendAnswerPacket(
            new CommandExecutionResult($this->commandLine, $sender->getCachedMessages())
        ));
    }
}