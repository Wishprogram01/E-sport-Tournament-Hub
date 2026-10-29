<?php

namespace App\Http\Resources;

use App\Models\MatchUp;
use App\Models\Tournament;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Tournament */
class BracketResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        $matches = $this->matchUps()->with(['teamA', 'teamB'])->get();
        $champion = $matches->firstWhere('next_match_id', null)?->winner;

        return [
            'tournament' => [
                'id' => $this->id,
                'name' => $this->name,
                'slug' => $this->slug,
                'game_title' => $this->game_title,
                'format' => $this->format,
                'prize_pool' => (float) $this->prize_pool,
                'status' => $this->status,
                'champion' => $champion ? ['id' => $champion->id, 'name' => $champion->name, 'tag' => $champion->tag] : null,
            ],
            'rounds' => $matches
                ->groupBy('round_number')
                ->map(fn ($roundMatches, $roundNumber) => [
                    'round_number' => (int) $roundNumber,
                    'name' => MatchUp::ROUND_NAMES[$roundNumber] ?? "Round {$roundNumber}",
                    'matches' => MatchUpResource::collection($roundMatches)->resolve($request),
                ])
                ->values()
                ->all(),
        ];
    }
}
