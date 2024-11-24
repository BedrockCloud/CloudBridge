<?php

namespace bedrockcloud\cloudbridge\task;

use bedrockcloud\cloudbridge\network\packet\RequestPacket;
use bedrockcloud\cloudbridge\network\request\RequestManager;
use pocketmine\scheduler\Task;

class RequestCheckTask extends Task {

    public function __construct(private readonly RequestPacket $requestPacket) {}

    public function onRun(): void {
        if (isset(RequestManager::getInstance()->getRequests()[$this->requestPacket->getRequestId()])) {
            if (($this->requestPacket->getSentTime() + 10) < time()) {
                RequestManager::getInstance()->callFailure($this->requestPacket);
                RequestManager::getInstance()->removeRequest($this->requestPacket);
                $this->getHandler()->cancel();
            }
        } else {
            $this->getHandler()->cancel();
        }
    }

    public function getRequestPacket(): RequestPacket {
        return $this->requestPacket;
    }
}