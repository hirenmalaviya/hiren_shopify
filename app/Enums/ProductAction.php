<?php

namespace App\Enums;

/** Records whether an imported product was created or updated in Shopify. */
enum ProductAction: string
{
    case Create = 'create';
    case Update = 'update';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
