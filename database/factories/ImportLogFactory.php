<?php

namespace Database\Factories;

use App\Enums\LogLevel;
use App\Models\Upload;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\ImportLog>
 */
class ImportLogFactory extends Factory
{
    public function definition(): array
    {
        return [
            'upload_id' => Upload::factory(),
            'product_id' => null,
            'level' => LogLevel::Info,
            'message' => $this->faker->sentence(),
            'context' => ['sku' => strtoupper($this->faker->bothify('???-###'))],
        ];
    }
}
