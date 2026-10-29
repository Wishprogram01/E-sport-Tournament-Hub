<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Player extends Model
{
    public const CORE_ROLES = ['Jungler', 'Roamer', 'Mid Laner', 'Gold Laner', 'EXP Laner'];

    public const ROLES = [...self::CORE_ROLES, 'Sixth Man'];

    protected $fillable = ['team_id', 'ign', 'role', 'is_starter', 'kills', 'deaths', 'assists'];

    protected $appends = ['kda'];

    protected function casts(): array
    {
        return [
            'is_starter' => 'boolean',
            'kills' => 'integer',
            'deaths' => 'integer',
            'assists' => 'integer',
        ];
    }

    public function scopeOrderByKda(Builder $query): Builder
    {
        return $query->orderByRaw('(kills + assists) * 1.0 / (CASE WHEN deaths < 1 THEN 1 ELSE deaths END) DESC')
            ->orderByDesc('kills');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    protected function kda(): Attribute
    {
        return Attribute::get(
            fn () => round(($this->kills + $this->assists) / max(1, $this->deaths), 2)
        );
    }
}
