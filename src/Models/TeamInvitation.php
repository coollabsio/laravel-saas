<?php

namespace Coollabsio\LaravelSaas\Models;

use Coollabsio\LaravelSaas\Database\Factories\TeamInvitationFactory;
use Coollabsio\LaravelSaas\Support\Billing;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeamInvitation extends Model
{
    /** @use HasFactory<TeamInvitationFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'team_id',
        'email',
        'role',
        'token',
    ];

    protected static function newFactory(): TeamInvitationFactory
    {
        return TeamInvitationFactory::new();
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Billing::teamModel());
    }
}
