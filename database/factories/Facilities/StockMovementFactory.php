<?php

namespace Database\Factories\Facilities;

use App\Models\Facilities\StockMovement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockMovement>
 */
class StockMovementFactory extends Factory
{
    protected $model = StockMovement::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'inventory_id' => 1,
            'type' => 'stock_in',
            'quantity' => fake()->numberBetween(1, 50),
            'adjustment_type' => null,
            'notes' => fake()->optional(0.7)->sentence(),
            'created_by' => fake()->name(),
        ];
    }

    /**
     * A stock-in record that increases inventory quantity.
     */
    public function stockIn(int $quantity, int $inventoryId): static
    {
        return $this->state(fn () => [
            'inventory_id' => $inventoryId,
            'type' => 'stock_in',
            'quantity' => $quantity,
            'adjustment_type' => null,
        ]);
    }

    /**
     * A stock-out record that decreases inventory quantity.
     */
    public function stockOut(int $quantity, int $inventoryId): static
    {
        return $this->state(fn () => [
            'inventory_id' => $inventoryId,
            'type' => 'stock_out',
            'quantity' => $quantity,
            'adjustment_type' => null,
        ]);
    }

    /**
     * A stock adjustment that increases inventory quantity.
     */
    public function adjustmentIncrease(int $quantity, int $inventoryId): static
    {
        return $this->state(fn () => [
            'inventory_id' => $inventoryId,
            'type' => 'adjustment',
            'quantity' => $quantity,
            'adjustment_type' => 'increase',
        ]);
    }

    /**
     * A stock adjustment that decreases inventory quantity.
     */
    public function adjustmentDecrease(int $quantity, int $inventoryId): static
    {
        return $this->state(fn () => [
            'inventory_id' => $inventoryId,
            'type' => 'adjustment',
            'quantity' => $quantity,
            'adjustment_type' => 'decrease',
        ]);
    }
}