<?php

namespace bedrockcloud\cloudbridge\network\packet\impl\normal;

use bedrockcloud\cloudbridge\CloudBridge;
use bedrockcloud\cloudbridge\network\Network;
use bedrockcloud\cloudbridge\network\packet\CloudPacket;

class KeepAlivePacket extends CloudPacket {

    public function handle(): void {
        CloudBridge::getInstance()->lastKeepALiveCheck = time();
        Network::getInstance()->sendPacket(new KeepALivePacket());
    }
}