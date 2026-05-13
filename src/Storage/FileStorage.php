<?php

namespace YakNet\CircuitBreaker\Storage;

use YakNet\CircuitBreaker\CircuitState;
use YakNet\CircuitBreaker\Exception\StorageException;

/**
 * File-based storage for Circuit Breaker metrics.
 */
class FileStorage implements StorageInterface
{
    private string $directory;

    public function __construct(string $directory = null)
    {
        $this->directory = $directory ?? sys_get_temp_dir() . '/yaknet-circuit-breaker';
        
        if (!is_dir($this->directory)) {
            if (!mkdir($this->directory, 0777, true) && !is_dir($this->directory)) {
                throw new StorageException(sprintf('Directory "%s" was not created', $this->directory));
            }
        }
    }

    private function getFilePath(string $serviceName): string
    {
        return $this->directory . DIRECTORY_SEPARATOR . md5($serviceName) . '.json';
    }

    private function loadData(string $serviceName): array
    {
        $path = $this->getFilePath($serviceName);
        if (!file_exists($path)) {
            return [
                'state' => CircuitState::CLOSED->value,
                'failures' => 0,
                'last_change' => time()
            ];
        }

        $content = file_get_contents($path);
        return json_decode($content, true) ?: [];
    }

    private function saveData(string $serviceName, array $data): void
    {
        file_put_contents($this->getFilePath($serviceName), json_encode($data));
    }

    public function getState(string $serviceName): CircuitState
    {
        $data = $this->loadData($serviceName);
        return CircuitState::from($data['state'] ?? CircuitState::CLOSED->value);
    }

    public function setState(string $serviceName, CircuitState $state): void
    {
        $data = $this->loadData($serviceName);
        $data['state'] = $state->value;
        $data['last_change'] = time();
        $this->saveData($serviceName, $data);
    }

    public function getFailures(string $serviceName): int
    {
        $data = $this->loadData($serviceName);
        return (int)($data['failures'] ?? 0);
    }

    public function incrementFailures(string $serviceName): int
    {
        $data = $this->loadData($serviceName);
        $data['failures'] = ($data['failures'] ?? 0) + 1;
        $this->saveData($serviceName, $data);
        return $data['failures'];
    }

    public function clearFailures(string $serviceName): void
    {
        $data = $this->loadData($serviceName);
        $data['failures'] = 0;
        $this->saveData($serviceName, $data);
    }

    public function getLastStateChange(string $serviceName): int
    {
        $data = $this->loadData($serviceName);
        return (int)($data['last_change'] ?? time());
    }
}
