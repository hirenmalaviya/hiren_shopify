<?php

namespace App\Models;

use App\Enums\ProductAction;
use App\Enums\ProductStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    /** @use HasFactory<\Database\Factories\ProductFactory> */
    use HasFactory;

    protected $fillable = [
        'upload_id',
        'row_number',
        'handle',
        'title',
        'body_html',
        'vendor',
        'product_type',
        'tags',
        'published',
        'sku',
        'price',
        'compare_at_price',
        'requires_shipping',
        'taxable',
        'inventory_tracker',
        'inventory_qty',
        'inventory_policy',
        'fulfillment_service',
        'weight',
        'weight_unit',
        'image_src',
        'image_position',
        'image_alt_text',
        'status',
        'action',
        'shopify_product_id',
        'shopify_variant_id',
        'error_message',
        'attempts',
    ];

    protected $casts = [
        'published' => 'boolean',
        'requires_shipping' => 'boolean',
        'taxable' => 'boolean',
        'price' => 'decimal:2',
        'compare_at_price' => 'decimal:2',
        'weight' => 'decimal:3',
        'inventory_qty' => 'integer',
        'image_position' => 'integer',
        'row_number' => 'integer',
        'attempts' => 'integer',
        'status' => ProductStatus::class,
        'action' => ProductAction::class,
    ];

    /** @return BelongsTo<Upload, $this> */
    public function upload(): BelongsTo
    {
        return $this->belongsTo(Upload::class);
    }

    /** @return HasMany<ImportLog, $this> */
    public function logs(): HasMany
    {
        return $this->hasMany(ImportLog::class);
    }

    /** Tags stored as a comma-separated string, exposed as an array for Shopify. */
    public function tagsArray(): array
    {
        return collect(explode(',', (string) $this->tags))
            ->map(fn (string $tag) => trim($tag))
            ->filter()
            ->values()
            ->all();
    }
}
