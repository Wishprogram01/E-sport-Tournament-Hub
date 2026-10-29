<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BracketResource;
use App\Http\Resources\MatchUpResource;
use App\Models\MatchUp;
use App\Models\Tournament;
use App\Services\BracketProgressionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MatchController extends Controller
{
    public function __construct(private readonly BracketProgressionService $bracket) {}

    public function bracket(Tournament $tournament): BracketResource
    {
        return new BracketResource($tournament);
    }

    public function updateScore(Request $request, MatchUp $match): JsonResponse
    {
        $validated = $request->validate([
            'winning_team_id' => ['required', 'integer', 'exists:teams,id'],
        ]);

        $updated = $this->bracket->recordGameWin($match, (int) $validated['winning_team_id']);

        return response()->json([
            'match' => (new MatchUpResource($updated))->resolve($request),
            'bracket' => (new BracketResource($updated->tournament))->resolve($request),
        ]);
    }
}
