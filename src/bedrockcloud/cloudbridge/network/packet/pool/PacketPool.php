<?php

namespace bedrockcloud\cloudbridge\network\packet\pool;

use bedrockcloud\cloudbridge\network\packet\impl\request\CheckPlayerExistsRequestPacket;
use bedrockcloud\cloudbridge\network\packet\impl\response\CheckPlayerExistsResponsePacket;
use GlobalLogger;
use bedrockcloud\cloudbridge\network\packet\CloudPacket;
use bedrockcloud\cloudbridge\network\packet\impl\normal\CloudNotifyPacket;
use bedrockcloud\cloudbridge\network\packet\impl\normal\CloudServerSavePacket;
use bedrockcloud\cloudbridge\network\packet\impl\normal\CloudServerStatusChangePacket;
use bedrockcloud\cloudbridge\network\packet\impl\normal\CloudServerSyncStoragePacket;
use bedrockcloud\cloudbridge\network\packet\impl\normal\CloudSyncStoragesPacket;
use bedrockcloud\cloudbridge\network\packet\impl\normal\CommandSendAnswerPacket;
use bedrockcloud\cloudbridge\network\packet\impl\normal\CommandSendPacket;
use bedrockcloud\cloudbridge\network\packet\impl\normal\ConsoleTextPacket;
use bedrockcloud\cloudbridge\network\packet\impl\normal\DisconnectPacket;
use bedrockcloud\cloudbridge\network\packet\impl\normal\KeepAlivePacket;
use bedrockcloud\cloudbridge\network\packet\impl\normal\LibrarySyncPacket;
use bedrockcloud\cloudbridge\network\packet\impl\normal\ModuleSyncPacket;
use bedrockcloud\cloudbridge\network\packet\impl\normal\PlayerConnectPacket;
use bedrockcloud\cloudbridge\network\packet\impl\normal\PlayerDisconnectPacket;
use bedrockcloud\cloudbridge\network\packet\impl\normal\PlayerKickPacket;
use bedrockcloud\cloudbridge\network\packet\impl\normal\PlayerNotifyUpdatePacket;
use bedrockcloud\cloudbridge\network\packet\impl\normal\PlayerSwitchServerPacket;
use bedrockcloud\cloudbridge\network\packet\impl\normal\PlayerSyncPacket;
use bedrockcloud\cloudbridge\network\packet\impl\normal\PlayerTextPacket;
use bedrockcloud\cloudbridge\network\packet\impl\normal\PlayerTransferPacket;
use bedrockcloud\cloudbridge\network\packet\impl\normal\ServerSyncPacket;
use bedrockcloud\cloudbridge\network\packet\impl\normal\TemplateSyncPacket;
use bedrockcloud\cloudbridge\network\packet\impl\request\CheckPlayerMaintenanceRequestPacket;
use bedrockcloud\cloudbridge\network\packet\impl\request\CheckPlayerNotifyRequestPacket;
use bedrockcloud\cloudbridge\network\packet\impl\request\CloudServerStartRequestPacket;
use bedrockcloud\cloudbridge\network\packet\impl\request\CloudServerStopRequestPacket;
use bedrockcloud\cloudbridge\network\packet\impl\request\LoginRequestPacket;
use bedrockcloud\cloudbridge\network\packet\impl\response\CheckPlayerMaintenanceResponsePacket;
use bedrockcloud\cloudbridge\network\packet\impl\response\CheckPlayerNotifyResponsePacket;
use bedrockcloud\cloudbridge\network\packet\impl\response\CloudServerStartResponsePacket;
use bedrockcloud\cloudbridge\network\packet\impl\response\CloudServerStopResponsePacket;
use bedrockcloud\cloudbridge\network\packet\impl\response\LoginResponsePacket;
use pocketmine\utils\SingletonTrait;
use ReflectionClass;
use ReflectionException;

class PacketPool {
    use SingletonTrait;

    /** @var array<CloudPacket> */
    private array $packets = [];

    public function __construct() {
        self::setInstance($this);
        $this->registerPacket(LoginRequestPacket::class);
        $this->registerPacket(LoginResponsePacket::class);
        $this->registerPacket(DisconnectPacket::class);
        $this->registerPacket(KeepAlivePacket::class);
        $this->registerPacket(CommandSendPacket::class);
        $this->registerPacket(CommandSendAnswerPacket::class);
        $this->registerPacket(ConsoleTextPacket::class);
        $this->registerPacket(PlayerConnectPacket::class);
        $this->registerPacket(PlayerDisconnectPacket::class);
        $this->registerPacket(PlayerTextPacket::class);
        $this->registerPacket(PlayerKickPacket::class);
        $this->registerPacket(PlayerNotifyUpdatePacket::class);
        $this->registerPacket(PlayerTransferPacket::class);
        $this->registerPacket(CloudServerSavePacket::class);
        $this->registerPacket(CloudServerStatusChangePacket::class);
        $this->registerPacket(PlayerSwitchServerPacket::class);
        $this->registerPacket(TemplateSyncPacket::class);
        $this->registerPacket(ServerSyncPacket::class);
        $this->registerPacket(PlayerSyncPacket::class);
        $this->registerPacket(CloudServerStartRequestPacket::class);
        $this->registerPacket(CloudServerStartResponsePacket::class);
        $this->registerPacket(CloudServerStopRequestPacket::class);
        $this->registerPacket(CloudServerStopResponsePacket::class);
        $this->registerPacket(CheckPlayerMaintenanceRequestPacket::class);
        $this->registerPacket(CheckPlayerMaintenanceResponsePacket::class);
        $this->registerPacket(CheckPlayerNotifyRequestPacket::class);
        $this->registerPacket(CheckPlayerNotifyResponsePacket::class);
        $this->registerPacket(CheckPlayerExistsRequestPacket::class);
        $this->registerPacket(CheckPlayerExistsResponsePacket::class);
        $this->registerPacket(CloudNotifyPacket::class);
        $this->registerPacket(ModuleSyncPacket::class);
        $this->registerPacket(LibrarySyncPacket::class);
        $this->registerPacket(CloudServerSyncStoragePacket::class);
        $this->registerPacket(CloudSyncStoragesPacket::class);
    }

    public function registerPacket(string $packetClass): void {
        if (!is_subclass_of($packetClass, CloudPacket::class)) return;
        try {
            $this->packets[(new ReflectionClass($packetClass))->getShortName()] = $packetClass;
        } catch (ReflectionException $e) {
            GlobalLogger::get()->logException($e);
        }
    }

    public function getPacketById(string $pid): ?CloudPacket {
        $get = $this->packets[$pid] ?? null;
        return ($get == null ? null : new $get());
    }

    public function getPackets(): array {
        return $this->packets;
    }
}