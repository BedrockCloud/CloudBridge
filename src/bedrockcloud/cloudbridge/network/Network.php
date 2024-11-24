<?php

namespace bedrockcloud\cloudbridge\network;

use Exception;
use GlobalLogger;
use pmmp\thread\ThreadSafeArray;
use bedrockcloud\cloudbridge\event\network\NetworkCloseEvent;
use bedrockcloud\cloudbridge\event\network\NetworkConnectEvent;
use bedrockcloud\cloudbridge\event\network\NetworkPacketSendEvent;
use bedrockcloud\cloudbridge\network\packet\CloudPacket;
use bedrockcloud\cloudbridge\network\packet\handler\PacketSerializer;
use bedrockcloud\cloudbridge\util\Address;
use pocketmine\snooze\SleeperHandlerEntry;
use pocketmine\thread\Thread;
use pocketmine\utils\SingletonTrait;
use Socket;

class Network extends Thread {
    use SingletonTrait;

    private Socket $socket;
    private bool $isConnected = false;

    public function __construct(
        private readonly Address $address,
        private readonly SleeperHandlerEntry $sleeperHandler,
        private ThreadSafeArray $buffer
    ) {
        self::setInstance($this);
        GlobalLogger::get()->info("Attempting to connect to §e{$this->address}§r...");

        try {
            $this->connect();
        } catch (Exception $e) {
            $this->logCriticalConnectionError($e);
        }
    }

    public function onRun(): void {
        while ($this->isConnected) {
            if ($this->receivePacket($buffer, $senderAddress, $senderPort)) {
                $this->buffer[] = $buffer;
                $this->sleeperHandler->createNotifier()->wakeupSleeper();
            }
        }
    }

    public function connect(): void {
        if ($this->isConnected) {
            return;
        }

        GlobalLogger::get()->info("Connecting to §b{$this->address}§r...");

        $this->socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);

        if (@socket_connect($this->socket, $this->address->getAddress(), $this->address->getPort())) {
            $this->initializeSocket();
            $this->isConnected = true;

            (new NetworkConnectEvent($this->address))->call();

            GlobalLogger::get()->info("Successfully connected to §b{$this->address}§r!");
            GlobalLogger::get()->info("§cWaiting for incoming packets...");
        } else {
            throw new Exception("Socket connection failed: " . socket_strerror(socket_last_error()));
        }
    }

    private function initializeSocket(): void {
        socket_set_option($this->socket, SOL_SOCKET, SO_SNDBUF, 8 * 1024 * 1024); // 8MB send buffer
        socket_set_option($this->socket, SOL_SOCKET, SO_RCVBUF, 8 * 1024 * 1024); // 8MB receive buffer
    }

    public function sendPacket(CloudPacket $packet): bool {
        if (!$this->isConnected) {
            return false;
        }

        $encodedPacket = PacketSerializer::encode($packet);
        if ($encodedPacket === "") {
            return false;
        }

        $event = new NetworkPacketSendEvent($packet);
        $event->call();

        if ($event->isCancelled()) {
            return false;
        }

        return $this->sendData($encodedPacket);
    }

    private function sendData(string $data): bool {
        return @socket_send($this->socket, $data, strlen($data), 0) !== false;
    }

    private function receivePacket(?string &$buffer, ?string &$address, ?int &$port): bool {
        if (!$this->isConnected) {
            return false;
        }

        return @socket_recvfrom($this->socket, $buffer, 65535, 0, $address, $port) !== false;
    }

    public function close(): void {
        if (!$this->isConnected) {
            return;
        }

        $this->isConnected = false;

        (new NetworkCloseEvent())->call();
        @socket_close($this->socket);

        GlobalLogger::get()->info("Disconnected from §b{$this->address}§r.");
    }

    public function getAddress(): Address {
        return $this->address;
    }

    public function getBuffer(): ThreadSafeArray {
        return $this->buffer;
    }

    public function getSocket(): Socket {
        return $this->socket;
    }

    public function isConnected(): bool {
        return $this->isConnected;
    }

    private function logCriticalConnectionError(Exception $e): void {
        $error = socket_last_error($this->socket);
        $errorMessage = socket_strerror($error);

        GlobalLogger::get()->critical("Failed to connect to {$this->address}: $errorMessage");
        GlobalLogger::get()->logException($e);
    }
}