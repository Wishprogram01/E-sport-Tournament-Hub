<?php

use App\Http\Controllers\Api\LeaderboardController;
use App\Http\Controllers\Api\MatchController;
use App\Http\Controllers\Api\TeamController;
use App\Http\Controllers\Api\TournamentRegistrationController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.')->group(function () {
    Route::get('/tournaments/{tournament}/bracket', [MatchController::class, 'bracket'])->name('tournaments.bracket');
    Route::post('/tournaments/{tournament}/register', [TournamentRegistrationController::class, 'store'])->name('tournaments.register');
    Route::patch('/matches/{match}/score', [MatchController::class, 'updateScore'])->name('matches.score');
    Route::get('/teams/{team:tag}/roster', [TeamController::class, 'roster'])->name('teams.roster');
    Route::get('/leaderboards/mvp', [LeaderboardController::class, 'mvp'])->name('leaderboards.mvp');
});
