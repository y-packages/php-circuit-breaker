<?php

namespace {
    if (!class_exists('Redis')) {
        class Redis
        {
            public function hGet(string $key, string $field): mixed { return null; }
            public function hSet(string $key, string $field, string $value): mixed { return null; }
            public function hMSet(string $key, array $dictionary): mixed { return null; }
            public function hIncrBy(string $key, string $field, int $value): mixed { return null; }
        }
    }
}

namespace YakNet\CircuitBreaker\Tests {

    use PHPUnit\Framework\TestCase;
    use YakNet\CircuitBreaker\CircuitState;
    use YakNet\CircuitBreaker\Storage\RedisStorage;

    class RedisStorageTest extends TestCase
    {
        public function testGetStateReturnsClosedByDefault(): void
        {
            $redis = $this->createMock(\Redis::class);
            $redis->expects($this->once())
                ->method('hGet')
                ->with('circuit_breaker:service-a', 'state')
                ->willReturn(false);

            $storage = new RedisStorage($redis);
            $this->assertEquals(CircuitState::CLOSED, $storage->getState('service-a'));
        }

        public function testGetStateReturnsStoredState(): void
        {
            $redis = $this->createMock(\Redis::class);
            $redis->expects($this->once())
                ->method('hGet')
                ->with('circuit_breaker:service-a', 'state')
                ->willReturn('open');

            $storage = new RedisStorage($redis);
            $this->assertEquals(CircuitState::OPEN, $storage->getState('service-a'));
        }

        public function testSetStateUpdatesRedis(): void
        {
            $redis = $this->createMock(\Redis::class);
            $redis->expects($this->once())
                ->method('hMSet')
                ->with(
                    'circuit_breaker:service-a',
                    $this->callback(function (array $data) {
                        return $data['state'] === 'open' && isset($data['last_change']);
                    })
                )
                ->willReturn(true);

            $storage = new RedisStorage($redis);
            $storage->setState('service-a', CircuitState::OPEN);
        }

        public function testIncrementFailures(): void
        {
            $redis = $this->createMock(\Redis::class);
            $redis->expects($this->once())
                ->method('hIncrBy')
                ->with('circuit_breaker:service-a', 'failures', 1)
                ->willReturn(3);

            $storage = new RedisStorage($redis);
            $this->assertEquals(3, $storage->incrementFailures('service-a'));
        }

        public function testClearFailures(): void
        {
            $redis = $this->createMock(\Redis::class);
            $redis->expects($this->once())
                ->method('hSet')
                ->with('circuit_breaker:service-a', 'failures', '0')
                ->willReturn(1);

            $storage = new RedisStorage($redis);
            $storage->clearFailures('service-a');
        }
    }
}
