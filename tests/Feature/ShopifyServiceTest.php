<?php

namespace Tests\Feature;

use App\Exceptions\ShopifyException;
use App\Models\Product;
use App\Services\ShopifyService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ShopifyServiceTest extends TestCase
{
    private const ENDPOINT = 'https://test.myshopify.com/admin/api/2024-10/graphql.json';

    private function service(): ShopifyService
    {
        // configuredLocationId set so inventory does not need a locations query.
        return new ShopifyService(self::ENDPOINT, 'token', '123', '999');
    }

    private function product(array $overrides = []): Product
    {
        return new Product(array_merge([
            'handle' => 'modern-desk-lamp',
            'title' => 'Modern Desk Lamp',
            'sku' => 'MDL-001',
            'price' => 39.99,
            'compare_at_price' => 49.99,
            'published' => true,
            'requires_shipping' => true,
            'taxable' => true,
            'inventory_tracker' => 'shopify',
            'inventory_qty' => 25,
            'inventory_policy' => 'deny',
            'weight' => 1.2,
            'weight_unit' => 'kg',
            'image_src' => 'https://example.com/lamp.jpg',
            'image_alt_text' => 'Lamp',
        ], $overrides));
    }

    private function op(Request $request): string
    {
        return $request->data()['query'] ?? '';
    }

    public function test_creates_product_when_none_exists(): void
    {
        Http::fake(function (Request $request) {
            $q = $this->op($request);

            return match (true) {
                str_contains($q, 'query findProduct') => Http::response(['data' => ['products' => ['edges' => []]]]),
                str_contains($q, 'productCreateMedia') => Http::response(['data' => ['productCreateMedia' => ['media' => [], 'mediaUserErrors' => []]]]),
                str_contains($q, 'mutation productCreate') => Http::response(['data' => ['productCreate' => [
                    'product' => ['id' => 'gid://shopify/Product/1', 'handle' => 'h', 'variants' => ['edges' => [['node' => [
                        'id' => 'gid://shopify/ProductVariant/11',
                        'inventoryItem' => ['id' => 'gid://shopify/InventoryItem/111'],
                    ]]]]],
                    'userErrors' => [],
                ]]]),
                str_contains($q, 'productVariantsBulkUpdate') => Http::response(['data' => ['productVariantsBulkUpdate' => [
                    'productVariants' => [['id' => 'gid://shopify/ProductVariant/11', 'inventoryItem' => ['id' => 'gid://shopify/InventoryItem/111']]],
                    'userErrors' => [],
                ]]]),
                str_contains($q, 'inventorySetQuantities') => Http::response(['data' => ['inventorySetQuantities' => ['userErrors' => []]]]),
                str_contains($q, 'collectionAddProducts') => Http::response(['data' => ['collectionAddProducts' => ['collection' => ['id' => 'gid://shopify/Collection/123'], 'userErrors' => []]]]),
                default => Http::response(['data' => []]),
            };
        });

        $result = $this->service()->upsertProduct($this->product());

        $this->assertSame('create', $result['action']);
        $this->assertSame('gid://shopify/Product/1', $result['product_id']);
        $this->assertSame('gid://shopify/ProductVariant/11', $result['variant_id']);

        Http::assertSent(fn (Request $r) => str_contains($this->op($r), 'mutation productCreate'));
        Http::assertSent(fn (Request $r) => str_contains($this->op($r), 'collectionAddProducts'));
        Http::assertSent(fn (Request $r) => str_contains($this->op($r), 'inventorySetQuantities'));
    }

    public function test_updates_product_when_it_exists(): void
    {
        Http::fake(function (Request $request) {
            $q = $this->op($request);

            return match (true) {
                str_contains($q, 'query findProduct') => Http::response(['data' => ['products' => ['edges' => [['node' => [
                    'id' => 'gid://shopify/Product/55',
                    'handle' => 'modern-desk-lamp',
                    'variants' => ['edges' => [['node' => ['id' => 'gid://shopify/ProductVariant/77', 'inventoryItem' => ['id' => 'gid://shopify/InventoryItem/777']]]]],
                ]]]]]]),
                str_contains($q, 'mutation productUpdate') => Http::response(['data' => ['productUpdate' => ['product' => ['id' => 'gid://shopify/Product/55', 'handle' => 'modern-desk-lamp'], 'userErrors' => []]]]),
                str_contains($q, 'productVariantsBulkUpdate') => Http::response(['data' => ['productVariantsBulkUpdate' => ['productVariants' => [['id' => 'gid://shopify/ProductVariant/77', 'inventoryItem' => ['id' => 'gid://shopify/InventoryItem/777']]], 'userErrors' => []]]]),
                str_contains($q, 'inventorySetQuantities') => Http::response(['data' => ['inventorySetQuantities' => ['userErrors' => []]]]),
                str_contains($q, 'collectionAddProducts') => Http::response(['data' => ['collectionAddProducts' => ['collection' => ['id' => 'gid://shopify/Collection/123'], 'userErrors' => []]]]),
                default => Http::response(['data' => []]),
            };
        });

        $result = $this->service()->upsertProduct($this->product());

        $this->assertSame('update', $result['action']);
        $this->assertSame('gid://shopify/Product/55', $result['product_id']);

        Http::assertSent(fn (Request $r) => str_contains($this->op($r), 'mutation productUpdate'));
        Http::assertNotSent(fn (Request $r) => str_contains($this->op($r), 'mutation productCreate'));
    }

    public function test_throws_on_user_errors(): void
    {
        Http::fake(function (Request $request) {
            $q = $this->op($request);

            return match (true) {
                str_contains($q, 'query findProduct') => Http::response(['data' => ['products' => ['edges' => []]]]),
                str_contains($q, 'mutation productCreate') => Http::response(['data' => ['productCreate' => [
                    'product' => null,
                    'userErrors' => [['field' => ['title'], 'message' => 'Title is invalid']],
                ]]]),
                default => Http::response(['data' => []]),
            };
        });

        $this->expectException(ShopifyException::class);
        $this->expectExceptionMessage('Title is invalid');

        $this->service()->createProduct($this->product());
    }

    public function test_retries_on_throttle_then_succeeds(): void
    {
        $calls = 0;
        Http::fake(function (Request $request) use (&$calls) {
            $calls++;
            if ($calls === 1) {
                return Http::response([
                    'errors' => [['message' => 'Throttled', 'extensions' => ['code' => 'THROTTLED']]],
                    'extensions' => ['cost' => ['requestedQueryCost' => 10, 'throttleStatus' => ['currentlyAvailable' => 0, 'restoreRate' => 50]]],
                ]);
            }

            return Http::response(['data' => ['shop' => ['name' => 'Test']]]);
        });

        $data = $this->service()->request('{ shop { name } }');

        $this->assertSame('Test', $data['shop']['name']);
        $this->assertSame(2, $calls);
    }
}
