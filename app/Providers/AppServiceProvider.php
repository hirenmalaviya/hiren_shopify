<?php

namespace App\Providers;

use App\Services\ShopifyService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(ShopifyService::class, function () {
            return new ShopifyService(
                endpoint: config('shopify.graphql_endpoint'),
                token: config('shopify.access_token'),
                collectionId: config('shopify.collection_id'),
                configuredLocationId: config('shopify.location_id'),
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
