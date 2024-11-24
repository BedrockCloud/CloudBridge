<?php

namespace bedrockcloud\cloudbridge\task;

use bedrockcloud\cloudbridge\api\CloudAPI;
use bedrockcloud\cloudbridge\api\object\server\status\ServerStatus;
use pocketmine\scheduler\Task;
use pocketmine\Server;

class ChangeStatusTask extends Task {

    public function onRun(): void {
        if (CloudAPI::serverProvider()->current()->getServerStatus() === ServerStatus::IN_GAME() || CloudAPI::serverProvider()->current()?->getServerStatus() === ServerStatus::STOPPING()) return;
        if (count(Server::getInstance()->getOnlinePlayers()) >= (CloudAPI::templateProvider()->current()->getMaxPlayerCount() ?? Server::getInstance()->getMaxPlayers())) {
            CloudAPI::getInstance()->changeStatus(ServerStatus::FULL());
        } else {
            if (CloudAPI::serverProvider()->current()->getServerStatus() === ServerStatus::FULL()) {
                CloudAPI::getInstance()->changeStatus(ServerStatus::ONLINE());
            }
        }
    }
}