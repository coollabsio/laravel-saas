<?php

namespace Coollabsio\LaravelSaas\Models;

use Coollabsio\LaravelSaas\Concerns\HasBilling;
use Coollabsio\LaravelSaas\Database\Factories\TeamFactory;
use Coollabsio\LaravelSaas\Support\Billing;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Team extends Model
{
    /** @use HasFactory<TeamFactory> */
    use HasBilling, HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'personal_team',
        'owner_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'personal_team' => 'boolean',
        ];
    }

    protected static function newFactory(): TeamFactory
    {
        return TeamFactory::new();
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(Billing::userModel(), 'owner_id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(Billing::userModel(), 'team_user')
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * @return HasMany<TeamInvitation, $this>
     */
    public function invitations(): HasMany
    {
        return $this->hasMany(Billing::teamInvitationModel());
    }

    public function isPersonalTeam(): bool
    {
        return $this->personal_team;
    }

    public function isOwner($user): bool
    {
        return $this->owner_id === $user->id;
    }

    public function hasUser($user): bool
    {
        return $this->users()->where('user_id', $user->id)->exists();
    }
}
