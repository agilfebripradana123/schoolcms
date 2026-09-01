<?php

namespace Database\Factories\Facilities;

use App\Models\Facilities\Inventory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Inventory>
 */
class InventoryFactory extends Factory
{
    protected $model = Inventory::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->numerify('INV-###'),
            'name' => fake()->words(3, true),
            'description' => fake()->optional(0.5)->sentence(),
            'category' => fake()->randomElement([
                'stationery',
                'electronics_supplies',
                'cleaning',
                'lab_supplies',
                'office_supplies',
                'other',
            ]),
            'unit' => fake()->randomElement(['pcs', 'box', 'rim', 'pak', 'botol', 'meter']),
            'quantity' => fake()->numberBetween(5, 50),
            'minimum_stock' => fake()->numberBetween(5, 15),
            'location' => fake()->optional(0.7)->randomElement([
                'Rak A1',
                'Rak A2',
                'Rak B1',
                'Rak B2',
                'Gudang Kebersihan',
            ]),
            'room_id' => null,
            'status' => 'active',
        ];
    }

    /**
     * Force the item to read as low stock (quantity <= minimum_stock).
     */
    public function lowStock(): static
    {
        return $this->state(function () {
            $minimum = fake()->numberBetween(5, 20);

            return [
                'quantity' => fake()->numberBetween(0, $minimum),
                'minimum_stock' => $minimum,
            ];
        });
    }

    /**
     * Force the item to read as normal stock (quantity > minimum_stock).
     */
    public function normalStock(): static
    {
        return $this->state(function () {
            $minimum = fake()->numberBetween(5, 15);

            return [
                'quantity' => fake()->numberBetween($minimum + 1, $minimum + 30),
                'minimum_stock' => $minimum,
            ];
        });
    }
}