<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Colla;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Colla> */
class CollaFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->company();
        return [
            'name'      => $name,
            'shortname' => str($name)->lower()->slug()->toString(),
            'email'     => fake()->companyEmail(),
            'city'      => fake()->city(),
            'country'   => 'ES',
            'gdpr_version'           => '1.0',
            'gdpr_text'              => Colla::defaultGdprText($name, fake()->email()),
            'assaigs_weeks_visible'  => 2,
        ];
    }
}
