<?php

namespace YakNet\CircuitBreaker\Tests;

use PHPUnit\Framework\TestCase;
use YakNet\CircuitBreaker\CircuitState;
use YakNet\CircuitBreaker\Storage\InMemoryStorage;

class InMemoryStorageTest extends TestCase
{
    public function testStorageOperations(): void
    {
        $storage = new InMemoryStorage();
        $service = 'test-service';

        // Check defaults
        $this->assertEquals(CircuitState::CLOSED, $storage->getState($service));
        $this->assertEquals(0, $storage->getFailures($service));
        $this->assertLessThanOrEqual(time(), $storage->getLastStateChange($service));

        // State changes
        $storage->setState($service, CircuitState::OPEN);
        $this->assertEquals(CircuitState::OPEN, $storage->getState($service));

        // Failures count
        $this->assertEquals(1, $storage->incrementFailures($service));
        $this->assertEquals(2, $storage->incrementFailures($service));
        $this->assertEquals(2, $storage->getFailures($service));

        // Clear failures
        $storage->clearFailures($service);
        $this->assertEquals(0, $storage->getFailures($service));
    }
}
