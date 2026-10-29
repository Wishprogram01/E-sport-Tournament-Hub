<?php

namespace Tests\Feature;

use App\Models\MatchUp;
use App\Models\Team;
use App\Models\Tournament;
use Database\Seeders\TournamentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TournamentApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(TournamentSeeder::class);
        $this->withoutVite();
    }

    public function test_hub_page_renders(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('NexusArena MPL Invitational 2026')
            ->assertSee('MVP Leaderboard')
            ->assertSee('Selangor Red Giants');
    }

    public function test_bracket_endpoint_groups_matches_by_round(): void
    {
        $tournament = Tournament::first();

        $this->getJson("/api/v1/tournaments/{$tournament->id}/bracket")
            ->assertOk()
            ->assertJsonCount(3, 'rounds')
            ->assertJsonPath('rounds.0.name', 'Quarter-Finals')
            ->assertJsonCount(4, 'rounds.0.matches')
            ->assertJsonCount(2, 'rounds.1.matches')
            ->assertJsonCount(1, 'rounds.2.matches')
            ->assertJsonPath('rounds.1.matches.0.team_a.tag', 'SRG')
            ->assertJsonPath('rounds.1.matches.0.status', 'LIVE');
    }

    public function test_score_endpoint_records_win_and_returns_bracket(): void
    {
        $match = MatchUp::where(['round_number' => 1, 'bracket_position' => 3])->first();
        $hb = Team::where('tag', 'HB')->first();

        $this->patchJson("/api/v1/matches/{$match->id}/score", ['winning_team_id' => $hb->id])
            ->assertOk()
            ->assertJsonPath('match.status', 'COMPLETED')
            ->assertJsonPath('match.winner_id', $hb->id)
            ->assertJsonPath('bracket.rounds.1.matches.1.team_a.tag', 'HB');
    }

    public function test_score_endpoint_rejects_invalid_requests(): void
    {
        $match = MatchUp::where(['round_number' => 1, 'bracket_position' => 3])->first();

        $this->patchJson("/api/v1/matches/{$match->id}/score", [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('winning_team_id');

        $this->patchJson("/api/v1/matches/{$match->id}/score", ['winning_team_id' => Team::where('tag', 'SRG')->value('id')])
            ->assertStatus(422)
            ->assertJsonPath('message', fn ($message) => str_contains($message, 'not playing'));
    }

    public function test_roster_and_mvp_endpoints(): void
    {
        $this->getJson('/api/v1/teams/TDK/roster')
            ->assertOk()
            ->assertJsonPath('roster_valid', true)
            ->assertJsonCount(5, 'players');

        $response = $this->getJson('/api/v1/leaderboards/mvp?limit=5')
            ->assertOk()
            ->assertJsonCount(5, 'data');

        $kdas = array_column($response->json('data'), 'kda');
        $sorted = $kdas;
        rsort($sorted);
        $this->assertSame($sorted, $kdas);
    }
}
