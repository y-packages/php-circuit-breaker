<?php

/**
 * YakNet \ Circuit-Breaker-PHP Demo
 * This script simulates a failing API and demonstrates the Circuit Breaker's behavior.
 */

// Simple Manual Autoloader
spl_autoload_register(function ($class) {
    $prefix = 'YakNet\\CircuitBreaker\\';
    $base_dir = __DIR__ . '/src/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    if (file_exists($file)) require $file;
});

use YakNet\CircuitBreaker\CircuitBreaker;
use YakNet\CircuitBreaker\Storage\FileStorage;
use YakNet\CircuitBreaker\Settings;
use YakNet\CircuitBreaker\Exception\CircuitOpenException;

// 1. Setup Storage (Temporary directory for demo)
$storage = new FileStorage(__DIR__ . '/cache');

// 2. Setup Settings (Aggressive thresholds for demo purposes)
$settings = new Settings(
    failureThreshold: 3, // Open after 3 failures
    retryTimeout: 10,     // Wait 10 seconds before half-open
    successThreshold: 1   // Close after 1 success in half-open
);

// 3. Create the Circuit Breaker for a fictional "Gemini-API"
$breaker = new CircuitBreaker('Gemini-API', $storage, $settings);

// Simulation Loop
echo "--- Starting Circuit Breaker Demo ---\n";

for ($i = 1; $i <= 10; $i++) {
    echo "Attempt #$i: ";
    
    try {
        $result = $breaker->run(function () use ($i) {
            // Simulate a failure for the first 5 attempts
            if ($i <= 5) {
                throw new \Exception("External API is down!");
            }
            return "SUCCESS from API";
        });
        echo "\e[32m" . $result . "\e[0m\n";
    } catch (CircuitOpenException $e) {
        echo "\e[33m[CIRCUIT OPEN] " . $e->getMessage() . "\e[0m\n";
        // Wait a bit if it's open, to see recovery later
        sleep(1);
    } catch (\Throwable $e) {
        echo "\e[31m[FAILURE] " . $e->getMessage() . "\e[0m\n";
    }
    
    usleep(500000); // 0.5s delay
}

echo "--- Demo Finished ---\n";
