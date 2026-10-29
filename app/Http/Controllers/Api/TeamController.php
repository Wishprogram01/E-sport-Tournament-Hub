<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Services\RosterValidationService;
use Illuminate\Http\JsonResponse;

class TeamController extends Controller
{
    public function roster(Team $team, RosterValidationService $rosters): JsonResponse
    {
        $players = $team->players()->orderByDesc('is_starter')->orderBy('id')->get();

        return response()->json([
            'team' => $team->only(['id', 'name', 'tag', 'logo_url', 'region']),
            'roster_valid' => $rosters->isEligible($team),
            'roster_errors' => $rosters->errors($team),
            'players' => $players->map(fn ($player) => [
                'id' => $player->id,
                'ign' => $player->ign,
                'role' => $player->role,
                'is_starter' => $player->is_starter,
                'kills' => $player->kills,
                'deaths' => $player->deaths,
                'assists' => $player->assists,
                'kda' => $player->kda,
            ]),
        ]);
    }
}
