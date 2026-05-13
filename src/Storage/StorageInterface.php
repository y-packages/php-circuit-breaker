<?php

namespace YakNet\CircuitBreaker\Storage;

use YakNet\CircuitBreaker\CircuitState;

/**
 * Interface for storing the circuit breaker state and metrics.
 */
interface StorageInterface
{
    /**
     * Get the current state of the circuit breaker.
     */
    public function getState(string $serviceName): CircuitState;

    /**
     * Set the current state of the circuit breaker.
     */
    public function setState(string $serviceName, CircuitState $state): void;

    /**
     * Get the number of failures for a service.
     */
    public function getFailures(string $serviceName): int;

    /**
     * Increment the failure count for a service.
     */
    public function incrementFailures(string $serviceName): int;

    /**
     * Clear failure count for a service.
     */
    public function clearFailures(string $serviceName): void;

    /**
     * Get the timestamp of the last state change.
     */
    public function getLastStateChange(string $serviceName): int;
}
