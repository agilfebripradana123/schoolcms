<?php

namespace Database\Factories\Facilities;

use App\Models\Facilities\Maintenance;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Maintenance>
 */
class MaintenanceFactory extends Factory
{
    protected $model = Maintenance::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->numerify('MNT-###'),
            'title' => fake()->words(5, true),
            'description' => fake()->optional(0.6)->sentence(),
            'asset_id' => null,
            'room_id' => null,
            'reported_by' => fake()->name(),
            'maintenance_type' => fake()->randomElement([
                'corrective',
                'preventive',
                'emergency',
                'inspection',
            ]),
            'priority' => fake()->randomElement(['low', 'medium', 'high', 'urgent']),
            'status' => 'pending',
            'scheduled_date' => null,
            'started_date' => null,
            'completed_date' => null,
            'estimated_cost' => null,
            'actual_cost' => null,
            'notes' => null,
            'resolution' => null,
        ];
    }

    /**
     * A pending work order: scheduled in the future, no dates recorded yet.
     */
    public function pending(): static
    {
        return $this->state(fn () => [
            'status' => 'pending',
            'scheduled_date' => now()->addDays(fake()->numberBetween(2, 14))->format('Y-m-d'),
            'started_date' => null,
            'completed_date' => null,
            'actual_cost' => null,
            'resolution' => null,
        ]);
    }

    /**
     * An in-progress work order: started, completed date absent.
     */
    public function inProgress(): static
    {
        return $this->state(function () {
            $scheduled = now()->subDays(fake()->numberBetween(3, 30));

            return [
                'status' => 'in_progress',
                'scheduled_date' => $scheduled->format('Y-m-d'),
                'started_date' => $scheduled->addDays(fake()->numberBetween(0, 2))->format('Y-m-d'),
                'completed_date' => null,
                'actual_cost' => null,
                'resolution' => null,
            ];
        });
    }

    /**
     * A completed work order: completed date set, actual cost filled.
     */
    public function completed(): static
    {
        return $this->state(function () {
            $scheduled = now()->subDays(fake()->numberBetween(30, 90));
            $started = (clone $scheduled)->addDays(fake()->numberBetween(0, 3));
            $completed = (clone $started)->addDays(fake()->numberBetween(0, 5));

            return [
                'status' => 'completed',
                'scheduled_date' => $scheduled->format('Y-m-d'),
                'started_date' => $started->format('Y-m-d'),
                'completed_date' => $completed->format('Y-m-d'),
                'actual_cost' => fake()->randomFloat(0, 0, 2000000),
                'resolution' => fake()->sentence(),
            ];
        });
    }

    /**
     * A cancelled work order: scheduled in the past, no other dates.
     */
    public function cancelled(): static
    {
        return $this->state(fn () => [
            'status' => 'cancelled',
            'scheduled_date' => now()->subDays(fake()->numberBetween(5, 60))->format('Y-m-d'),
            'started_date' => null,
            'completed_date' => null,
            'actual_cost' => null,
            'resolution' => null,
            'notes' => 'Ditunda dan dibatalkan.',
        ]);
    }
}