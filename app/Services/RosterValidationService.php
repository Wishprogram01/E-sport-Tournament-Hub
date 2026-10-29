<?php

namespace App\Services;

use App\Models\Player;
use App\Models\Team;
use App\Models\Tournament;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RosterValidationService
{
    public const MIN_STARTERS = 5;

    /**
     * @return list<string>
     */
    public function errors(Team $team): array
    {
        $starters = $team->players()->where('is_starter', true)->get(['role']);
        $errors = [];

        if ($starters->count() < self::MIN_STARTERS) {
            $errors[] = sprintf(
                '%s has %d active starter(s); at least %d are required.',
                $team->name,
                $starters->count(),
                self::MIN_STARTERS
            );
        }

        $missingRoles = array_values(array_diff(Player::CORE_ROLES, $starters->pluck('role')->unique()->all()));

        if ($missingRoles !== []) {
            $errors[] = sprintf('%s is missing a starter for: %s.', $team->name, implode(', ', $missingRoles));
        }

        return $errors;
    }

    public function isEligible(Team $team): bool
    {
        return $this->errors($team) === [];
    }

    public function assertEligible(Team $team): void
    {
        $errors = $this->errors($team);

        if ($errors !== []) {
            throw ValidationException::withMessages(['roster' => $errors]);
        }
    }

    public function register(Tournament $tournament, Team $team): Tournament
    {
        if ($tournament->status !== Tournament::STATUS_REGISTRATION) {
            throw ValidationException::withMessages([
                'tournament' => ["Registration for {$tournament->name} is closed ({$tournament->status})."],
            ]);
        }

        $this->assertEligible($team);

        return DB::transaction(function () use ($tournament, $team) {
            $locked = Tournament::query()->whereKey($tournament->getKey())->lockForUpdate()->firstOrFail();

            if ($locked->teams()->whereKey($team->getKey())->exists()) {
                throw ValidationException::withMessages([
                    'team' => ["{$team->name} is already registered for {$locked->name}."],
                ]);
            }

            $locked->teams()->attach($team->getKey(), ['seed' => $locked->teams()->count() + 1]);

            return $locked->load('teams');
        });
    }
}
