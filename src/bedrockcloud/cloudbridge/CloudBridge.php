<?php

namespace bedrockcloud\cloudbridge;

use GlobalLogger;
use bedrockcloud\cloudbridge\module\npc\listener\NPCListener;
use bedrockcloud\cloudbridge\module\sign\listener\SignListener;
use pmmp\thread\ThreadSafeArray;
use bedrockcloud\cloudbridge\api\CloudAPI;
use bedrockcloud\cloudbridge\command\CloudCommand;
use bedrockcloud\cloudbridge\command\CloudNotifyCommand;
use bedrockcloud\cloudbridge\command\TransferCommand;
use bedrockcloud\cloudbridge\event\network\NetworkPacketReceiveEvent;
use bedrockcloud\cloudbridge\language\Language;
use bedrockcloud\cloudbridge\listener\EventListener;
use bedrockcloud\cloudbridge\network\Network;
use bedrockcloud\cloudbridge\network\packet\handler\PacketSerializer;
use bedrockcloud\cloudbridge\network\packet\impl\normal\DisconnectPacket;
use bedrockcloud\cloudbridge\network\packet\impl\types\DisconnectReason;
use bedrockcloud\cloudbridge\network\packet\ResponsePacket;
use bedrockcloud\cloudbridge\network\request\RequestManager;
use bedrockcloud\cloudbridge\task\TimeoutTask;
use bedrockcloud\cloudbridge\util\Address;
use bedrockcloud\cloudbridge\util\GeneralSettings;
use pocketmine\permission\DefaultPermissions;
use pocketmine\permission\Permission;
use pocketmine\permission\PermissionManager;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\SingletonTrait;

class CloudBridge extends PluginBase {
    use SingletonTrait;

    public static function getPrefix(): string {
        return Language::current()->translate("inGame.prefix");
    }

    public array $signDelay = [];
    public float|int $lastKeepALiveCheck = 0.0;
    private Network $network;

    protected function onEnable(): void {
        self::setInstance($this);
        if (!file_exists($this->getDataFolder() . "skins/")) mkdir($this->getDataFolder() . "skins/");
        GeneralSettings::sync();
        Language::init();
        $networkBuffer = new ThreadSafeArray();
        $this->network = new Network(new Address("127.0.0.1", GeneralSettings::getNetworkPort()), $this->getServer()->getTickSleeper()->addNotifier(function() use ($networkBuffer): void {
            while (($buffer = $networkBuffer->shift()) !== null) {
                if (($packet = PacketSerializer::decode($buffer)) !== null) {
                    ($ev = new NetworkPacketReceiveEvent($packet))->call();
                    if ($ev->isCancelled()) return;
                    $packet->handle();

                    if ($packet instanceof ResponsePacket) {
                        RequestManager::getInstance()->callThen($packet);
                        RequestManager::getInstance()->removeRequest($packet->getRequestId());
                    }
                } else {
                    GlobalLogger::get()->warning("§cReceived an unknown packet from the cloud!");
                    GlobalLogger::get()->debug(GeneralSettings::isNetworkEncryptionEnabled() ? base64_decode($buffer) : $buffer);
                }
            }
        }), $networkBuffer);
        $this->network->start();

        $this->lastKeepALiveCheck = time();
        $this->getScheduler()->scheduleRepeatingTask(new TimeoutTask(), 20);

        $this->registerPermission("bedrockcloud.command.cloud", "bedrockcloud.command.notify", "bedrockcloud.notify.receive", "bedrockcloud.maintenance.bypass", "bedrockcloud.command.transfer", "bedrockcloud.command.cloudnpc", "bedrockcloud.command.template_group", "bedrockcloud.cloudsign.add", "bedrockcloud.cloudsign.remove");
        $this->getServer()->getPluginManager()->registerEvents(new EventListener(), $this);
        $this->getServer()->getPluginManager()->registerEvents(new NPCListener(), $this);
        $this->getServer()->getPluginManager()->registerEvents(new SignListener(), $this);
        $this->getServer()->getCommandMap()->registerAll("bedrockcloud", [
            new CloudNotifyCommand(),
            new CloudCommand(),
            new TransferCommand()
        ]);

        CloudAPI::getInstance()->processLogin();
    }

    public function registerPermission(string... $permissions): void {
        $operator = PermissionManager::getInstance()->getPermission(DefaultPermissions::ROOT_OPERATOR);
        if ($operator !== null) {
            foreach ($permissions as $permission) {
                DefaultPermissions::registerPermission(new Permission($permission), [$operator]);
            }
        }
    }

    protected function onDisable(): void {
        $this->network->sendPacket(new DisconnectPacket(DisconnectReason::SERVER_SHUTDOWN()));
        $this->network->close();
    }
}