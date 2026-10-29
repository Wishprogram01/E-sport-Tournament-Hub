<?php

namespace App\Services;

use App\Exceptions\BracketException;
use App\Models\MatchUp;
use App\Models\Tournament;
use Illuminate\Support\Facades\DB;

class BracketProgressionService
{
    public function recordGameWin(MatchUp $match, int $winningTeamId): MatchUp
    {
        return DB::transaction(function () use ($match, $winningTeamId) {
            $locked = MatchUp::query()->whereKey($match->getKey())->lockForUpdate()->firstOrFail();

            $this->guardAgainstInvalidResult($locked, $winningTeamId);

            if ($winningTeamId === $locked->team_a_id) {
                $locked->team_a_score++;
            } else {
                $locked->team_b_score++;
            }

            $winnerScore = max($locked->team_a_score, $locked->team_b_score);

            if ($winnerScore >= $locked->winsRequired()) {
                $locked->winner_id = $winningTeamId;
                $locked->status = MatchUp::STATUS_COMPLETED;
                $locked->save();

                $this->advanceWinner($locked, $winningTeamId);
            } else {
                $locked->save();
            }

            return $locked->fresh(['teamA', 'teamB', 'winner']);
        });
    }

    private function guardAgainstInvalidResult(MatchUp $match, int $winningTeamId): void
    {
        if ($match->status !== MatchUp::STATUS_LIVE) {
            throw new BracketException("Match #{$match->id} is {$match->status}; only LIVE matches accept results.");
        }

        if ($match->team_a_id === null || $match->team_b_id === null) {
            throw new BracketException("Match #{$match->id} is still waiting for both teams.");
        }

        if (! $match->involvesTeam($winningTeamId)) {
            throw new BracketException("Team #{$winningTeamId} is not playing in match #{$match->id}.");
        }
    }

    private function advanceWinner(MatchUp $match, int $winningTeamId): void
    {
        if ($match->next_match_id === null) {
            Tournament::query()
                ->whereKey($match->tournament_id)
                ->update(['status' => Tournament::STATUS_COMPLETED]);

            return;
        }

        $next = MatchUp::query()->whereKey($match->next_match_id)->lockForUpdate()->firstOrFail();

        $slot = $match->bracket_position % 2 === 1 ? 'team_a_id' : 'team_b_id';

        if ($next->{$slot} !== null && $next->{$slot} !== $winningTeamId) {
            throw new BracketException("Slot {$slot} of match #{$next->id} is already taken.");
        }

        $next->{$slot} = $winningTeamId;

        if ($next->team_a_id !== null && $next->team_b_id !== null && $next->status === MatchUp::STATUS_PENDING) {
            $next->status = MatchUp::STATUS_LIVE;
        }

        $next->save();
    }
}
