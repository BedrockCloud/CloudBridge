<?php

namespace bedrockcloud\cloudbridge\form\sub;

use dktapps\pmforms\CustomForm;
use dktapps\pmforms\CustomFormResponse;
use dktapps\pmforms\element\Dropdown;
use dktapps\pmforms\element\Input;
use dktapps\pmforms\element\Slider;
use dktapps\pmforms\element\Toggle;
use dktapps\pmforms\MenuForm;
use dktapps\pmforms\MenuOption;
use bedrockcloud\cloudbridge\api\CloudAPI;
use bedrockcloud\cloudbridge\api\object\server\CloudServer;
use bedrockcloud\cloudbridge\api\object\template\Template;
use bedrockcloud\cloudbridge\form\selection\CloudSelectionForm;
use bedrockcloud\cloudbridge\language\Language;
use pocketmine\player\Player;

class CloudManageServersSubForm extends MenuForm {

    public function __construct() {
        parent::__construct(
            Language::current()->translate("inGame.ui.manage_server.title"),
            Language::current()->translate("inGame.ui.manage_server.text"),
            [
                new MenuOption(Language::current()->translate("inGame.ui.manage_server.button.start")),
                new MenuOption(Language::current()->translate("inGame.ui.manage_server.button.stop")),
                new MenuOption(Language::current()->translate("inGame.ui.manage_server.button.list")),
                new MenuOption(Language::current()->translate("inGame.ui.manage_server.button.info")),
            ],
            function(Player $player, int $data): void {
                if ($data == 0) {
                    $player->sendForm(new CloudSelectionForm(
                        new CustomForm(
                            Language::current()->translate("inGame.ui.manage_server.sub.start.title"),
                            [
                                new Input("name", Language::current()->translate("inGame.ui.manage_server.sub.start.name.text")),
                                new Slider("count", Language::current()->translate("inGame.ui.manage_server.sub.start.count.text"), 1, 10, 1.0, 1.0)
                            ],
                            function(Player $player, CustomFormResponse $response): void {
                                $player->chat("/cloud start " . $response->getString("name") . " " . $response->getFloat("count"));
                            }
                        ),
                        new CustomForm(
                            Language::current()->translate("inGame.ui.manage_server.sub.start.title"),
                            [
                                new Dropdown("name", Language::current()->translate("inGame.ui.manage_server.sub.start.dropdown.text"), array_map(fn(Template $template) => $template->getName(), CloudAPI::templateProvider()->getTemplates())),
                                new Slider("count", Language::current()->translate("inGame.ui.manage_server.sub.start.count.text"), 1, 10, 1.0, 1.0)
                            ],
                            function(Player $player, CustomFormResponse $response): void {
                                $template = array_values(CloudAPI::templateProvider()->getTemplates())[$response->getInt("name")] ?? null;
                                if ($template !== null) $player->chat("/cloud start " . $template->getName() . " " . $response->getFloat("count"));
                            }
                        )
                    ));
                } else if ($data == 1) {
                    $player->sendForm(new CloudSelectionForm(
                        new CustomForm(
                            Language::current()->translate("inGame.ui.manage_server.sub.stop.title"),
                            [new Input("name", Language::current()->translate("inGame.ui.manage_server.sub.stop.name.text"))],
                            function(Player $player, CustomFormResponse $response): void {
                                $player->chat("/cloud stop " . $response->getString("name"));
                            }
                        ),
                        new CustomForm(
                            Language::current()->translate("inGame.ui.manage_server.sub.stop.title"),
                            [
                                new Dropdown("name", Language::current()->translate("inGame.ui.manage_server.sub.stop.dropdown.text"), array_map(fn(CloudServer $server) => $server->getName(), CloudAPI::serverProvider()->getServers())),
                                new Toggle("template", Language::current()->translate("inGame.ui.manage_server.sub.stop.template_option.text")),
                                new Toggle("all", Language::current()->translate("inGame.ui.manage_server.sub.stop.all_option.text"))
                            ],
                            function(Player $player, CustomFormResponse $response): void {
                                $server = array_values(CloudAPI::serverProvider()->getServers())[$response->getInt("name")] ?? null;
                                if ($response->getBool("all")) {
                                    $player->chat("/cloud stop all");
                                } else if ($response->getBool("template")) {
                                    if ($server !== null) $player->chat("/cloud stop " . $server->getTemplate()->getName());
                                } else {
                                    if ($server !== null) $player->chat("/cloud stop " . $server->getName());
                                }
                            }
                        )
                    ));
                } else if ($data == 2) {
                    $player->chat("/cloud list servers");
                } else if ($data == 3) {
                    $player->sendForm(new CloudSelectionForm(
                        new CustomForm(
                            Language::current()->translate("inGame.ui.manage_server.sub.info.title"),
                            [new Input("name", Language::current()->translate("inGame.ui.manage_server.sub.info.name.text"))],
                            function(Player $player, CustomFormResponse $response): void {
                                $player->chat("/cloud info server " . $response->getString("name"));
                            }
                        ),
                        new CustomForm(
                            Language::current()->translate("inGame.ui.manage_server.sub.info.title"),
                            [new Dropdown("name", Language::current()->translate("inGame.ui.manage_server.sub.info.dropdown.text"), array_map(fn(CloudServer $server) => $server->getName(), CloudAPI::serverProvider()->getServers()))],
                            function(Player $player, CustomFormResponse $response): void {
                                $server = array_values(CloudAPI::serverProvider()->getServers())[$response->getInt("name")] ?? null;
                                if ($server !== null) $player->chat("/cloud info server " . $server->getName());
                            }
                        )
                    ));
                }
            }
        );
    }
}