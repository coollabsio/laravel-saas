<?php

namespace Coollabsio\LaravelSaas\Database\Factories;

use Coollabsio\LaravelSaas\Models\Team;
use Coollabsio\LaravelSaas\Models\TeamInvitation;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<TeamInvitation>
 */
class TeamInvitationFactory extends Factory
{
    protected $model = TeamInvitation::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'email' => fake()->unique()->safeEmail(),
            'role' => 'member',
            'token' => Str::random(40),
        ];
    }
}
