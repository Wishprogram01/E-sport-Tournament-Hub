<?php

namespace Tests\Feature;

use App\Models\Player;
use App\Models\Team;
use App\Models\Tournament;
use App\Services\RosterValidationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class RosterValidationTest extends TestCase
{
    use RefreshDatabase;

    private function teamWithRoles(array $roles, array $starterFlags = []): Team
    {
        $team = Team::create(['name' => 'Test Squad', 'tag' => 'TST', 'region' => 'Malaysia']);

        foreach ($roles as $index => $role) {
            $team->players()->create([
                'ign' => "Player{$index}",
                'role' => $role,
                'is_starter' => $starterFlags[$index] ?? true,
            ]);
        }

        return $team;
    }

    private function openTournament(): Tournament
    {
        return Tournament::create(['name' => 'Open Cup', 'slug' => 'open-cup', 'prize_pool' => 5000]);
    }

    public function test_full_role_coverage_is_eligible(): void
    {
        $team = $this->teamWithRoles([...Player::CORE_ROLES, 'Sixth Man'], [5 => false]);

        $this->assertTrue(app(RosterValidationService::class)->isEligible($team));
    }

    public function test_missing_role_is_rejected(): void
    {
        $team = $this->teamWithRoles(['Jungler', 'Roamer', 'Mid Laner', 'Gold Laner', 'Gold Laner']);

        $errors = app(RosterValidationService::class)->errors($team);

        $this->assertCount(1, $errors);
        $this->assertStringContainsString('EXP Laner', $errors[0]);
    }

    public function test_bench_players_do_not_count_as_starters(): void
    {
        $team = $this->teamWithRoles(Player::CORE_ROLES, [4 => false]);

        $errors = app(RosterValidationService::class)->errors($team);

        $this->assertCount(2, $errors);
    }

    public function test_register_attaches_eligible_team_once(): void
    {
        $service = app(RosterValidationService::class);
        $tournament = $this->openTournament();
        $team = $this->teamWithRoles(Player::CORE_ROLES);

        $service->register($tournament, $team);
        $this->assertTrue($tournament->teams()->whereKey($team->id)->exists());

        $this->expectException(ValidationException::class);
        $service->register($tournament, $team);
    }

    public function test_register_endpoint_validates_roster(): void
    {
        $tournament = $this->openTournament();
        $team = $this->teamWithRoles(['Jungler', 'Roamer']);

        $this->postJson("/api/v1/tournaments/{$tournament->id}/register", ['team_id' => $team->id])
            ->assertStatus(422)
            ->assertJsonValidationErrors('roster');
    }

    public function test_register_endpoint_accepts_valid_roster(): void
    {
        $tournament = $this->openTournament();
        $team = $this->teamWithRoles(Player::CORE_ROLES);

        $this->postJson("/api/v1/tournaments/{$tournament->id}/register", ['team_id' => $team->id])
            ->assertCreated()
            ->assertJsonPath('registered_teams.0.tag', 'TST');
    }
}
