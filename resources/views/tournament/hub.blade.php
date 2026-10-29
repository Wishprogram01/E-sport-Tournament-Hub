@php
    $roleStyles = [
        'Jungler' => 'border-emerald-400/50 bg-emerald-400/10 text-emerald-300',
        'Roamer' => 'border-sky-400/50 bg-sky-400/10 text-sky-300',
        'Mid Laner' => 'border-violet-400/50 bg-violet-400/10 text-violet-300',
        'Gold Laner' => 'border-amber-400/50 bg-amber-400/10 text-amber-300',
        'EXP Laner' => 'border-rose-400/50 bg-rose-400/10 text-rose-300',
        'Sixth Man' => 'border-slate-400/50 bg-slate-400/10 text-slate-300',
    ];
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $tournament->name }} · NexusArena</title>
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full font-sans text-slate-200 antialiased"
      x-data="tournamentHub(@js(['bracket' => $bracket]))">

    {{-- Toast --}}
    <div x-cloak x-show="toast" x-transition.opacity
         class="fixed right-4 top-4 z-50 max-w-sm border px-4 py-3 text-sm font-semibold tracking-wide backdrop-blur clip-corner"
         :class="{
            'border-neon-lime/60 bg-neon-lime/10 text-neon-lime': toast?.type === 'success',
            'border-neon-cyan/60 bg-neon-cyan/10 text-neon-cyan': toast?.type === 'info',
            'border-rose-500/60 bg-rose-500/10 text-rose-300': toast?.type === 'error',
         }"
         x-text="toast?.message"></div>

    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">

        {{-- Header --}}
        <header class="relative overflow-hidden border border-neon-pink/30 bg-panel/80 p-6 clip-corner sm:p-8">
            <div class="pointer-events-none absolute -right-20 -top-24 h-64 w-64 rounded-full bg-neon-pink/20 blur-3xl"></div>
            <div class="pointer-events-none absolute -bottom-24 left-1/3 h-64 w-64 rounded-full bg-neon-cyan/10 blur-3xl"></div>

            <div class="relative flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="font-display text-xs uppercase tracking-[0.4em] text-neon-cyan/80">NexusArena · Esports Tournament Hub</p>
                    <h1 class="mt-3 font-display text-3xl font-black uppercase leading-tight text-white sm:text-4xl lg:text-5xl">
                        <span x-text="tournament.name">{{ $tournament->name }}</span>
                    </h1>
                    <p class="mt-3 text-lg text-slate-400">
                        <span x-text="tournament.game_title">{{ $tournament->game_title }}</span>
                        <span class="mx-2 text-neon-pink">//</span>
                        <span x-text="tournament.format">{{ $tournament->format }}</span>
                        <span class="mx-2 text-neon-pink">//</span>
                        {{ $teams->count() }} Teams
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <div class="border border-neon-amber/50 bg-neon-amber/10 px-4 py-2 clip-corner">
                        <p class="text-xs uppercase tracking-widest text-neon-amber/80">Prize Pool</p>
                        <p class="font-display text-2xl font-bold text-neon-amber">${{ number_format((float) $tournament->prize_pool) }}</p>
                    </div>

                    <div x-show="tournament.status === 'LIVE'"
                         class="flex items-center gap-2 border border-rose-500/60 bg-rose-500/10 px-4 py-3 font-display text-sm font-bold uppercase tracking-widest text-rose-400 shadow-[0_0_18px_rgba(244,63,94,0.45)]">
                        <span class="relative flex h-3 w-3">
                            <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-rose-500 opacity-75"></span>
                            <span class="relative inline-flex h-3 w-3 rounded-full bg-rose-500"></span>
                        </span>
                        Live
                    </div>
                    <div x-cloak x-show="tournament.status === 'COMPLETED'"
                         class="border border-neon-lime/60 bg-neon-lime/10 px-4 py-3 font-display text-sm font-bold uppercase tracking-widest text-neon-lime">
                        Completed
                    </div>
                    <div x-cloak x-show="tournament.status === 'REGISTRATION'"
                         class="border border-neon-cyan/60 bg-neon-cyan/10 px-4 py-3 font-display text-sm font-bold uppercase tracking-widest text-neon-cyan">
                        Registration Open
                    </div>
                </div>
            </div>
        </header>

        {{-- Champion banner --}}
        <div x-cloak x-show="tournament.champion" x-transition
             class="mt-6 border border-neon-amber/60 bg-gradient-to-r from-neon-amber/20 via-neon-pink/10 to-transparent p-5 text-center clip-corner">
            <p class="font-display text-xs uppercase tracking-[0.4em] text-neon-amber">Champions</p>
            <p class="mt-1 font-display text-3xl font-black uppercase text-white" x-text="tournament.champion?.name"></p>
        </div>

        {{-- Bracket --}}
        <section class="mt-10">
            <div class="mb-4 flex flex-wrap items-end justify-between gap-2">
                <h2 class="font-display text-xl font-bold uppercase tracking-widest text-white">
                    <span class="neon-text-cyan">▍</span> Playoff Bracket
                </h2>
                <p class="text-sm text-slate-400">
                    <span class="font-semibold text-neon-cyan" x-text="liveMatches.length"></span> live series
                    · Admin quick-action: hit <span class="font-semibold text-neon-pink">+1 Win</span> to log a game
                </p>
            </div>

            <div class="overflow-x-auto pb-2">
                <div class="grid min-w-[880px] grid-cols-3 gap-10">
                    <template x-for="round in bracket.rounds" :key="round.round_number">
                        <div class="flex flex-col">
                            <h3 class="mb-4 border-b border-white/10 pb-2 text-center font-display text-sm font-bold uppercase tracking-[0.3em] text-slate-400"
                                x-text="round.name"></h3>

                            <div class="flex flex-1 flex-col justify-around gap-6">
                                <template x-for="match in round.matches" :key="match.id">
                                    <div class="relative border bg-panel/90 p-3 transition clip-corner"
                                         :class="match.status === 'LIVE'
                                            ? 'neon-border-cyan'
                                            : (match.status === 'COMPLETED' ? 'border-white/15' : 'border-white/5 opacity-70')">
                                        <div class="mb-2 flex items-center justify-between text-[11px] font-semibold uppercase tracking-widest">
                                            <span class="text-slate-500">
                                                Match <span x-text="match.bracket_position"></span> · Bo<span x-text="match.best_of"></span>
                                            </span>
                                            <span :class="{
                                                    'text-rose-400': match.status === 'LIVE',
                                                    'text-neon-lime': match.status === 'COMPLETED',
                                                    'text-slate-500': match.status === 'PENDING',
                                                  }">
                                                <span x-show="match.status === 'LIVE'" class="animate-pulse">●</span>
                                                <span x-text="match.status"></span>
                                            </span>
                                        </div>

                                        <template x-for="side in ['team_a', 'team_b']" :key="side">
                                            <div class="flex items-center gap-2 py-2"
                                                 :class="{ 'opacity-40': isLoser(match, match[side]), 'border-t border-white/5': side === 'team_b' }">
                                                <template x-if="match[side]">
                                                    <div class="flex flex-1 items-center gap-3">
                                                        <span class="flex h-9 w-12 items-center justify-center border font-display text-xs font-bold"
                                                              :class="isWinner(match, match[side])
                                                                ? 'border-neon-lime/60 bg-neon-lime/10 text-neon-lime'
                                                                : 'border-neon-pink/40 bg-neon-pink/10 text-neon-pink'"
                                                              x-text="match[side].tag"></span>
                                                        <span class="flex-1 truncate font-semibold text-white" x-text="match[side].name"></span>
                                                        <button type="button"
                                                                x-show="match.status === 'LIVE'"
                                                                @click="recordWin(match, match[side])"
                                                                :disabled="!canScore(match)"
                                                                class="border border-neon-pink/60 bg-neon-pink/10 px-2 py-1 text-[11px] font-bold uppercase tracking-wider text-neon-pink transition hover:bg-neon-pink hover:text-void disabled:cursor-wait disabled:opacity-40">
                                                            +1 Win
                                                        </button>
                                                        <span class="w-6 text-right font-display text-xl font-bold"
                                                              :class="isWinner(match, match[side]) ? 'text-neon-lime' : 'text-slate-300'"
                                                              x-text="match[side].score"></span>
                                                    </div>
                                                </template>
                                                <template x-if="!match[side]">
                                                    <div class="flex flex-1 items-center gap-3 text-slate-600">
                                                        <span class="flex h-9 w-12 items-center justify-center border border-dashed border-white/10 font-display text-xs">TBD</span>
                                                        <span class="italic">Awaiting winner</span>
                                                    </div>
                                                </template>
                                            </div>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </section>

        <div class="mt-12 grid gap-10 lg:grid-cols-5">
            {{-- MVP leaderboard --}}
            <section class="lg:col-span-2">
                <h2 class="mb-4 font-display text-xl font-bold uppercase tracking-widest text-white">
                    <span class="neon-text-pink">▍</span> MVP Leaderboard
                </h2>
                <div class="overflow-hidden border border-white/10 bg-panel/90 clip-corner">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-panel-light text-[11px] uppercase tracking-widest text-slate-400">
                            <tr>
                                <th class="px-3 py-3">#</th>
                                <th class="px-3 py-3">Player</th>
                                <th class="px-3 py-3 text-center">K / D / A</th>
                                <th class="px-3 py-3 text-right">KDA</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            @foreach ($mvps as $player)
                                <tr class="{{ $loop->first ? 'bg-neon-amber/5' : '' }}">
                                    <td class="px-3 py-3 font-display font-bold {{ $loop->first ? 'text-neon-amber' : 'text-slate-500' }}">{{ $loop->iteration }}</td>
                                    <td class="px-3 py-3">
                                        <div class="font-semibold text-white">{{ $player->ign }} <span class="text-xs text-neon-pink">{{ $player->team->tag }}</span></div>
                                        <span class="mt-1 inline-block border px-1.5 text-[10px] font-semibold uppercase tracking-wider {{ $roleStyles[$player->role] }}">{{ $player->role }}</span>
                                    </td>
                                    <td class="px-3 py-3 text-center tabular-nums text-slate-300">{{ $player->kills }} / {{ $player->deaths }} / {{ $player->assists }}</td>
                                    <td class="px-3 py-3 text-right font-display font-bold text-neon-cyan">{{ number_format($player->kda, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <p class="mt-2 text-xs text-slate-500">KDA = (Kills + Assists) / max(1, Deaths)</p>
            </section>

            {{-- Rosters --}}
            <section class="lg:col-span-3">
                <h2 class="mb-4 font-display text-xl font-bold uppercase tracking-widest text-white">
                    <span class="neon-text-cyan">▍</span> Team Rosters
                </h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    @foreach ($teams as $team)
                        <article class="border border-white/10 bg-panel/90 p-4 clip-corner" x-data="{ open: {{ $loop->index < 2 ? 'true' : 'false' }} }">
                            <button type="button" class="flex w-full items-center gap-3 text-left" @click="open = !open">
                                @if ($team->logo_url)
                                    <img src="{{ $team->logo_url }}" alt="{{ $team->name }} logo" class="h-10 w-10 object-contain">
                                @else
                                    <span class="flex h-10 w-12 items-center justify-center border border-neon-cyan/40 bg-neon-cyan/10 font-display text-xs font-bold text-neon-cyan">{{ $team->tag }}</span>
                                @endif
                                <span class="flex-1">
                                    <span class="block font-semibold text-white">{{ $team->name }}</span>
                                    <span class="text-xs uppercase tracking-widest text-slate-500">Seed #{{ $team->pivot->seed }} · {{ $team->region }}</span>
                                </span>
                                @if ($team->roster_valid)
                                    <span class="border border-neon-lime/40 px-1.5 text-[10px] font-semibold uppercase tracking-wider text-neon-lime" title="5 starters covering all core roles">Roster ✓</span>
                                @else
                                    <span class="border border-rose-500/40 px-1.5 text-[10px] font-semibold uppercase tracking-wider text-rose-400">Invalid</span>
                                @endif
                                <span class="text-slate-500 transition" :class="open && 'rotate-90'">›</span>
                            </button>
                            <ul x-show="open" x-transition class="mt-3 space-y-1.5 border-t border-white/5 pt-3">
                                @foreach ($team->players as $player)
                                    <li class="flex items-center justify-between gap-2 text-sm">
                                        <span class="flex items-center gap-2">
                                            <span class="inline-block w-24 border px-1.5 text-center text-[10px] font-semibold uppercase tracking-wider {{ $roleStyles[$player->role] }}">{{ $player->role }}</span>
                                            <span class="font-semibold text-white">{{ $player->ign }}</span>
                                        </span>
                                        <span class="tabular-nums text-xs text-slate-400">{{ $player->kills }}/{{ $player->deaths }}/{{ $player->assists }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </article>
                    @endforeach
                </div>
            </section>
        </div>

        <footer class="mt-16 border-t border-white/10 pt-6 text-center text-xs uppercase tracking-widest text-slate-600">
            NexusArena · Built with Laravel, Tailwind CSS &amp; Alpine.js ·
            <a href="{{ url('/api/v1/tournaments/'.$tournament->id.'/bracket') }}" class="text-neon-cyan/70 hover:text-neon-cyan">Bracket JSON</a>
        </footer>
    </div>
</body>
</html>
