<?php

namespace Coollabsio\LaravelSaas\Database\Factories;

use Coollabsio\LaravelSaas\Models\Team;
use Coollabsio\LaravelSaas\Support\Billing;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Team>
 */
class TeamFactory extends Factory
{
    protected $model = Team::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $userModel = Billing::userModel();

        return [
            'name' => fake()->company(),
            'personal_team' => false,
            'owner_id' => $userModel::factory(),
        ];
    }

    public function personal(): static
    {
        return $this->state(fn (array $attributes) => [
            'personal_team' => true,
        ]);
    }
}
