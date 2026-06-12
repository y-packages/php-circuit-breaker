<?php

namespace YakNet\CircuitBreaker\Storage;

use YakNet\CircuitBreaker\CircuitState;

/**
 * Redis-based storage for Circuit Breaker metrics.
 * Suitable for distributed environments to share state between servers.
 */
class RedisStorage implements StorageInterface
{
    /**
     * @param \Redis $redis The Redis client instance (phpredis extension)
     * @param string $prefix Prefix for Redis keys
     */
    public function __construct(
        private readonly \Redis $redis,
        private readonly string $prefix = 'circuit_breaker:'
    ) {
    }

    private function getKey(string $serviceName): string
    {
        return $this->prefix . $serviceName;
    }

    public function getState(string $serviceName): CircuitState
    {
        $key = $this->getKey($serviceName);
        $state = $this->redis->hGet($key, 'state');

        if ($state === false) {
            return CircuitState::CLOSED;
        }

        return CircuitState::tryFrom($state) ?? CircuitState::CLOSED;
    }

    public function setState(string $serviceName, CircuitState $state): void
    {
        $key = $this->getKey($serviceName);
        $this->redis->hMSet($key, [
            'state' => $state->value,
            'last_change' => (string)time(),
        ]);
    }

    public function getFailures(string $serviceName): int
    {
        $key = $this->getKey($serviceName);
        $failures = $this->redis->hGet($key, 'failures');

        return $failures !== false ? (int)$failures : 0;
    }

    public function incrementFailures(string $serviceName): int
    {
        $key = $this->getKey($serviceName);
        return (int)$this->redis->hIncrBy($key, 'failures', 1);
    }

    public function clearFailures(string $serviceName): void
    {
        $key = $this->getKey($serviceName);
        $this->redis->hSet($key, 'failures', '0');
    }

    public function getLastStateChange(string $serviceName): int
    {
        $key = $this->getKey($serviceName);
        $lastChange = $this->redis->hGet($key, 'last_change');

        return $lastChange !== false ? (int)$lastChange : time();
    }
}
