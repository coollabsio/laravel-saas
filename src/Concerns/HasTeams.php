<?php

namespace Coollabsio\LaravelSaas\Concerns;

use Coollabsio\LaravelSaas\Enums\TeamRole;
use Coollabsio\LaravelSaas\Support\Billing;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait HasTeams
{
    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Billing::teamModel(), 'team_user')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function ownedTeams(): HasMany
    {
        return $this->hasMany(Billing::teamModel(), 'owner_id');
    }

    public function currentTeam(): BelongsTo
    {
        return $this->belongsTo(Billing::teamModel(), 'current_team_id');
    }

    public function switchTeam($team): void
    {
        $this->update(['current_team_id' => $team->id]);
        $this->setRelation('currentTeam', $team);
    }

    public function isOwnerOf($team): bool
    {
        return $this->id === $team->owner_id;
    }

    public function teamRole($team): ?TeamRole
    {
        $pivot = $this->teams()->where('team_id', $team->id)->first()?->pivot;

        return $pivot ? TeamRole::from($pivot->role) : null;
    }

    public function isRootUser(): bool
    {
        if (! config('saas.self_hosted')) {
            return false;
        }

        return $this->ownedTeams()->where('is_root', true)->exists();
    }
}
