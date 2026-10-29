<?php

namespace App\Http\Controllers;

use App\Http\Resources\BracketResource;
use App\Models\Player;
use App\Models\Tournament;
use App\Services\RosterValidationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class TournamentDashboardController extends Controller
{
    public function index(Request $request, RosterValidationService $rosters): View
    {
        $tournament = Tournament::query()
            ->orderByRaw("CASE status WHEN 'LIVE' THEN 0 WHEN 'REGISTRATION' THEN 1 ELSE 2 END")
            ->latest('id')
            ->firstOrFail();

        return $this->show($request, $tournament, $rosters);
    }

    public function show(Request $request, Tournament $tournament, RosterValidationService $rosters): View
    {
        $teams = $tournament->teams()
            ->with(['players' => fn ($query) => $query->orderByDesc('is_starter')->orderBy('id')])
            ->orderBy('team_tournament.seed')
            ->get()
            ->each(fn ($team) => $team->setAttribute('roster_valid', $rosters->isEligible($team)));

        $mvps = Player::query()
            ->with('team:id,name,tag')
            ->whereIn('team_id', $teams->pluck('id'))
            ->orderByKda()
            ->limit(5)
            ->get();

        return view('tournament.hub', [
            'tournament' => $tournament,
            'bracket' => (new BracketResource($tournament))->resolve($request),
            'teams' => $teams,
            'mvps' => $mvps,
        ]);
    }
}
