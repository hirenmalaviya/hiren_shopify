<?php

namespace App\Enums;

enum LogLevel: string
{
    case Info = 'info';
    case Warning = 'warning';
    case Error = 'error';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    /** PrimeVue severity for log-level badges. */
    public function severity(): string
    {
        return match ($this) {
            self::Info => 'info',
            self::Warning => 'warn',
            self::Error => 'danger',
        };
    }
}
