<?php

namespace bedrockcloud\cloudbridge\module\npc\task;

use bedrockcloud\cloudbridge\module\npc\CloudNPC;
use pocketmine\scheduler\Task;

class CloudNPCTickTask extends Task {

    public function __construct(private readonly CloudNPC $cloudNPC) {}

    public function onRun(): void {
        $this->cloudNPC->tick();
    }
}