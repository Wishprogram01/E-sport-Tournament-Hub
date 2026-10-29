<?php

namespace Database\Seeders;

use App\Models\MatchUp;
use App\Models\Player;
use App\Models\Team;
use App\Models\Tournament;
use App\Services\BracketProgressionService;
use App\Services\RosterValidationService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TournamentSeeder extends Seeder
{
    /**
     * Rosters are listed in Player::CORE_ROLES order:
     * Jungler, Roamer, Mid Laner, Gold Laner, EXP Laner.
     */
    private const TEAMS = [
        ['name' => 'Selangor Red Giants', 'tag' => 'SRG', 'players' => ['Lyrox', 'Ghostwall', 'Nyxian', 'Kairo', 'Brutus']],
        ['name' => 'Todak', 'tag' => 'TDK', 'players' => ['Shadowfang', 'Ironveil', 'Zephyrus', 'Hawkeye', 'Titanos']],
        ['name' => 'HomeBois', 'tag' => 'HB', 'players' => ['Viperix', 'Bulwark', 'Arcanist', 'Quickdraw', 'Ragnar']],
        ['name' => 'Team Vamos', 'tag' => 'VMS', 'players' => ['Nightjar', 'Stonehold', 'Spellbyte', 'Deadeye', 'Warhorn']],
        ['name' => 'RSG MY', 'tag' => 'RSG', 'players' => ['Phantomz', 'Guardian', 'Mystique', 'Sharpshot', 'Colossus']],
        ['name' => 'King Empire', 'tag' => 'KGE', 'players' => ['Reaperx', 'Bastion', 'Enigma', 'Longbow', 'Juggernaut']],
        ['name' => 'Monster Vicious', 'tag' => 'MV', 'players' => ['Predator', 'Rampart', 'Sorcerix', 'Bullseye', 'Behemoth']],
        ['name' => 'RED Esports', 'tag' => 'RED', 'players' => ['Wraithz', 'Aegis', 'Voltage', 'Ricochet', 'Goliath']],
    ];

    /**
     * Baseline [kills, deaths, assists] per role; each player gets a deterministic variance on top.
     */
    private const ROLE_STATS = [
        'Jungler' => [68, 24, 72],
        'Roamer' => [14, 38, 141],
        'Mid Laner' => [52, 26, 98],
        'Gold Laner' => [74, 22, 61],
        'EXP Laner' => [41, 33, 79],
    ];

    /**
     * Quarter-final pairings by bracket position (tags).
     */
    private const QUARTER_FINALS = [
        1 => ['SRG', 'RED'],
        2 => ['TDK', 'MV'],
        3 => ['HB', 'KGE'],
        4 => ['VMS', 'RSG'],
    ];

    public function run(BracketProgressionService $bracket, RosterValidationService $rosters): void
    {
        mt_srand(2026);

        DB::transaction(function () use ($bracket, $rosters) {
            $tournament = Tournament::create([
                'name' => 'NexusArena MPL Invitational 2026',
                'slug' => 'nexusarena-mpl-invitational-2026',
                'game_title' => 'Mobile Legends: Bang Bang',
                'prize_pool' => 100000,
                'format' => 'Single Elimination',
                'status' => Tournament::STATUS_REGISTRATION,
            ]);

            $teams = collect(self::TEAMS)->mapWithKeys(function (array $data) {
                $team = Team::create([
                    'name' => $data['name'],
                    'tag' => $data['tag'],
                    'logo_url' => null,
                    'region' => 'Malaysia',
                ]);

                foreach ($data['players'] as $index => $ign) {
                    $role = Player::CORE_ROLES[$index];
                    [$kills, $deaths, $assists] = self::ROLE_STATS[$role];

                    $team->players()->create([
                        'ign' => $ign,
                        'role' => $role,
                        'is_starter' => true,
                        'kills' => $kills + mt_rand(-12, 18),
                        'deaths' => $deaths + mt_rand(-6, 10),
                        'assists' => $assists + mt_rand(-15, 20),
                    ]);
                }

                return [$team->tag => $team];
            });

            foreach (array_merge(...array_values(self::QUARTER_FINALS)) as $tag) {
                $rosters->register($tournament, $teams[$tag]);
            }

            $tournament->update(['status' => Tournament::STATUS_LIVE]);

            $grandFinal = $this->createMatch($tournament, round: 3, position: 1, bestOf: 5);

            $semiFinals = collect([1, 2])->mapWithKeys(fn (int $position) => [
                $position => $this->createMatch($tournament, round: 2, position: $position, bestOf: 5, next: $grandFinal),
            ]);

            $quarterFinals = collect(self::QUARTER_FINALS)->map(fn (array $tags, int $position) => $this->createMatch(
                $tournament,
                round: 1,
                position: $position,
                bestOf: 3,
                next: $semiFinals[(int) ceil($position / 2)],
                teamA: $teams[$tags[0]],
                teamB: $teams[$tags[1]],
            ));

            // QF1: SRG 2-1 RED, QF2: TDK 2-0 MV -> Semi-Final 1 (SRG vs TDK) goes LIVE.
            foreach (['SRG', 'RED', 'SRG'] as $tag) {
                $bracket->recordGameWin($quarterFinals[1]->refresh(), $teams[$tag]->id);
            }
            foreach (['TDK', 'TDK'] as $tag) {
                $bracket->recordGameWin($quarterFinals[2]->refresh(), $teams[$tag]->id);
            }

            // Mid-series states so the hub has live action to drive.
            $bracket->recordGameWin($semiFinals[1]->refresh(), $teams['SRG']->id);
            $bracket->recordGameWin($quarterFinals[3]->refresh(), $teams['HB']->id);
        });
    }

    private function createMatch(
        Tournament $tournament,
        int $round,
        int $position,
        int $bestOf,
        ?MatchUp $next = null,
        ?Team $teamA = null,
        ?Team $teamB = null,
    ): MatchUp {
        return MatchUp::create([
            'tournament_id' => $tournament->id,
            'team_a_id' => $teamA?->id,
            'team_b_id' => $teamB?->id,
            'round_number' => $round,
            'bracket_position' => $position,
            'best_of' => $bestOf,
            'status' => $teamA && $teamB ? MatchUp::STATUS_LIVE : MatchUp::STATUS_PENDING,
            'next_match_id' => $next?->id,
        ]);
    }
}
