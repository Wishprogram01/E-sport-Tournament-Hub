<?php

use App\Http\Controllers\TournamentDashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', [TournamentDashboardController::class, 'index'])->name('hub');
Route::get('/tournaments/{tournament:slug}', [TournamentDashboardController::class, 'show'])->name('tournaments.show');
