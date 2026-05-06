<?php

namespace App\Traits;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Support\Facades\Log;

trait LogsActivityAndSystem
{
    /**
     * Log an informational message with optional context.
     */
    protected function logInfo(string $message, array $context = []): void
    {
        Log::info($message, $context);
    }

    /**
     * Log a warning message with optional context.
     */
    protected function logWarning(string $message, array $context = []): void
    {
        Log::warning($message, $context);
    }

    /**
     * Log an error message with optional context.
     */
    protected function logError(string $message, array $context = []): void
    {
        Log::error($message, $context);
    }

    /**
     * Convenience wrapper for activity logging.
     */
    protected function logActivity(User $user, string $action, string $description, ?array $metadata = null): ActivityLog
    {
        return ActivityLog::log($user, $action, $description, $metadata);
    }
}

