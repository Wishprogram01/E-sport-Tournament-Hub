<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\Tournament;
use App\Services\RosterValidationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TournamentRegistrationController extends Controller
{
    public function store(Request $request, Tournament $tournament, RosterValidationService $rosters): JsonResponse
    {
        $validated = $request->validate([
            'team_id' => ['required', 'integer', 'exists:teams,id'],
        ]);

        $team = Team::findOrFail($validated['team_id']);
        $tournament = $rosters->register($tournament, $team);

        return response()->json([
            'message' => "{$team->name} is registered for {$tournament->name}.",
            'registered_teams' => $tournament->teams->map->only(['id', 'name', 'tag']),
        ], 201);
    }
}
