<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tournament extends Model
{
    public const STATUS_REGISTRATION = 'REGISTRATION';

    public const STATUS_LIVE = 'LIVE';

    public const STATUS_COMPLETED = 'COMPLETED';

    protected $attributes = [
        'game_title' => 'Mobile Legends: Bang Bang',
        'format' => 'Single Elimination',
        'status' => self::STATUS_REGISTRATION,
    ];

    protected $fillable = ['name', 'slug', 'game_title', 'prize_pool', 'format', 'status'];

    protected function casts(): array
    {
        return [
            'prize_pool' => 'decimal:2',
        ];
    }

    public function matchUps(): HasMany
    {
        return $this->hasMany(MatchUp::class)->orderBy('round_number')->orderBy('bracket_position');
    }

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class)->withPivot('seed')->withTimestamps();
    }

    public function roundName(int $roundNumber): string
    {
        return MatchUp::ROUND_NAMES[$roundNumber] ?? "Round {$roundNumber}";
    }
}
