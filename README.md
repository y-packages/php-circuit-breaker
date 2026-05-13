# YakNet \ Circuit Breaker

[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.1-8892bf.svg?style=flat-square)](https://php.net)
[![License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)

A robust, resilient, and lightweight Circuit Breaker implementation for modern PHP applications. Prevent cascading failures and make your system more reliable when interacting with external APIs or microservices.

## 🚀 Features

- **Modern PHP 8.1+**: Leverages Enums, Readonly properties, and strictly typed logic.
- **Fail-Fast Strategy**: Immediately blocks requests to failing services to save resources and allow recovery.
- **Three-State Management**: Full support for `CLOSED`, `OPEN`, and `HALF_OPEN` states.
- **Storage Agnostic**: Comes with `FileStorage` out of the box, with an interface to easily add Redis or Memcached.
- **Developer Friendly**: Simple API and zero-dependency core.

## 📦 Installation

```bash
composer require yaknet/circuit-breaker
```

## 🛠 Usage

### Basic Example

```php
use YakNet\CircuitBreaker\CircuitBreaker;
use YakNet\CircuitBreaker\Storage\FileStorage;
use YakNet\CircuitBreaker\Settings;

// 1. Initialize Storage
$storage = new FileStorage(__DIR__ . '/cache');

// 2. Configure Settings (Optional)
$settings = new Settings(
    failureThreshold: 5,
    retryTimeout: 60
);

// 3. Create the Breaker
$breaker = new CircuitBreaker('GeminiAPI', $storage, $settings);

// 4. Run your risky operation
try {
    $result = $breaker->run(function() {
        // Your logic here (e.g. API call)
        return $api->fetchData();
    });
} catch (\YakNet\CircuitBreaker\Exception\CircuitOpenException $e) {
    // Service is currently down, handle gracefully
    echo "Service unavailable. Try again in " . $e->getMessage();
}
```

## 🔧 Core States

| State | Description |
| :--- | :--- |
| **CLOSED** | Normal operation. Requests flow through to the service. |
| **OPEN** | Failure threshold reached. Requests fail immediately without hitting the service. |
| **HALF_OPEN** | Trial period. A limited number of requests are allowed to check if the service recovered. |

## 🧪 Testing

```bash
composer test
```

## 📄 License

The MIT License (MIT). Please see [License File](LICENSE) for more information.

---
Developed with ❤️ by [YakNet](https://yak.net.tr)
