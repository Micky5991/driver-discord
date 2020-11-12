<?php

namespace BotMan\Drivers\Discord;

use Discord\Discord;
use BotMan\BotMan\BotMan;
use Illuminate\Support\Collection;
use Psr\Log\LoggerInterface;
use React\EventLoop\LoopInterface;
use BotMan\BotMan\Cache\ArrayCache;
use BotMan\BotMan\Interfaces\CacheInterface;
use BotMan\BotMan\Interfaces\StorageInterface;
use BotMan\BotMan\Storages\Drivers\FileStorage;

class Factory
{
    /**
     * Create a new BotMan instance.
     *
     * @param array $config
     * @param LoopInterface $loop
     * @param CacheInterface|null $cache
     * @param StorageInterface|null $storageDriver
     * @param LoggerInterface|null $logger
     * @return void
     */
    public function createForDiscord(
        array $config,
        LoopInterface $loop,
        CacheInterface $cache = null,
        StorageInterface $storageDriver = null,
        LoggerInterface $logger = null
    ) {
        $client = new Discord([
            'token' => Collection::make($config['discord'])->get('token'),
            'loop' => $loop,
            'logger' => $logger,
        ]);

        return $this->createUsingDiscord($config, $client, $cache, $storageDriver);
    }

    /**
     * Create a new BotMan instance.
     *
     * @param array $config
     * @param Discord $client
     * @param CacheInterface $cache
     * @param StorageInterface $storageDriver
     * @return BotMan
     * @internal param LoopInterface $loop
     */
    public function createUsingDiscord(
        array $config,
        Discord $client,
        CacheInterface $cache = null,
        StorageInterface $storageDriver = null
    ) {
        if (empty($cache)) {
            $cache = new ArrayCache();
        }

        if (empty($storageDriver)) {
            $storageDriver = new FileStorage(__DIR__);
        }

        $driver = new DiscordDriver($config, $client);
        $botman = new BotMan($cache, $driver, $config, $storageDriver);

        $client->on('message', function () use ($botman) {
            $botman->listen();
        });

        $client->on('ready', function ($discord) use ($driver) {
            $driver->connected();
        });

        return $botman;
    }
}
