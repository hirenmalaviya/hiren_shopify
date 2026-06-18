<?php

namespace Database\Factories;

use App\Enums\UploadStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Upload>
 */
class UploadFactory extends Factory
{
    public function definition(): array
    {
        return [
            'original_filename' => $this->faker->word().'.csv',
            'stored_path' => 'uploads/'.$this->faker->uuid().'.csv',
            'status' => UploadStatus::Pending,
            'total_rows' => 0,
            'processed_rows' => 0,
            'successful_rows' => 0,
            'failed_rows' => 0,
            'skipped_rows' => 0,
        ];
    }
}
