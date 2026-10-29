<?php

namespace Tests\Feature;

use App\Exceptions\BracketException;
use App\Models\MatchUp;
use App\Models\Player;
use App\Models\Team;
use App\Models\Tournament;
use App\Services\BracketProgressionService;
use Database\Seeders\TournamentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BracketProgressionTest extends TestCase
{
    use RefreshDatabase;

    private BracketProgressionService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(TournamentSeeder::class);
        $this->service = app(BracketProgressionService::class);
    }

    private function match(int $round, int $position): MatchUp
    {
        return MatchUp::where(['round_number' => $round, 'bracket_position' => $position])->firstOrFail();
    }

    private function team(string $tag): Team
    {
        return Team::where('tag', $tag)->firstOrFail();
    }

    public function test_seeder_builds_the_expected_bracket_state(): void
    {
        $this->assertSame(7, MatchUp::count());
        $this->assertSame(40, Player::count());

        $semiFinal = $this->match(2, 1);
        $this->assertSame(MatchUp::STATUS_LIVE, $semiFinal->status);
        $this->assertSame($this->team('SRG')->id, $semiFinal->team_a_id);
        $this->assertSame($this->team('TDK')->id, $semiFinal->team_b_id);
        $this->assertSame($this->match(3, 1)->id, $semiFinal->next_match_id);
        $this->assertSame(MatchUp::STATUS_COMPLETED, $this->match(1, 1)->status);
        $this->assertSame(MatchUp::STATUS_COMPLETED, $this->match(1, 2)->status);
    }

    public function test_game_win_increments_score_without_completing_series(): void
    {
        $quarterFinal = $this->match(1, 4);

        $updated = $this->service->recordGameWin($quarterFinal, $this->team('RSG')->id);

        $this->assertSame(0, $updated->team_a_score);
        $this->assertSame(1, $updated->team_b_score);
        $this->assertSame(MatchUp::STATUS_LIVE, $updated->status);
        $this->assertNull($updated->winner_id);
    }

    public function test_clinching_even_position_advances_winner_to_team_b_slot(): void
    {
        $rsg = $this->team('RSG');
        $quarterFinal = $this->match(1, 4);

        $this->service->recordGameWin($quarterFinal, $rsg->id);
        $updated = $this->service->recordGameWin($quarterFinal->refresh(), $rsg->id);

        $this->assertSame(MatchUp::STATUS_COMPLETED, $updated->status);
        $this->assertSame($rsg->id, $updated->winner_id);

        $semiFinal = $this->match(2, 2);
        $this->assertSame($rsg->id, $semiFinal->team_b_id);
        $this->assertNull($semiFinal->team_a_id);
        $this->assertSame(MatchUp::STATUS_PENDING, $semiFinal->status);
    }

    public function test_next_match_goes_live_once_both_feeders_finish(): void
    {
        $this->service->recordGameWin($this->match(1, 3), $this->team('HB')->id);
        $this->service->recordGameWin($this->match(1, 4), $this->team('VMS')->id);
        $this->service->recordGameWin($this->match(1, 4), $this->team('VMS')->id);

        $semiFinal = $this->match(2, 2);
        $this->assertSame($this->team('HB')->id, $semiFinal->team_a_id);
        $this->assertSame($this->team('VMS')->id, $semiFinal->team_b_id);
        $this->assertSame(MatchUp::STATUS_LIVE, $semiFinal->status);
    }

    public function test_winning_the_grand_final_completes_the_tournament(): void
    {
        $srg = $this->team('SRG');
        $hb = $this->team('HB');

        foreach (range(1, 2) as $_) {
            $this->service->recordGameWin($this->match(2, 1), $srg->id);
        }
        $this->service->recordGameWin($this->match(1, 3), $hb->id);
        foreach (range(1, 2) as $_) {
            $this->service->recordGameWin($this->match(1, 4), $this->team('VMS')->id);
        }
        foreach (range(1, 3) as $_) {
            $this->service->recordGameWin($this->match(2, 2), $hb->id);
        }

        $grandFinal = $this->match(3, 1);
        $this->assertSame(MatchUp::STATUS_LIVE, $grandFinal->status);

        foreach (range(1, 3) as $_) {
            $grandFinal = $this->service->recordGameWin($grandFinal, $hb->id);
        }

        $this->assertSame($hb->id, $grandFinal->winner_id);
        $this->assertSame(Tournament::STATUS_COMPLETED, Tournament::first()->status);
    }

    public function test_rejects_team_not_in_match(): void
    {
        $this->expectException(BracketException::class);

        $this->service->recordGameWin($this->match(1, 4), $this->team('SRG')->id);
    }

    public function test_rejects_results_for_completed_or_pending_matches(): void
    {
        try {
            $this->service->recordGameWin($this->match(1, 1), $this->team('SRG')->id);
            $this->fail('Completed match accepted a result.');
        } catch (BracketException) {
        }

        $this->expectException(BracketException::class);
        $this->service->recordGameWin($this->match(3, 1), $this->team('SRG')->id);
    }
}
