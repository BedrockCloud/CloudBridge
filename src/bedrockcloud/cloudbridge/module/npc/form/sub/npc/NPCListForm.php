<?php

namespace bedrockcloud\cloudbridge\module\npc\form\sub\npc;

use dktapps\pmforms\MenuForm;
use dktapps\pmforms\MenuOption;
use bedrockcloud\cloudbridge\language\Language;
use bedrockcloud\cloudbridge\module\npc\CloudNPC;
use bedrockcloud\cloudbridge\module\npc\CloudNPCModule;
use bedrockcloud\cloudbridge\util\Utils;
use pocketmine\player\Player;

class NPCListForm extends MenuForm {

    public function __construct() {
        $npcs = array_values(CloudNPCModule::get()->getCloudNPCs());
        parent::__construct(
            Language::current()->translate("inGame.ui.cloudnpc.list.title"),
            Language::current()->translate("inGame.ui.cloudnpc.list.text", count($npcs)),
            array_map(fn(CloudNPC $npc) => new MenuOption("§e" . ($npc->hasTemplateGroup() ? $npc->getTemplate()->getDisplayName() : $npc->getTemplate()->getName()) . "\n§e" . str_replace(":", "§8:§e", Utils::convertToString($npc->getPosition()))), $npcs),
            function(Player $player, int $data) use($npcs): void {
                if (empty($npcs)) return;

                $cloudNPC = $npcs[$data] ?? null;
                if ($cloudNPC !== null) {
                    $player->sendForm(new NPCListViewForm($cloudNPC));
                }
            }
        );
    }
}