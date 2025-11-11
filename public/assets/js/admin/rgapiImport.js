// Turnier-Auswahl Dropdown
$(document).on("change", "select.tournament-selector", function () {
	$("div.writing-wrapper").addClass("hidden");
	$(`div.writing-wrapper[data-id=${this.value}]`).removeClass("hidden");
});

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
	unsetButtonUpdating(jqButton);
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
	unsetButtonUpdating(jqButton);
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
    unsetButtonUpdating(jqButton);
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
    unsetButtonUpdating(jqButton);
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
    unsetButtonUpdating(jqButton);
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
    unsetButtonUpdating(jqButton);
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
    unsetButtonUpdating(jqButton);
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
    unsetButtonUpdating(jqButton);
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
    unsetButtonUpdating(jqButton);
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
    unsetButtonUpdating(jqButton);
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
    unsetButtonUpdating(jqButton);
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
			if (button !== null) unsetButtonUpdating(button, true);
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


function setButtonUpdating(button) {
	setButtonLoadingBarWidth(button, 0);
	button.addClass("button-updating");
	button.prop("disabled",true);
}
async function unsetButtonUpdating(button, skipFinish = false) {
	if (!skipFinish) {
		setButtonLoadingBarWidth(button, 100);
		await new Promise(r => setTimeout(r, 100));
	}
	button.removeClass("button-updating");
	button.prop("disabled",false);
	setButtonLoadingBarWidth(button, 0);
}
function setButtonLoadingBarWidth(button, widthPercentage) {
	button.attr("style", `--loading-bar-width: ${widthPercentage}%`);
}