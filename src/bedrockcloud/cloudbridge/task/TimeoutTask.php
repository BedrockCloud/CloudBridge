<?php

namespace bedrockcloud\cloudbridge\task;

use GlobalLogger;
use bedrockcloud\cloudbridge\api\CloudAPI;
use bedrockcloud\cloudbridge\CloudBridge;
use bedrockcloud\cloudbridge\language\Language;
use pocketmine\scheduler\Task;
use pocketmine\Server;

class TimeoutTask extends Task {

    public function onRun(): void {
        if (!CloudAPI::getInstance()->isVerified()) return;
        if ((CloudBridge::getInstance()->lastKeepALiveCheck + 10) <= time()) {
            GlobalLogger::get()->warning(Language::current()->translate("inGame.server.timeout"));
            Server::getInstance()->shutdown();
        }
    }
}