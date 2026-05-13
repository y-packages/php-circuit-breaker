<?php

namespace YakNet\CircuitBreaker\Exception;

/**
 * Exception thrown when the circuit is open.
 */
class CircuitOpenException extends \Exception
{
    public function __construct(string $serviceName, int $remainingTimeout)
    {
        parent::__construct(sprintf(
            'Circuit for service "%s" is OPEN. Please wait %d seconds before retrying.',
            $serviceName,
            $remainingTimeout
        ));
    }
}
