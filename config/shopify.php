<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Shopify Admin API
    |--------------------------------------------------------------------------
    |
    | Credentials and settings for the Shopify Admin GraphQL API. The store URL
    | should be the *.myshopify.com domain without protocol. All product imports
    | go through the GraphQL endpoint built from the store URL + API version.
    |
    */

    'store_url' => env('SHOPIFY_STORE_URL'),

    'access_token' => env('SHOPIFY_ACCESS_TOKEN'),

    'api_version' => env('SHOPIFY_API_VERSION', '2024-10'),

    // Custom collection every imported product is added to.
    'collection_id' => env('SHOPIFY_COLLECTION_ID'),

    // Optional: pin a location for inventory quantities. Null = auto-detect default.
    'location_id' => env('SHOPIFY_LOCATION_ID'),

    // Fully-qualified GraphQL endpoint, derived from the store URL + API version.
    'graphql_endpoint' => sprintf(
        'https://%s/admin/api/%s/graphql.json',
        env('SHOPIFY_STORE_URL'),
        env('SHOPIFY_API_VERSION', '2024-10'),
    ),

    /*
    |--------------------------------------------------------------------------
    | CSV Import limits
    |--------------------------------------------------------------------------
    */

    'import' => [
        'max_file_size_kb' => (int) env('IMPORT_MAX_FILE_SIZE_KB', 5120),
        'max_rows' => (int) env('IMPORT_MAX_ROWS', 5000),
    ],

];
