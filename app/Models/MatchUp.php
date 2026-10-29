<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MatchUp extends Model
{
    public const STATUS_PENDING = 'PENDING';

    public const STATUS_LIVE = 'LIVE';

    public const STATUS_COMPLETED = 'COMPLETED';

    public const ROUND_NAMES = [
        1 => 'Quarter-Finals',
        2 => 'Semi-Finals',
        3 => 'Grand Finals',
    ];

    protected $table = 'match_ups';

    protected $fillable = [
        'tournament_id', 'team_a_id', 'team_b_id', 'team_a_score', 'team_b_score',
        'winner_id', 'round_number', 'bracket_position', 'best_of', 'status', 'next_match_id',
    ];

    protected function casts(): array
    {
        return [
            'tournament_id' => 'integer',
            'team_a_id' => 'integer',
            'team_b_id' => 'integer',
            'winner_id' => 'integer',
            'next_match_id' => 'integer',
            'team_a_score' => 'integer',
            'team_b_score' => 'integer',
            'round_number' => 'integer',
            'bracket_position' => 'integer',
            'best_of' => 'integer',
        ];
    }

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function teamA(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'team_a_id');
    }

    public function teamB(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'team_b_id');
    }

    public function winner(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'winner_id');
    }

    public function nextMatch(): BelongsTo
    {
        return $this->belongsTo(self::class, 'next_match_id');
    }

    public function previousMatches(): HasMany
    {
        return $this->hasMany(self::class, 'next_match_id');
    }

    public function winsRequired(): int
    {
        return (int) ceil($this->best_of / 2);
    }

    public function involvesTeam(int $teamId): bool
    {
        return in_array($teamId, [$this->team_a_id, $this->team_b_id], true);
    }

    public function roundName(): string
    {
        return self::ROUND_NAMES[$this->round_number] ?? "Round {$this->round_number}";
    }
}
