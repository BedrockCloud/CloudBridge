<?php

namespace bedrockcloud\cloudbridge\module\npc\command;

use bedrockcloud\cloudbridge\module\npc\form\NPCMainForm;
use bedrockcloud\cloudbridge\language\Language;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class CloudNPCCommand extends Command {

    public function __construct() {
        parent::__construct("cloudnpc", Language::current()->translate("inGame.command.description.cloudnpc"), "/cloudnpc");
        $this->setPermission("bedrockcloud.command.cloudnpc");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): bool {
        if ($sender instanceof Player) {
            if ($this->testPermissionSilent($sender)) {
                $sender->sendForm(new NPCMainForm());
            } else $sender->sendMessage(Language::current()->translate("inGame.no.permission"));
        }
        return true;
    }
}