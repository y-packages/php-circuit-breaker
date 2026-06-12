<?php

namespace YakNet\CircuitBreaker\Storage;

use YakNet\CircuitBreaker\CircuitState;

/**
 * In-memory storage for Circuit Breaker metrics.
 * Useful for testing and short-lived CLI processes.
 */
class InMemoryStorage implements StorageInterface
{
    /** @var array<string, array{state: string, failures: int, last_change: int}> */
    private array $data = [];

    /**
     * @return array{state: string, failures: int, last_change: int}
     */
    private function getServiceData(string $serviceName): array
    {
        if (!isset($this->data[$serviceName])) {
            $this->data[$serviceName] = [
                'state' => CircuitState::CLOSED->value,
                'failures' => 0,
                'last_change' => time(),
            ];
        }

        return $this->data[$serviceName];
    }

    public function getState(string $serviceName): CircuitState
    {
        $serviceData = $this->getServiceData($serviceName);
        return CircuitState::from($serviceData['state']);
    }

    public function setState(string $serviceName, CircuitState $state): void
    {
        $serviceData = $this->getServiceData($serviceName);
        $serviceData['state'] = $state->value;
        $serviceData['last_change'] = time();
        $this->data[$serviceName] = $serviceData;
    }

    public function getFailures(string $serviceName): int
    {
        $serviceData = $this->getServiceData($serviceName);
        return $serviceData['failures'];
    }

    public function incrementFailures(string $serviceName): int
    {
        $serviceData = $this->getServiceData($serviceName);
        $serviceData['failures']++;
        $this->data[$serviceName] = $serviceData;
        return $serviceData['failures'];
    }

    public function clearFailures(string $serviceName): void
    {
        $serviceData = $this->getServiceData($serviceName);
        $serviceData['failures'] = 0;
        $this->data[$serviceName] = $serviceData;
    }

    public function getLastStateChange(string $serviceName): int
    {
        $serviceData = $this->getServiceData($serviceName);
        return $serviceData['last_change'];
    }
}
