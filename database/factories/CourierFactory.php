<?php

namespace Database\Factories;

use App\Enums\VehicleType;
use Illuminate\Database\Eloquent\Factories\Factory;

class CourierFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'phone' => fake()->unique()->numerify('08##########'),
            'email' => fake()->unique()->safeEmail(),
            'address' => fake()->address(),
            'vehicle_type' => fake()->randomElement(VehicleType::cases()),
            'vehicle_plate' => strtoupper(fake()->bothify('? #### ???')),
            'level' => fake()->numberBetween(1, 5),
            'is_active' => true,
            'registered_at' => fake()->dateTimeBetween('-2 years'),
        ];
    }
}
