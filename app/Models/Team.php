<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Team extends Model
{
    protected $fillable = ['name', 'tag', 'logo_url', 'region'];

    public function players(): HasMany
    {
        return $this->hasMany(Player::class);
    }

    public function starters(): HasMany
    {
        return $this->players()->where('is_starter', true);
    }

    public function tournaments(): BelongsToMany
    {
        return $this->belongsToMany(Tournament::class)->withPivot('seed')->withTimestamps();
    }

    public function wonMatches(): HasMany
    {
        return $this->hasMany(MatchUp::class, 'winner_id');
    }
}
