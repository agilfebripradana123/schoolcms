<?php

namespace Database\Factories\Facilities;

use App\Models\Facilities\Asset;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Asset>
 */
class AssetFactory extends Factory
{
    protected $model = Asset::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->numerify('AST-###'),
            'name' => fake()->words(3, true),
            'description' => fake()->optional(0.6)->sentence(),
            'category' => fake()->randomElement([
                'electronics',
                'furniture',
                'lab_equipment',
                'sports',
                'teaching_aids',
                'office',
                'other',
            ]),
            'quantity' => fake()->numberBetween(1, 20),
            'condition' => fake()->randomElement(['good', 'fair', 'poor', 'damaged']),
            'location' => fake()->optional(0.7)->randomElement([
                'Gudang Lab',
                'Gudang Olahraga',
                'Rak A1',
                'Rak B2',
                'Seluruh area',
            ]),
            'room_id' => null,
            'purchase_date' => fake()->dateTimeBetween('-8 years', '-6 months')->format('Y-m-d'),
            'purchase_price' => fake()->randomFloat(0, 100000, 9000000),
            'status' => 'active',
        ];
    }

    /**
     * Mark the asset as inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn () => ['status' => 'inactive']);
    }

    /**
     * Attach the asset to a room.
     */
    public function inRoom(int $roomId): static
    {
        return $this->state(fn () => ['room_id' => $roomId]);
    }
}