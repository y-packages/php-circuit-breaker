<?php

namespace YakNet\CircuitBreaker;

/**
 * Configuration settings for the Circuit Breaker.
 */
class Settings
{
    public function __construct(
        /**
         * Number of failures allowed before opening the circuit.
         */
        public readonly int $failureThreshold = 5,

        /**
         * Time in seconds to wait before moving from OPEN to HALF_OPEN.
         */
        public readonly int $retryTimeout = 60,

        /**
         * Number of successful requests required in HALF_OPEN to CLOSE the circuit.
         */
        public readonly int $successThreshold = 3
    ) {
    }
}
