<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Colla;
use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Event> */
class EventFactory extends Factory
{
    public function definition(): array
    {
        $start = fake()->dateTimeBetween('-1 month', '+2 months');
        return [
            'colla_id'   => Colla::factory(),
            'name'       => fake()->words(3, true),
            'type'       => fake()->randomElement([1, 2, 3]),
            'start_date' => $start,
            'open_date'  => (clone $start)->modify('-7 days'),
            'close_date' => $start,
            'visibility' => true,
            'companions' => false,
        ];
    }

    public function assaig(): static
    {
        return $this->state(['type' => 1, 'companions' => false]);
    }

    public function actuacio(): static
    {
        return $this->state(['type' => 2, 'companions' => true]);
    }

    public function passat(): static
    {
        return $this->state(function () {
            $start = fake()->dateTimeBetween('-2 months', '-1 week');
            return [
                'start_date' => $start,
                'open_date'  => (clone $start)->modify('-7 days'),
                'close_date' => $start,
            ];
        });
    }

    public function futur(): static
    {
        return $this->state(function () {
            $start = fake()->dateTimeBetween('+1 week', '+2 months');
            return [
                'start_date' => $start,
                'open_date'  => now(),
                'close_date' => (clone $start)->modify('-1 day'),
            ];
        });
    }
}
