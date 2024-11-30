<?php

namespace bedrockcloud\cloudbridge\api\object\player;

use bedrockcloud\cloudbridge\api\CloudAPI;
use bedrockcloud\cloudbridge\api\object\server\CloudServer;
use bedrockcloud\cloudbridge\network\Network;
use bedrockcloud\cloudbridge\network\packet\impl\normal\PlayerKickPacket;
use bedrockcloud\cloudbridge\network\packet\impl\normal\PlayerTextPacket;
use bedrockcloud\cloudbridge\network\packet\impl\types\TextType;
use bedrockcloud\cloudbridge\util\Utils;
use pocketmine\player\Player;
use pocketmine\Server;

class CloudPlayer {

    public function __construct(
        private readonly string $name,
        private readonly string $host,
        private readonly string $address,
        private readonly string $xboxUserId,
        private readonly string $uniqueId,
        private ?CloudServer $currentServer = null,
        private ?CloudServer $currentProxy = null
    ) {}

    public function getName(): string {
        return $this->name;
    }

    public function getHost(): string {
        return $this->host;
    }

    public function getXboxUserId(): string {
        return $this->xboxUserId;
    }

    public function getUniqueId(): string {
        return $this->uniqueId;
    }

    public function getCurrentServer(): ?CloudServer {
        return $this->currentServer;
    }

    public function getCurrentProxy(): ?CloudServer {
        return $this->currentProxy;
    }

    public function setCurrentServer(?CloudServer $currentServer): void {
        $this->currentServer = $currentServer;
    }

    public function setCurrentProxy(?CloudServer $currentProxy): void {
        $this->currentProxy = $currentProxy;
    }

    public function send(string $message, TextType $textType): void {
        Network::getInstance()->sendPacket(new PlayerTextPacket($this->getName(), $message, $textType));
    }

    public function sendMessage(string $message): void {
        $this->send($message, TextType::MESSAGE());
    }

    public function sendPopup(string $message): void {
        $this->send($message, TextType::POPUP());
    }

    public function sendTip(string $message): void {
        $this->send($message, TextType::TIP());
    }

    public function sendTitle(string $message): void {
        $this->send($message, TextType::TITLE());
    }

    public function sendActionBarMessage(string $message): void {
        $this->send($message, TextType::ACTION_BAR());
    }

    public function sendToastNotification(string $title, string $body): void {
        $this->send($title . "\n" .  $body, TextType::TOAST_NOTIFICATION());
    }

    public function kick(string $reason = ""): void {
        Network::getInstance()->sendPacket(new PlayerKickPacket(
            $this->name, $reason
        ));
    }

    public function getServerPlayer(): ?Player {
        return Server::getInstance()->getPlayerExact($this->name);
    }

    public function toArray(): array {
        return [
            "name" => $this->name,
            "host" => $this->host,
            "address" => $this->address,
            "xboxUserId" => $this->xboxUserId,
            "uniqueId" => $this->uniqueId,
            "currentServer" => $this->getCurrentServer()?->getName(),
            "currentProxy" => $this->getCurrentProxy()?->getName()
        ];
    }

    public static function fromArray(array $player): ?CloudPlayer {
        if (!Utils::containKeys($player, "name", "host", "address", "xboxUserId", "uniqueId")) return null;
        return new CloudPlayer(
            $player["name"],
            $player["host"],
            $player["address"],
            $player["xboxUserId"],
            $player["uniqueId"],
            (!isset($player["currentServer"]) ? null : CloudAPI::serverProvider()->getServer($player["currentServer"])),
            (!isset($player["currentProxy"]) ? null : CloudAPI::serverProvider()->getServer($player["currentProxy"]))
        );
    }

    public static function fromPlayer(Player $player): CloudPlayer {
        return new CloudPlayer($player->getName(), $player->getNetworkSession()->getIp() . ":" . $player->getNetworkSession()->getPort(), $player->getNetworkSession()->getIp(), $player->getXuid(), $player->getUniqueId()->toString(), CloudAPI::serverProvider()->current());
    }
}