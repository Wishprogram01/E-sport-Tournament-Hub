<?php

namespace App\Http\Resources;

use App\Models\MatchUp;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin MatchUp */
class MatchUpResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'round_number' => $this->round_number,
            'round_name' => $this->roundName(),
            'bracket_position' => $this->bracket_position,
            'best_of' => $this->best_of,
            'wins_required' => $this->winsRequired(),
            'status' => $this->status,
            'team_a' => $this->teamPayload($this->teamA, $this->team_a_score),
            'team_b' => $this->teamPayload($this->teamB, $this->team_b_score),
            'winner_id' => $this->winner_id,
            'next_match_id' => $this->next_match_id,
        ];
    }

    private function teamPayload($team, int $score): ?array
    {
        if ($team === null) {
            return null;
        }

        return [
            'id' => $team->id,
            'name' => $team->name,
            'tag' => $team->tag,
            'logo_url' => $team->logo_url,
            'score' => $score,
        ];
    }
}
