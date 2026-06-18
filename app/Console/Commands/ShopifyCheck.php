<?php

namespace App\Console\Commands;

use App\Services\ShopifyService;
use Illuminate\Console\Command;

class ShopifyCheck extends Command
{
    protected $signature = 'shopify:check';

    protected $description = 'Verify Shopify credentials, default location, and the target collection.';

    public function handle(ShopifyService $shopify): int
    {
        try {
            $shop = $shopify->request('{ shop { name myshopifyDomain currencyCode } }');
            $this->info('Shop:       '.($shop['shop']['name'] ?? '?').' ('.($shop['shop']['myshopifyDomain'] ?? '?').')');

            $location = $shopify->defaultLocationId();
            $this->info('Location:   '.($location ?: 'none found'));

            $collectionId = config('shopify.collection_id');
            if ($collectionId) {
                $node = $shopify->request(
                    'query($id: ID!){ node(id:$id){ ... on Collection { id title productsCount { count } ruleSet { appliedDisjunctively } } } }',
                    ['id' => "gid://shopify/Collection/{$collectionId}"]
                );
                $collection = $node['node'] ?? null;

                if (! $collection) {
                    $this->error("Collection {$collectionId} not found.");

                    return self::FAILURE;
                }

                $isManual = ($collection['ruleSet'] ?? null) === null;
                $this->info('Collection: '.$collection['title'].' — '.($collection['productsCount']['count'] ?? 0).' products');
                $this->info('Type:       '.($isManual ? 'manual (✓ products can be added)' : 'smart/automated (✗ cannot add products manually)'));
            }

            $this->newLine();
            $this->info('✓ Shopify connection OK.');

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('✗ '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
