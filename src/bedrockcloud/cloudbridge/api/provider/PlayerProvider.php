<?php

namespace bedrockcloud\cloudbridge\api\provider;

use bedrockcloud\cloudbridge\api\object\player\CloudPlayer;
use bedrockcloud\cloudbridge\api\object\server\CloudServer;
use bedrockcloud\cloudbridge\api\object\server\status\ServerStatus;
use bedrockcloud\cloudbridge\api\object\template\Template;
use bedrockcloud\cloudbridge\api\registry\Registry;
use bedrockcloud\cloudbridge\network\Network;
use bedrockcloud\cloudbridge\network\packet\impl\normal\PlayerTransferPacket;
use pocketmine\network\mcpe\protocol\TransferPacket;
use pocketmine\player\Player;
use pocketmine\utils\Internet;

class PlayerProvider {

    public function transferPlayer(Player|CloudPlayer $player, CloudServer $server, bool $useCustomMaxPlayerCount = false): bool {
        $player = ($player instanceof Player ? $this->getPlayer($player->getName()) : $player);
        if ($player !== null) {
            $serverPlayer = $player->getServerPlayer();
            if (($useCustomMaxPlayerCount ? count($server->getCloudPlayers()) >= $server->getCloudServerData()->getMaxPlayers() : ($server->getServerStatus() === ServerStatus::IN_GAME() || $server->getServerStatus() === ServerStatus::FULL())) || $server->getServerStatus() === ServerStatus::STOPPING()) return false;
            if ($server->getTemplate()->isMaintenance() && !$serverPlayer?->hasPermission("bedrockcloud.maintenance.bypass")) return false;

            if ($player->getCurrentProxy() === null && $serverPlayer !== null) {
                return $serverPlayer->transfer(Internet::getInternalIP(), $server->getCloudServerData()->getPort());
            }

            if ($serverPlayer === null) {
                return Network::getInstance()->sendPacket(new PlayerTransferPacket($player->getName(), $server->getName()));
            }

            return $serverPlayer->getNetworkSession()->sendDataPacket(TransferPacket::create($server->getName(), $server->getCloudServerData()->getPort(), false));
        }
        return false;
    }

    /** @return array<CloudPlayer> */
    public function getPlayersOfTemplate(Template $template): array {
        return array_filter($this->getPlayers(), function(CloudPlayer $player) use($template): bool {
            if ($template->getTemplateType() == "PROXY") return ($player->getCurrentProxy() !== null && $player->getCurrentProxy()->getTemplate()->getName() == $template->getName());
            else return ($player->getCurrentServer() !== null && $player->getCurrentServer()->getTemplate()->getName() == $template->getName());
        });
    }

    public function getPlayer(string $name): ?CloudPlayer {
        return Registry::getPlayers()[$name] ?? null;
    }

    public function getPlayerByUniqueId(string $uniqueId): ?CloudPlayer {
        return array_values(array_filter(Registry::getPlayers(), fn(CloudPlayer $player) => $player->getUniqueId() == $uniqueId))[0] ?? null;
    }

    public function getPlayerByXboxUserId(string $xboxUserId): ?CloudPlayer {
        return array_values(array_filter(Registry::getPlayers(), fn(CloudPlayer $player) => $player->getXboxUserId() == $xboxUserId))[0] ?? null;
    }

    /** @return array<CloudPlayer> */
    public function getPlayers(): array {
        return Registry::getPlayers();
    }
}