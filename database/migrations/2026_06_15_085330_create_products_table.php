<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('upload_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('row_number');

            // Shopify product fields (mapped from CSV columns).
            $table->string('handle')->nullable();
            $table->string('title')->nullable();
            $table->longText('body_html')->nullable();
            $table->string('vendor')->nullable();
            $table->string('product_type')->nullable();
            $table->string('tags')->nullable();
            $table->boolean('published')->default(true);

            // Single-variant fields.
            $table->string('sku')->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->decimal('compare_at_price', 10, 2)->nullable();
            $table->boolean('requires_shipping')->default(true);
            $table->boolean('taxable')->default(true);
            $table->string('inventory_tracker')->nullable();
            $table->integer('inventory_qty')->default(0);
            $table->string('inventory_policy')->nullable();
            $table->string('fulfillment_service')->nullable();
            $table->decimal('weight', 8, 3)->nullable();
            $table->string('weight_unit')->nullable();

            // Image.
            $table->string('image_src', 1024)->nullable();
            $table->unsignedInteger('image_position')->nullable();
            $table->string('image_alt_text')->nullable();

            // Import tracking.
            $table->string('status')->default('pending')->index();
            $table->string('action')->nullable();
            $table->string('shopify_product_id')->nullable();
            $table->string('shopify_variant_id')->nullable();
            $table->text('error_message')->nullable();
            $table->unsignedTinyInteger('attempts')->default(0);

            $table->timestamps();

            $table->index(['upload_id', 'status']);
            $table->index('sku');
            $table->index('handle');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
