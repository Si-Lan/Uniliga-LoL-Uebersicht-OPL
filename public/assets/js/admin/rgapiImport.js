// Turnier-Auswahl Dropdown
import {finishButtonUpdating, unsetButtonUpdating, setButtonUpdating, setButtonLoadingBarWidth} from "../utils/updatingButton";

$(document).on("change", "select.tournament-selector", function () {
	$("div.writing-wrapper").addClass("hidden");
	$(`div.writing-wrapper[data-id=${this.value}]`).removeClass("hidden");
});

// Beim Start nach laufenden Jobs suchen und Buttons aktualisieren
$(async function () {
    // Rufe alle Jobs ab
    const runningJobs = await fetch('/api/jobs/admin/running')
        .then(res => {
            if (res.ok) {
                return res.json()
            } else {
                console.error("Fehler beim Laden der Daten");
            }
        })
        .catch(e => console.error(e));
    if (!runningJobs || runningJobs.error) {
        return;
    }

    for (const job of runningJobs) {
        // wenn kein Kontext gesetzt ist, prüfe nach generellen Updates
        if (job['contextType'] === null) {
            const action = job['action'];
            if (['update_puuids', 'update_player_ranks', 'update_team_ranks'].indexOf(action) === -1) {
                continue;
            }
            const jqButton = $(`div.general-administration button[data-action=${action}]`);
            if (jqButton.length === 0) {
                continue;
            }
            setButtonUpdating(jqButton);
            checkJobStatusRepeatedly(job['id'], 1000, jqButton)
                .then(() => {
                    finishButtonUpdating(jqButton);
                })
                .catch(e => console.error(e));
            continue;
        }

        // Für RGAPI-Updates sind jetzt nur noch Jobs mit korrektem TournamentContext relevant
        if (job['contextType'] !== 'tournament') {
            continue;
        }
        if (!job['context'] || !job['context']['id']) {
            continue;
        }

        // Prüfe nach passenden Turnier-Update Buttons
        const tournamentId = job['context']['id'];
        const action = job['action'];
        const jqButton = $(`button.write[data-id=${tournamentId}][data-action=${action}]`);
        if (jqButton.length === 0) {
            continue;
        }
        setButtonUpdating(jqButton);
        checkJobStatusRepeatedly(job['id'], 1000, jqButton)
            .then(() => {
                finishButtonUpdating(jqButton);
            })
            .catch(e => console.error(e));
    }
})

// PUUIDs im Turnier schreiben
$(document).on("click", "button.write.puuids", async function () {
	const tournamentId = this.dataset.id;
	const jqButton = $(this);
	const jobResponse = await startJob(`/admin/api/rgapi/tournaments/${tournamentId}/players/puuids?withoutPuuid=true`)
	if (jobResponse.error) {
		console.error(jobResponse.error);
		return;
	}
	const jobId = parseInt(jobResponse["job_id"]);
	setButtonUpdating(jqButton);
	await checkJobStatusRepeatedly(jobId, 1000, jqButton);
    finishButtonUpdating(jqButton);
})
$(document).on("click", "button.write.puuids-all", async function () {
	const tournamentId = this.dataset.id;
	const jqButton = $(this);
	const jobResponse = await startJob(`/admin/api/rgapi/tournaments/${tournamentId}/players/puuids`)
	if (jobResponse.error) {
		console.error(jobResponse.error);
		return;
	}
	const jobId = parseInt(jobResponse["job_id"]);
	setButtonUpdating(jqButton);
	await checkJobStatusRepeatedly(jobId, 1000, jqButton);
    finishButtonUpdating(jqButton);
})

// RiotIDs im Turnier schreiben
$(document).on("click", "button.write.riotids-puuids", async function () {
    const tournamentId = this.dataset.id;
    const jqButton = $(this);
    const jobResponse = await startJob(`/admin/api/rgapi/tournaments/${tournamentId}/players/riotids`)
    if (jobResponse.error) {
        console.error(jobResponse.error);
        return;
    }
    const jobId = parseInt(jobResponse["job_id"]);
    setButtonUpdating(jqButton);
    await checkJobStatusRepeatedly(jobId, 1000, jqButton);
    finishButtonUpdating(jqButton);
})

// Player-Ranks im Turnier schreiben
$(document).on("click", "button.write.get-ranks", async function () {
	const tournamentId = this.dataset.id;
	const jqButton = $(this);
    setButtonUpdating(jqButton);
    const jobResponse = await startJob(`/admin/api/rgapi/tournaments/${tournamentId}/players/ranks`)
    if (jobResponse.error) {
        console.error(jobResponse.error);
        return;
    }
    const jobId = parseInt(jobResponse["job_id"]);
    await checkJobStatusRepeatedly(jobId, 1000, jqButton);
    finishButtonUpdating(jqButton);
})

// Team-Ranks im Turnier schreiben
$(document).on("click", "button.write.calc-team-rank", async function () {
	const tournamentId = this.dataset.id;
	const jqButton = $(this);
    setButtonUpdating(jqButton);
    const jobResponse = await startJob(`/admin/api/rgapi/tournaments/${tournamentId}/teams/ranks`)
    if (jobResponse.error) {
        console.error(jobResponse.error);
        return;
    }
    const jobId = parseInt(jobResponse["job_id"]);
    await checkJobStatusRepeatedly(jobId, 1000, jqButton);
    finishButtonUpdating(jqButton);
})

// Spieldaten im Turnier schreiben
$(document).on("click", "button.write.gamedata", async function () {
	const tournamentId = this.dataset.id;
	const jqButton = $(this);
    setButtonUpdating(jqButton);
    const jobResponse = await startJob(`/admin/api/rgapi/tournaments/${tournamentId}/games/data`)
    if (jobResponse.error) {
        console.error(jobResponse.error);
        return;
    }
    const jobId = parseInt(jobResponse["job_id"]);
    await checkJobStatusRepeatedly(jobId, 1000, jqButton);
    finishButtonUpdating(jqButton);
})

// Stats im Turnier schreiben
$(document).on("click", "button.write.playerstats", async function () {
    const tournamentId = this.dataset.id;
    const jqButton = $(this);
    setButtonUpdating(jqButton);
    const jobResponse = await startJob(`/admin/api/rgapi/tournaments/${tournamentId}/players/stats`)
    if (jobResponse.error) {
        console.error(jobResponse.error);
        return;
    }
    const jobId = parseInt(jobResponse["job_id"]);
    await checkJobStatusRepeatedly(jobId, 1000, jqButton);
    finishButtonUpdating(jqButton);
})
$(document).on("click", "button.write.teamstats", async function () {
    const tournamentId = this.dataset.id;
    const jqButton = $(this);
    setButtonUpdating(jqButton);
    const jobResponse = await startJob(`/admin/api/rgapi/tournaments/${tournamentId}/teams/stats`)
    if (jobResponse.error) {
        console.error(jobResponse.error);
        return;
    }
    const jobId = parseInt(jobResponse["job_id"]);
    await checkJobStatusRepeatedly(jobId, 1000, jqButton);
    finishButtonUpdating(jqButton);
})

// PUUIDs aller Spieler schreiben
$(document).on("click", ".general-administration button.get_all_player_puuids", async function () {
    const jqButton = $(this);
    setButtonUpdating(jqButton);
    const jobResponse = await startJob(`/admin/api/rgapi/players/all/puuid?withoutPuuid=true`)
    if (jobResponse.error) {
        console.error(jobResponse.error);
        return;
    }
    const jobId = parseInt(jobResponse["job_id"]);
    await checkJobStatusRepeatedly(jobId, 1000, jqButton);
    finishButtonUpdating(jqButton);
})
// Ränge aller Spieler schreiben
$(document).on("click", ".general-administration button.update_all_player_ranks", async function () {
    const jqButton = $(this);
    setButtonUpdating(jqButton);
    const jobResponse = await startJob(`/admin/api/rgapi/players/all/rank`)
    if (jobResponse.error) {
        console.error(jobResponse.error);
        return;
    }
    const jobId = parseInt(jobResponse["job_id"]);
    await checkJobStatusRepeatedly(jobId, 1000, jqButton);
    finishButtonUpdating(jqButton);
})
// Ränge aller Teams schreiben
$(document).on("click", ".general-administration button.update_all_team_ranks", async function () {
    const jqButton = $(this);
    setButtonUpdating(jqButton);
    const jobResponse = await startJob(`/admin/api/rgapi/teams/all/rank`)
    if (jobResponse.error) {
        console.error(jobResponse.error);
        return;
    }
    const jobId = parseInt(jobResponse["job_id"]);
    await checkJobStatusRepeatedly(jobId, 1000, jqButton);
    finishButtonUpdating(jqButton);
})


/* -------------- */

async function startJob(endpoint) {
	return await fetch(endpoint, {method: 'POST'})
		.then(res => {
			if (res.ok) {
				return res.json()
			} else {
				return {"error": "Fehler beim Starten des Jobs"};
			}
		})
		.catch(e => console.error(e));
}
async function checkJobStatusRepeatedly(jobId, interval, button = null) {
	while (true) {
		const job = await checkJobStatus(jobId);
		if (job.error) {
			if (button !== null) unsetButtonUpdating(button);
			console.error(job.error);
			return;
		}
		if (job.status !== "running" && job.status !== "queued") {
			if (button !== null) unsetButtonUpdating(button);
			break;
		}
		if (button !== null) setButtonLoadingBarWidth(button, Math.round(job.progress));
		await new Promise(r => setTimeout(r, interval));
	}
}
async function checkJobStatus(jobId) {
	return await fetch(`/api/jobs/${jobId}`)
		.then(res => {
			if (res.ok) {
				return res.json()
			} else {
				return {"error": "Fehler beim Laden der Daten"};
			}
		})
		.catch(e => {
			console.error(e);
			return {"error": "Fehler beim Laden der Daten"};
		});
}