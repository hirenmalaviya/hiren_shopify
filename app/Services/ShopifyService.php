<?php

namespace App\Services;

use App\Exceptions\ShopifyException;
use App\Models\Product;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Thin client over the Shopify Admin GraphQL API.
 *
 * Product import uses a robust multi-step flow that matches the 2024-10 schema:
 *   1. productCreate / productUpdate  (core fields + default variant)
 *   2. productVariantsBulkUpdate      (price, sku, weight, policy on the variant)
 *   3. inventorySetQuantities         (stock at a location)
 *   4. productCreateMedia             (image, on create only)
 *   5. collectionAddProducts          (add to the target collection — idempotent)
 */
class ShopifyService
{
    private const MAX_RETRIES = 4;

    private ?string $cachedLocationId = null;

    public function __construct(
        private readonly string $endpoint,
        private readonly string $token,
        private readonly ?string $collectionId,
        private readonly ?string $configuredLocationId = null,
    ) {}

    /* ------------------------------------------------------------------ */
    /* Core request                                                        */
    /* ------------------------------------------------------------------ */

    /**
     * Execute a GraphQL operation and return the `data` payload.
     *
     * @param  array<string, mixed>  $variables
     * @return array<string, mixed>
     *
     * @throws ShopifyException
     */
    public function request(string $query, array $variables = []): array
    {
        $attempt = 0;

        while (true) {
            $attempt++;

            $response = $this->client()->post($this->endpoint, [
                'query' => $query,
                'variables' => (object) $variables,
            ]);

            // Rate limited at the HTTP layer.
            if ($response->status() === 429) {
                if ($attempt > self::MAX_RETRIES) {
                    throw new ShopifyException('Shopify rate limit exceeded (HTTP 429).');
                }
                $this->sleepSeconds((float) ($response->header('Retry-After') ?: 2));

                continue;
            }

            if ($response->failed()) {
                throw new ShopifyException(
                    "Shopify HTTP error {$response->status()}: ".$response->body()
                );
            }

            $json = $response->json() ?? [];

            if (! empty($json['errors'])) {
                if ($this->isThrottled($json['errors']) && $attempt <= self::MAX_RETRIES) {
                    $this->sleepForThrottle($json);

                    continue;
                }

                throw new ShopifyException(
                    'Shopify GraphQL error: '.$this->summarize($json['errors']),
                    $json['errors']
                );
            }

            return $json['data'] ?? [];
        }
    }

    private function client(): PendingRequest
    {
        return Http::withHeaders([
            'X-Shopify-Access-Token' => $this->token,
            'Content-Type' => 'application/json',
        ])->timeout(30)->acceptJson();
    }

    /* ------------------------------------------------------------------ */
    /* High-level operations                                               */
    /* ------------------------------------------------------------------ */

    /**
     * Create or update a product based on whether one with the same handle/SKU
     * already exists. Adds it to the configured collection.
     *
     * @return array{product_id:string, variant_id:?string, action:string}
     */
    public function upsertProduct(Product $product): array
    {
        $existing = $this->findProductByHandleOrSku($product->handle, $product->sku);

        if ($existing) {
            $result = $this->updateProduct($existing['id'], $existing['variantId'], $product);
            $action = 'update';
        } else {
            $result = $this->createProduct($product);
            $action = 'create';
        }

        if ($this->collectionId) {
            $this->addToCollection($result['product_id']);
        }

        return [...$result, 'action' => $action];
    }

    /**
     * Find an existing product by handle, then by variant SKU.
     *
     * @return array{id:string, variantId:?string, inventoryItemId:?string}|null
     */
    public function findProductByHandleOrSku(?string $handle, ?string $sku): ?array
    {
        foreach (array_filter([
            $handle ? "handle:{$this->escapeQuery($handle)}" : null,
            $sku ? "sku:{$this->escapeQuery($sku)}" : null,
        ]) as $queryString) {
            $data = $this->request(self::FIND_PRODUCT_QUERY, ['query' => $queryString]);
            $node = $data['products']['edges'][0]['node'] ?? null;

            if ($node) {
                $variant = $node['variants']['edges'][0]['node'] ?? null;

                return [
                    'id' => $node['id'],
                    'variantId' => $variant['id'] ?? null,
                    'inventoryItemId' => $variant['inventoryItem']['id'] ?? null,
                ];
            }
        }

        return null;
    }

    /**
     * @return array{product_id:string, variant_id:?string}
     */
    public function createProduct(Product $product): array
    {
        $data = $this->request(self::PRODUCT_CREATE_MUTATION, [
            'input' => $this->productInput($product),
        ]);

        $this->assertNoUserErrors($data, 'productCreate');

        $node = $data['productCreate']['product'];
        $variant = $node['variants']['edges'][0]['node'] ?? null;

        $this->applyVariant($node['id'], $variant['id'] ?? null, $variant['inventoryItem']['id'] ?? null, $product);

        if ($product->image_src) {
            $this->attachImage($node['id'], $product->image_src, $product->image_alt_text);
        }

        return ['product_id' => $node['id'], 'variant_id' => $variant['id'] ?? null];
    }

    /**
     * @return array{product_id:string, variant_id:?string}
     */
    public function updateProduct(string $productId, ?string $variantId, Product $product): array
    {
        $data = $this->request(self::PRODUCT_UPDATE_MUTATION, [
            'input' => [...$this->productInput($product), 'id' => $productId],
        ]);

        $this->assertNoUserErrors($data, 'productUpdate');

        $inventoryItemId = null;
        if (! $variantId) {
            $found = $this->findProductByHandleOrSku($product->handle, $product->sku);
            $variantId = $found['variantId'] ?? null;
            $inventoryItemId = $found['inventoryItemId'] ?? null;
        }

        $this->applyVariant($productId, $variantId, $inventoryItemId, $product);

        return ['product_id' => $productId, 'variant_id' => $variantId];
    }

    /**
     * Update the variant (price/sku/weight/policy) and set inventory quantity.
     */
    private function applyVariant(string $productId, ?string $variantId, ?string $inventoryItemId, Product $product): void
    {
        if (! $variantId) {
            return;
        }

        $variantInput = [
            'id' => $variantId,
            'price' => (string) ($product->price ?? '0'),
            'inventoryItem' => array_filter([
                'sku' => $product->sku,
                'tracked' => $product->inventory_tracker !== null,
                'requiresShipping' => $product->requires_shipping,
                'measurement' => $product->weight !== null ? [
                    'weight' => [
                        'value' => (float) $product->weight,
                        'unit' => $this->weightUnit($product->weight_unit),
                    ],
                ] : null,
            ], fn ($v) => $v !== null),
            'inventoryPolicy' => $this->inventoryPolicy($product->inventory_policy),
        ];

        if ($product->compare_at_price !== null) {
            $variantInput['compareAtPrice'] = (string) $product->compare_at_price;
        }

        $data = $this->request(self::VARIANTS_BULK_UPDATE_MUTATION, [
            'productId' => $productId,
            'variants' => [$variantInput],
        ]);

        $this->assertNoUserErrors($data, 'productVariantsBulkUpdate');

        // Resolve inventory item id from the response if we did not have it.
        $inventoryItemId ??= $data['productVariantsBulkUpdate']['productVariants'][0]['inventoryItem']['id'] ?? null;

        if ($inventoryItemId && $product->inventory_tracker) {
            $this->setInventory($inventoryItemId, (int) $product->inventory_qty);
        }
    }

    public function setInventory(string $inventoryItemId, int $quantity): void
    {
        $locationId = $this->defaultLocationId();
        if (! $locationId) {
            return;
        }

        $data = $this->request(self::INVENTORY_SET_MUTATION, [
            'input' => [
                'name' => 'available',
                'reason' => 'correction',
                'ignoreCompareQuantity' => true,
                'quantities' => [[
                    'inventoryItemId' => $inventoryItemId,
                    'locationId' => $locationId,
                    'quantity' => $quantity,
                ]],
            ],
        ]);

        $this->assertNoUserErrors($data, 'inventorySetQuantities');
    }

    public function attachImage(string $productId, string $src, ?string $alt): void
    {
        $data = $this->request(self::CREATE_MEDIA_MUTATION, [
            'productId' => $productId,
            'media' => [[
                'originalSource' => $src,
                'alt' => $alt ?? '',
                'mediaContentType' => 'IMAGE',
            ]],
        ]);

        // Media errors are non-fatal to the import; log them but don't abort.
        $errors = $data['productCreateMedia']['mediaUserErrors'] ?? [];
        if (! empty($errors)) {
            Log::channel('stack')->warning('Shopify media upload returned errors', [
                'product_id' => $productId,
                'errors' => $errors,
            ]);
        }
    }

    public function addToCollection(string $productId): void
    {
        $data = $this->request(self::COLLECTION_ADD_MUTATION, [
            'id' => $this->gid('Collection', $this->collectionId),
            'productIds' => [$productId],
        ]);

        $this->assertNoUserErrors($data, 'collectionAddProducts');
    }

    /** Resolve and cache the location id used for inventory. */
    public function defaultLocationId(): ?string
    {
        if ($this->configuredLocationId) {
            return $this->gid('Location', $this->configuredLocationId);
        }

        if ($this->cachedLocationId !== null) {
            return $this->cachedLocationId;
        }

        $data = $this->request(self::LOCATIONS_QUERY);

        return $this->cachedLocationId = $data['locations']['edges'][0]['node']['id'] ?? null;
    }

    /* ------------------------------------------------------------------ */
    /* Helpers                                                             */
    /* ------------------------------------------------------------------ */

    /**
     * @return array<string, mixed>
     */
    private function productInput(Product $product): array
    {
        return array_filter([
            'title' => $product->title,
            'handle' => $product->handle,
            'descriptionHtml' => $product->body_html,
            'vendor' => $product->vendor,
            'productType' => $product->product_type,
            'tags' => $product->tagsArray(),
            'status' => $product->published ? 'ACTIVE' : 'DRAFT',
        ], fn ($v) => $v !== null && $v !== '');
    }

    private function gid(string $type, string|int|null $id): string
    {
        $id = (string) $id;

        return str_starts_with($id, 'gid://') ? $id : "gid://shopify/{$type}/{$id}";
    }

    private function weightUnit(?string $unit): string
    {
        return match (strtolower((string) $unit)) {
            'g' => 'GRAMS',
            'lb' => 'POUNDS',
            'oz' => 'OUNCES',
            default => 'KILOGRAMS',
        };
    }

    private function inventoryPolicy(?string $policy): string
    {
        return strtolower((string) $policy) === 'continue' ? 'CONTINUE' : 'DENY';
    }

    private function escapeQuery(string $value): string
    {
        return str_replace(['"', '\\'], ['', ''], $value);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function assertNoUserErrors(array $data, string $mutation): void
    {
        $errors = $data[$mutation]['userErrors'] ?? [];

        if (! empty($errors)) {
            throw new ShopifyException(
                "Shopify {$mutation} failed: ".$this->summarize($errors),
                $errors
            );
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $errors
     */
    private function isThrottled(array $errors): bool
    {
        foreach ($errors as $error) {
            if (($error['extensions']['code'] ?? null) === 'THROTTLED') {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $json
     */
    private function sleepForThrottle(array $json): void
    {
        $cost = $json['extensions']['cost'] ?? null;
        $available = $cost['throttleStatus']['currentlyAvailable'] ?? 0;
        $restoreRate = $cost['throttleStatus']['restoreRate'] ?? 50;
        $requested = $cost['requestedQueryCost'] ?? 1;

        $deficit = max(0, $requested - $available);
        $wait = $restoreRate > 0 ? ($deficit / $restoreRate) : 1;

        $this->sleepSeconds(max(1.0, $wait));
    }

    private function sleepSeconds(float $seconds): void
    {
        usleep((int) (min($seconds, 10) * 1_000_000));
    }

    /**
     * @param  array<int, array<string, mixed>>  $errors
     */
    private function summarize(array $errors): string
    {
        return collect($errors)
            ->map(fn ($e) => $e['message'] ?? json_encode($e))
            ->implode('; ');
    }

    /* ------------------------------------------------------------------ */
    /* GraphQL documents                                                   */
    /* ------------------------------------------------------------------ */

    private const FIND_PRODUCT_QUERY = <<<'GQL'
    query findProduct($query: String!) {
        products(first: 1, query: $query) {
            edges {
                node {
                    id
                    handle
                    variants(first: 1) {
                        edges { node { id inventoryItem { id } } }
                    }
                }
            }
        }
    }
    GQL;

    private const PRODUCT_CREATE_MUTATION = <<<'GQL'
    mutation productCreate($input: ProductInput!) {
        productCreate(input: $input) {
            product {
                id
                handle
                variants(first: 1) {
                    edges { node { id inventoryItem { id } } }
                }
            }
            userErrors { field message }
        }
    }
    GQL;

    private const PRODUCT_UPDATE_MUTATION = <<<'GQL'
    mutation productUpdate($input: ProductInput!) {
        productUpdate(input: $input) {
            product { id handle }
            userErrors { field message }
        }
    }
    GQL;

    private const VARIANTS_BULK_UPDATE_MUTATION = <<<'GQL'
    mutation productVariantsBulkUpdate($productId: ID!, $variants: [ProductVariantsBulkInput!]!) {
        productVariantsBulkUpdate(productId: $productId, variants: $variants) {
            productVariants { id inventoryItem { id } }
            userErrors { field message }
        }
    }
    GQL;

    private const INVENTORY_SET_MUTATION = <<<'GQL'
    mutation inventorySetQuantities($input: InventorySetQuantitiesInput!) {
        inventorySetQuantities(input: $input) {
            userErrors { field message }
        }
    }
    GQL;

    private const CREATE_MEDIA_MUTATION = <<<'GQL'
    mutation productCreateMedia($productId: ID!, $media: [CreateMediaInput!]!) {
        productCreateMedia(productId: $productId, media: $media) {
            media { alt mediaContentType status }
            mediaUserErrors { field message }
        }
    }
    GQL;

    private const COLLECTION_ADD_MUTATION = <<<'GQL'
    mutation collectionAddProducts($id: ID!, $productIds: [ID!]!) {
        collectionAddProducts(id: $id, productIds: $productIds) {
            collection { id }
            userErrors { field message }
        }
    }
    GQL;

    private const LOCATIONS_QUERY = <<<'GQL'
    query locations {
        locations(first: 1, query: "status:active") {
            edges { node { id name } }
        }
    }
    GQL;
}
