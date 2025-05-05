<?php

namespace Database\Factories;

use App\Models\File;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\File>
 */
class FileFactory extends Factory
{
    protected $model = File::class;
    
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $filename = $this->faker->word() . '.csv';
        $uniqueFilename = $filename . '-' . Str::random(8);
        $path = 'uploads/' . $uniqueFilename;

        return [
            'filename' => $filename,
            'unique_filename' => $uniqueFilename,
            'path' => $path,
            'status' => $this->faker->randomElement(['pending', 'processing', 'completed', 'failed']),
        ];
    }
}
