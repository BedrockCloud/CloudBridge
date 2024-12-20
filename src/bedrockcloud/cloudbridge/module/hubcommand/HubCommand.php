<?php

namespace bedrockcloud\cloudbridge\module\hubcommand;

use bedrockcloud\cloudbridge\api\CloudAPI;
use bedrockcloud\cloudbridge\api\object\template\Template;
use bedrockcloud\cloudbridge\language\Language;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;

final class HubCommand extends Command {

    public function __construct() {
        parent::__construct("hub", Language::current()->translate("inGame.command.description.hub"), "/hub", ["lobby"]);
        $this->setPermission(DefaultPermissions::ROOT_USER);
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): bool {
        if ($sender instanceof Player) {
            if (!CloudAPI::templateProvider()->current()->isLobby()) {
                $availableTemplates = CloudAPI::templateProvider()->pickTemplates(fn(Template $template) => $template->isLobby() && !$template->isMaintenance());
                if (!empty($availableTemplates)) {
                    $pickedTemplate = $availableTemplates[array_rand($availableTemplates)];
                    if ($pickedTemplate !== null) {
                        $lobbyServer = CloudAPI::serverProvider()->getFreeServerByTemplate($pickedTemplate);
                        if ($lobbyServer !== null) {
                            $sender->sendMessage(Language::current()->translate("inGame.server.connect", $lobbyServer->getName()));
                            if (!CloudAPI::playerProvider()->transferPlayer($sender, $lobbyServer)) {
                                $sender->sendMessage(Language::current()->translate("inGame.server.connect.failed", $lobbyServer->getName()));
                            }
                        } else {
                            $sender->sendMessage(Language::current()->translate("inGame.server.not.found"));
                        }
                    } else {
                        $sender->sendMessage(Language::current()->translate("inGame.server.not.found"));
                    }
                } else {
                    $sender->sendMessage(Language::current()->translate("inGame.server.not.found"));
                }
            } else {
                $sender->sendMessage(Language::current()->translate("inGame.already.in.lobby"));
            }
        }
        return true;
    }
}