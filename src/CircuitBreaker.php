<?php

namespace YakNet\CircuitBreaker;

use YakNet\CircuitBreaker\Exception\CircuitOpenException;
use YakNet\CircuitBreaker\Storage\StorageInterface;

/**
 * Main Circuit Breaker class.
 */
class CircuitBreaker
{
    public function __construct(
        private readonly string $serviceName,
        private readonly StorageInterface $storage,
        private readonly Settings $settings = new Settings()
    ) {
    }

    /**
     * Executes the given callable if the circuit is not open.
     * If a fallback is provided, it will be executed when the circuit is open
     * or when the callback execution fails.
     * 
     * @template T
     * @param callable(): T $callback
     * @param (callable(\Throwable): T)|null $fallback
     * @return T
     * @throws CircuitOpenException
     * @throws \Throwable
     */
    public function run(callable $callback, ?callable $fallback = null): mixed
    {
        try {
            $state = $this->getEffectiveState();

            if ($state === CircuitState::OPEN) {
                $lastChange = $this->storage->getLastStateChange($this->serviceName);
                $remaining = $this->settings->retryTimeout - (time() - $lastChange);
                throw new CircuitOpenException($this->serviceName, max(0, $remaining));
            }

            try {
                $result = $callback();
                $this->onSuccess();
                return $result;
            } catch (\Throwable $e) {
                $this->onFailure();
                throw $e;
            }
        } catch (\Throwable $e) {
            if ($fallback !== null) {
                return $fallback($e);
            }
            throw $e;
        }
    }

    /**
     * Determines the current state, considering timeouts.
     */
    public function getEffectiveState(): CircuitState
    {
        $state = $this->storage->getState($this->serviceName);

        if ($state === CircuitState::OPEN) {
            $lastChange = $this->storage->getLastStateChange($this->serviceName);
            if (time() - $lastChange >= $this->settings->retryTimeout) {
                $this->storage->setState($this->serviceName, CircuitState::HALF_OPEN);
                return CircuitState::HALF_OPEN;
            }
        }

        return $state;
    }

    private function onSuccess(): void
    {
        $state = $this->storage->getState($this->serviceName);

        if ($state === CircuitState::HALF_OPEN) {
            // In half-open, we could track successful attempts. 
            // For simplicity, we'll reset to CLOSED after the threshold is met via multiple calls 
            // or just reset immediately if we want a simpler logic.
            // Let's implement a simple version: reset to closed on success in half-open.
            $this->storage->setState($this->serviceName, CircuitState::CLOSED);
            $this->storage->clearFailures($this->serviceName);
        } elseif ($state === CircuitState::CLOSED) {
            // Keep failures at 0 if closed
            $this->storage->clearFailures($this->serviceName);
        }
    }

    private function onFailure(): void
    {
        $state = $this->storage->getState($this->serviceName);

        if ($state === CircuitState::CLOSED) {
            $failures = $this->storage->incrementFailures($this->serviceName);
            if ($failures >= $this->settings->failureThreshold) {
                $this->storage->setState($this->serviceName, CircuitState::OPEN);
            }
        } elseif ($state === CircuitState::HALF_OPEN) {
            // Any failure in half-open immediately opens the circuit again
            $this->storage->setState($this->serviceName, CircuitState::OPEN);
        }
    }
}
