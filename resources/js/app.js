import Alpine from 'alpinejs';

Alpine.data('tournamentHub', (initial) => ({
    bracket: initial.bracket,
    pendingMatchId: null,
    toast: null,
    toastTimer: null,

    get tournament() {
        return this.bracket.tournament;
    },

    get liveMatches() {
        return this.bracket.rounds.flatMap((round) => round.matches).filter((match) => match.status === 'LIVE');
    },

    canScore(match) {
        return match.status === 'LIVE' && match.team_a && match.team_b && this.pendingMatchId === null;
    },

    isWinner(match, team) {
        return team && match.winner_id === team.id;
    },

    isLoser(match, team) {
        return team && match.winner_id !== null && match.winner_id !== team.id;
    },

    async recordWin(match, team) {
        if (!this.canScore(match)) {
            return;
        }

        this.pendingMatchId = match.id;

        try {
            const response = await fetch(`/api/v1/matches/${match.id}/score`, {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
                body: JSON.stringify({ winning_team_id: team.id }),
            });
            const payload = await response.json();

            if (!response.ok) {
                throw new Error(payload.message ?? 'Unable to record result.');
            }

            this.bracket = payload.bracket;
            const updated = payload.match;

            if (updated.status === 'COMPLETED') {
                const champion = this.bracket.tournament.champion;
                this.notify(
                    champion
                        ? `${champion.name} are the champions!`
                        : `${team.tag} clinch the ${updated.round_name} series and advance!`,
                    'success',
                );
            } else {
                this.notify(`${team.tag} take the game (${updated.team_a.score}-${updated.team_b.score})`, 'info');
            }
        } catch (error) {
            this.notify(error.message, 'error');
        } finally {
            this.pendingMatchId = null;
        }
    },

    notify(message, type) {
        clearTimeout(this.toastTimer);
        this.toast = { message, type };
        this.toastTimer = setTimeout(() => (this.toast = null), 3500);
    },
}));

window.Alpine = Alpine;
Alpine.start();
