<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Player;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeaderboardController extends Controller
{
    public function mvp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tournament_id' => ['nullable', 'integer', 'exists:tournaments,id'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $players = Player::query()
            ->with('team:id,name,tag')
            ->when($validated['tournament_id'] ?? null, fn ($query, $tournamentId) => $query->whereHas(
                'team.tournaments',
                fn ($tournaments) => $tournaments->whereKey($tournamentId)
            ))
            ->orderByKda()
            ->limit($validated['limit'] ?? 10)
            ->get();

        return response()->json([
            'data' => $players->values()->map(fn (Player $player, int $index) => [
                'rank' => $index + 1,
                'ign' => $player->ign,
                'role' => $player->role,
                'team' => $player->team->only(['id', 'name', 'tag']),
                'kills' => $player->kills,
                'deaths' => $player->deaths,
                'assists' => $player->assists,
                'kda' => $player->kda,
            ]),
        ]);
    }
}
