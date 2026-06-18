<?php

namespace App\Enums;

enum UploadStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Completed = 'completed';
    case CompletedWithErrors = 'completed_with_errors';
    case Failed = 'failed';

    /** Human-readable label for the UI. */
    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Processing => 'Processing',
            self::Completed => 'Completed',
            self::CompletedWithErrors => 'Completed with errors',
            self::Failed => 'Failed',
        };
    }

    /** PrimeVue severity for status badges. */
    public function severity(): string
    {
        return match ($this) {
            self::Pending => 'secondary',
            self::Processing => 'info',
            self::Completed => 'success',
            self::CompletedWithErrors => 'warn',
            self::Failed => 'danger',
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Completed, self::CompletedWithErrors, self::Failed], true);
    }
}
