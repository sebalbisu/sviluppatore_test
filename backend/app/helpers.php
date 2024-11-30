<?php

use App\Services\BackgroundJob\BackgroundJobService;
use App\Enums\JobPriority;

if (!function_exists('runBackgroundJob')) {
    function runBackgroundJob(
        string $classname,
        string $method,
        array $params = [],
        JobPriority $priority = JobPriority::MEDIUM,
        int $delay = 0,
        int $retryDelay = null,
        int $maxRetries = null,
        int $timeout = null,
    )
    {
        return (new BackgroundJobService())->create(
            $classname,
            $method,
            $params,
            $priority,
            $delay,
            $retryDelay,
            $maxRetries,
            $timeout
        );
    }
}
