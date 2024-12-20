<?php

namespace bedrockcloud\cloudbridge\network\packet\impl\normal;

use bedrockcloud\cloudbridge\api\CloudAPI;
use bedrockcloud\cloudbridge\api\object\template\Template;
use bedrockcloud\cloudbridge\api\registry\Registry;
use bedrockcloud\cloudbridge\network\packet\CloudPacket;
use bedrockcloud\cloudbridge\network\packet\utils\PacketData;

class TemplateSyncPacket extends CloudPacket {

    public function __construct(
        private ?Template $template = null,
        private bool $removal = false
    ) {}

    public function encodePayload(PacketData $packetData): void {
        $packetData->writeTemplate($this->template);
        $packetData->write($this->removal);
    }

    public function decodePayload(PacketData $packetData): void {
        $this->template = $packetData->readTemplate();
        $this->removal = $packetData->readBool();
    }

    public function getTemplate(): ?Template {
        return $this->template;
    }

    public function isRemoval(): bool {
        return $this->removal;
    }

    public function handle(): void {
        if (CloudAPI::templateProvider()->getTemplate($this->template->getName()) === null) {
            if (!$this->removal) Registry::registerTemplate($this->template);
        } else {
            if ($this->removal) {
                Registry::unregisterTemplate($this->template->getName());
            } else Registry::updateTemplate($this->template->getName(), $this->template->toArray());
        }
    }
}