<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{

    protected $model = Product::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name'          => fake()->words(5, true),
            'description'   => fake()->text(),
            'price'         => fake()->numberBetween(5, 50),
            'image'         => fake()->imageUrl(),
            'category'      => fake()->word(),
        ];
    }

}
