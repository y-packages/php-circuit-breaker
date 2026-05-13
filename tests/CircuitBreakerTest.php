<?php

namespace YakNet\CircuitBreaker\Tests;

use PHPUnit\Framework\TestCase;
use YakNet\CircuitBreaker\CircuitBreaker;
use YakNet\CircuitBreaker\CircuitState;
use YakNet\CircuitBreaker\Settings;
use YakNet\CircuitBreaker\Storage\FileStorage;
use YakNet\CircuitBreaker\Exception\CircuitOpenException;

class CircuitBreakerTest extends TestCase
{
    private string $cacheDir;
    private FileStorage $storage;

    protected function setUp(): void
    {
        $this->cacheDir = sys_get_temp_dir() . '/circuit-breaker-test-' . uniqid();
        $this->storage = new FileStorage($this->cacheDir);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->cacheDir)) {
            $files = glob($this->cacheDir . '/*');
            foreach ($files as $file) {
                unlink($file);
            }
            rmdir($this->cacheDir);
        }
    }

    public function testCircuitStartsClosed(): void
    {
        $breaker = new CircuitBreaker('test-service', $this->storage);
        $this->assertEquals(CircuitState::CLOSED, $breaker->getEffectiveState());
    }

    public function testCircuitOpensAfterThreshold(): void
    {
        $settings = new Settings(failureThreshold: 2);
        $breaker = new CircuitBreaker('test-service', $this->storage, $settings);

        // First failure
        try {
            $breaker->run(fn() => throw new \Exception('Fail 1'));
        } catch (\Exception) {}

        $this->assertEquals(CircuitState::CLOSED, $breaker->getEffectiveState());

        // Second failure -> Opens
        try {
            $breaker->run(fn() => throw new \Exception('Fail 2'));
        } catch (\Exception) {}

        $this->assertEquals(CircuitState::OPEN, $breaker->getEffectiveState());
    }

    public function testThrowsCircuitOpenExceptionWhenOpen(): void
    {
        $settings = new Settings(failureThreshold: 1);
        $breaker = new CircuitBreaker('test-service', $this->storage, $settings);

        // Open the circuit
        try {
            $breaker->run(fn() => throw new \Exception('Fail'));
        } catch (\Exception) {}

        $this->expectException(CircuitOpenException::class);
        $breaker->run(fn() => 'Should not run');
    }
}
