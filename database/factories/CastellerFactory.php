<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Casteller;
use App\Models\Colla;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/** @extends Factory<Casteller> */
class CastellerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'colla_id'        => Colla::factory(),
            'alias'           => strtoupper(fake()->unique()->lexify('??????')),
            'name'            => fake()->firstName(),
            'last_name'       => fake()->lastName(),
            'email'           => fake()->unique()->safeEmail(),
            'password'        => Hash::make('password'),
            'status'          => 1,
            'height'          => fake()->numberBetween(155, 195),
            'weight'          => fake()->numberBetween(50, 110),
            'shoulder_height' => fake()->numberBetween(130, 170),
            'birthdate'       => fake()->dateTimeBetween('-60 years', '-16 years')->format('Y-m-d'),
            'gender'          => fake()->randomElement(['M', 'F', 'N']),
        ];
    }

    public function actiu(): static
    {
        return $this->state(['status' => 1]);
    }

    public function inactiu(): static
    {
        return $this->state(['status' => 2]);
    }
}
