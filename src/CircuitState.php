<?php

namespace YakNet\CircuitBreaker;

/**
 * Represents the state of the Circuit Breaker.
 */
enum CircuitState: string
{
    /**
     * The circuit is closed; requests are allowed to pass through.
     * This is the normal operating state.
     */
    case CLOSED = 'closed';

    /**
     * The circuit is open; requests are immediately blocked and fail-fast.
     * This state is entered when the failure threshold is exceeded.
     */
    case OPEN = 'open';

    /**
     * The circuit is half-open; a limited number of requests are allowed
     * to check if the underlying service has recovered.
     */
    case HALF_OPEN = 'half_open';
}
