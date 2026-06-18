<?php

namespace Database\Factories;

use App\Enums\ProductStatus;
use App\Models\Upload;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        $title = $this->faker->words(3, true);

        return [
            'upload_id' => Upload::factory(),
            'row_number' => $this->faker->numberBetween(1, 100),
            'handle' => str()->slug($title),
            'title' => ucfirst($title),
            'body_html' => '<p>'.$this->faker->sentence().'</p>',
            'vendor' => $this->faker->company(),
            'product_type' => $this->faker->word(),
            'tags' => implode(',', $this->faker->words(3)),
            'published' => true,
            'sku' => strtoupper($this->faker->bothify('???-###')),
            'price' => $this->faker->randomFloat(2, 5, 200),
            'compare_at_price' => $this->faker->randomFloat(2, 200, 300),
            'requires_shipping' => true,
            'taxable' => true,
            'inventory_tracker' => 'shopify',
            'inventory_qty' => $this->faker->numberBetween(0, 100),
            'inventory_policy' => 'deny',
            'fulfillment_service' => 'manual',
            'weight' => $this->faker->randomFloat(2, 0.1, 20),
            'weight_unit' => 'kg',
            'status' => ProductStatus::Pending,
        ];
    }
}
