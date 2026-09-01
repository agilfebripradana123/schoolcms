<?php

namespace Database\Factories\Facilities;

use App\Models\Facilities\Room;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Room>
 */
class RoomFactory extends Factory
{
    protected $model = Room::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->numerify('RM-###'),
            'name' => fake()->words(3, true),
            'capacity' => fake()->numberBetween(20, 40),
            'location' => fake()->randomElement([
                'Lantai 1, Timur',
                'Lantai 1, Barat',
                'Lantai 1, Utara',
                'Lantai 1, Selatan',
                'Lantai 2, Timur',
                'Lantai 2, Barat',
                'Lantai 2, Utara',
                'Lantai 2, Selatan',
                'Lantai 2, Tengah',
            ]),
            'has_computer' => fake()->boolean(30),
            'status' => 'active',
        ];
    }

    /**
     * Mark the room as inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn () => ['status' => 'inactive']);
    }

    /**
     * Mark the room as a computer-equipped room.
     */
    public function computerRoom(): static
    {
        return $this->state(fn () => ['has_computer' => true]);
    }
}