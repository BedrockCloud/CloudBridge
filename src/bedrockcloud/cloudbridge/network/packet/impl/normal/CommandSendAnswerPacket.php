<?php

namespace bedrockcloud\cloudbridge\network\packet\impl\normal;

use bedrockcloud\cloudbridge\network\packet\CloudPacket;
use bedrockcloud\cloudbridge\network\packet\impl\types\CommandExecutionResult;
use bedrockcloud\cloudbridge\network\packet\utils\PacketData;

class CommandSendAnswerPacket extends CloudPacket {

    public function __construct(private ?CommandExecutionResult $result = null) {}

    public function encodePayload(PacketData $packetData): void {
        $packetData->writeCommandExecutionResult($this->result);
    }

    public function decodePayload(PacketData $packetData): void {
        $this->result = $packetData->readCommandExecutionResult();
    }

    public function getResult(): ?CommandExecutionResult {
        return $this->result;
    }

    public function handle(): void {}
}