<?php

namespace App\Enums;

enum ProductStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Successful = 'successful';
    case Failed = 'failed';
    case Skipped = 'skipped';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    /** PrimeVue severity for status badges. */
    public function severity(): string
    {
        return match ($this) {
            self::Pending => 'secondary',
            self::Processing => 'info',
            self::Successful => 'success',
            self::Failed => 'danger',
            self::Skipped => 'warn',
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Successful, self::Failed, self::Skipped], true);
    }
}
