<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when a Shopify GraphQL request fails — at the transport layer,
 * via top-level GraphQL `errors`, or via mutation `userErrors`.
 */
class ShopifyException extends RuntimeException
{
    /** @var array<int, array<string, mixed>> */
    public array $errors;

    /**
     * @param  array<int, array<string, mixed>>  $errors
     */
    public function __construct(string $message, array $errors = [])
    {
        parent::__construct($message);
        $this->errors = $errors;
    }
}
