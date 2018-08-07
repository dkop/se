<?php

namespace src\Decorator;

use DateTime;
use Exception;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use src\Integration\DataProvider;
use src\Integration\DataProviderInterface;
use Symfony\Component\Lock\Factory;

class DecoratorManager implements DataProviderInterface
{
    /**
     * @var CacheItemPoolInterface
     */
    public $cache;
    /**
     * @var LoggerInterface
     */
    public $logger;
    /**
     * @var DataProvider
     */
    private $provider;
    /**
     * @var Factory
     */
    private $lockFactory;

    private $cacheTtl = '1 day';

    /**
     * @param DataProvider $provider
     * @param CacheItemPoolInterface $cache
     */
    public function __construct(DataProvider $provider, CacheItemPoolInterface $cache, Factory $lockFactory)
    {
        $this->provider = $provider;
        $this->cache = $cache;
        $this->lockFactory = $lockFactory;
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param string $ttl относительное время в формате php
     * @see http://php.net/manual/ru/datetime.formats.relative.php
     */
    public function setCacheTtl($ttl = '1 day')
    {
        $this->cacheTtl = $ttl;
    }

    /**
     * {@inheritdoc}
     */
    public function get(array $request)
    {
        try {
            $cacheKey = $this->getCacheKey($request);
            $cacheItem = $this->cache->getItem($cacheKey);
            if ($cacheItem->isHit()) {
                return $cacheItem->get();
            }

            $lock = $this->lockFactory->createLock($cacheKey);
            if ($lock->acquire(true)) {
                $this->cache->getItem($cacheKey);
                if ($cacheItem->isHit()) {
                    return $cacheItem->get();
                }

                $result = $this->provider->get($request);

                $cacheItem
                    ->set($result)
                    ->expiresAt(new DateTime('+' . $this->cacheTtl));

                return $result;
            }

        } catch (Exception $e) {
            // Обработка исключений не менялась, так как нет информации о том, какие исключения могут вывалиться
            // из кода, отвечающего за получение данных
            if ($this->logger) {
                $this->logger->critical($e->getMessage());
            }
        }

        // Возврат пустых данных в случае, если ничего не удалось не получить, не менялся, так как нет информации о том,
        // какова должна быть бизнес-логика процесса
        return [];
    }

    public function getCacheKey(array $input)
    {
        ksort($input);
        return md5(json_encode($input));
    }
}
