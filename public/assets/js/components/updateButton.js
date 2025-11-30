$(async function () {
    const updateButtonsWrapper = $(".updatebuttonwrapper");
    for (const updateButtonWrapper of updateButtonsWrapper) {
        const button = $(updateButtonWrapper).find("button.user_update");
        const type = button.data("type");
        let runningUpdate = null;
        if (type === "group" && button.data("group")) {
            runningUpdate = await checkRunningGroupUpdate(button.data("group"));
        }
        if (type === "team" && button.data("team") && button.data("tournament")) {
            runningUpdate = await checkRunningTeamUpdate(button.data("team"), button.data("tournament"));
        }
        if (type === "match" && button.data("match")) {
            runningUpdate = await checkRunningMatchUpdate(button.data("match"));
        }
        if (runningUpdate !== null && !runningUpdate.error) {
            setUserButtonUpdating(button);
            checkUserUpdateStatusRepeatedly(runningUpdate.id, 1000, button)
                .then(() => {
                    unsetUserButtonUpdating(button);
                    if (type === "group") refreshTournamentStagePageContent(button.data("group"));
                    if (type === "team") refreshTeamInTournamentPageContent(button.data("team"), button.data("tournament"));
                    if (type === "match") {
                        const popupId = button.closest("dialog.match-popup").attr("id");
                        const teamId = button.data("team") ? button.data("team") : null;
                        refreshMatchPopupContent(popupId, button.data("match"), teamId);
                    }
                })
                .catch(e => console.error(e));
        }
    }
})

$(document).on("click", "button.user_update", async function () {
    const button = $(this);
    setUserButtonUpdating(button);
    const type = button.data("type");
    let jobResponse = null;

    switch (type) {
        case "group":
            jobResponse = await startUserUpdateGroup(button.data("group"));
            break;
        case "team":
            jobResponse = await startUserUpdateTeam(button.data("team"), button.data("tournament"));
            break;
        case "match":
            jobResponse = await startUserUpdateMatch(button.data("match"));
            break;
    }

    if (jobResponse !== null && jobResponse.error === "Zu viele Anfragen") {
        const data = await jobResponse.data;
        window.alert(`Das letzte Update wurde ${data.lastUpdate??'vor wenigen Sekunden'} durchgeführt. Versuche es ${data.nextTry??'in 10 Minuten'} noch einmal`);
        const updateTime = button.closest(".updatebuttonwrapper").find("span.update-time");
        updateTime.html(data.lastUpdate);
    }
    if (jobResponse === null || jobResponse.error) {
        console.error(jobResponse?.error ?? "Fehler beim Starten des Jobs");
        unsetUserButtonUpdating(button, true);
        return;
    }
    const jobId = parseInt(jobResponse["id"]);
    checkUserUpdateStatusRepeatedly(jobId, 1000, button)
        .then(() => {
            unsetUserButtonUpdating(button);
            if (type === "group") refreshTournamentStagePageContent(button.data("group"));
            if (type === "team") refreshTeamInTournamentPageContent(button.data("team"), button.data("tournament"));
            if (type === "match") {
                const popupId = button.closest("dialog.match-popup").attr("id");
                const teamId = button.data("team") ? button.data("team") : null;
                refreshMatchPopupContent(popupId, button.data("match"), teamId);
            }
        })
        .catch(e => console.error(e))
})


async function checkRunningGroupUpdate(groupId) {
    return await fetch(`/api/jobs/user/group/${groupId}/running`, {method: "GET"})
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
async function checkRunningTeamUpdate(teamId, tournamentId) {
    return await fetch(`/api/jobs/user/team/${teamId}/tournament/${tournamentId}/running`, {method: "GET"})
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
        })
}
async function checkRunningMatchUpdate(matchId) {
    return await fetch(`/api/jobs/user/matchup/${matchId}/running`, {method: "GET"})
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
        })
}

async function checkUserUpdateStatusRepeatedly(jobId, interval, button = null) {
    const updateTime = button !== null ? $(button).closest(".updatebuttonwrapper").find("span.update-time") : null;
    while (true) {
        const job = await checkUserUpdateStatus(jobId);
        if (job.error) {
            if (button !== null) unsetUserButtonUpdating(button, true);
            console.error(job.error);
            return;
        }
        if (updateTime !== null) updateTime.html(job.lastUpdate);
        if (job.status !== "running" && job.status !== "queued") {
            if (button !== null) unsetUserButtonUpdating(button);
            break;
        }
        if (button !== null) setUserButtonLoadingBarWidth(button, Math.round(job.progress));
        await new Promise(r => setTimeout(r, interval));
    }
}
async function checkUserUpdateStatus(jobId) {
    return await fetch(`/api/jobs/${jobId}`, {method: "GET"})
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
async function startUserUpdateGroup(groupId) {
    return await startJob(`/api/jobs/user/group/${groupId}`)
}
async function startUserUpdateTeam(teamId, tournamentId) {
    return await startJob(`/api/jobs/user/team/${teamId}/tournament/${tournamentId}`)
}
async function startUserUpdateMatch(matchId) {
    return await startJob(`/api/jobs/user/matchup/${matchId}`)
}
async function startJob(endpoint) {
    return await fetch(endpoint, {method: "POST"})
        .then(res => {
            if (res.ok) {
                return res.json()
            } else {
                if (res.status === 429) {
                    return {"error": "Zu viele Anfragen", "data": res.json()};
                }
                return {"error": "Fehler beim Starten des Jobs"};
            }
        })
        .catch(e => console.error(e));
}

function setUserButtonUpdating(button) {
    setUserButtonLoadingBarWidth(button, 0);
    button.addClass("user_updating");
    button.prop("disabled", true);
}
async function unsetUserButtonUpdating(button, skipFinish = false) {
    if (!skipFinish) {
        setUserButtonLoadingBarWidth(button, 100);
        await new Promise(r => setTimeout(r, 100));
    }
    button.removeClass("user_updating");
    button.prop("disabled", false);
    setUserButtonLoadingBarWidth(button, 0);
}
function setUserButtonLoadingBarWidth(button, widthPercentage) {
    button.attr("style", `--update-loading-bar-width: ${widthPercentage}%`);
}


function refreshTournamentStagePageContent(tournamentId) {
    fragmentLoader(`standings-table?tournamentId=${tournamentId}`)
        .then(html => {
            $("div.standings").replaceWith(html);
        })
    fragmentLoader(`match-button-list?tournamentId=${tournamentId}`)
        .then(html => {
            $("main .matches").replaceWith(html);
        })
}

function refreshTeamInTournamentPageContent(teamId, tournamentId) {
    fragmentLoader(`summoner-cards?teamId=${teamId}&tournamentId=${tournamentId}`, null, null, true)
        .then(html => {
            $("div.summoner-card-container").replaceWith(html);
        })
    fragmentLoader(`multi-opgg-button?teamId=${teamId}&tournamentId=${tournamentId}`, null, null, true)
        .then(html => {
            $(".opgg-cards a.button.op-gg").replaceWith(html);
        })

    const stageButtons = $("button.teampage_switch_group");
    const activeStageButton = $("button.teampage_switch_group.active");
    let activeStageId = (activeStageButton.length > 0) ? activeStageButton.data("group") : null;

    stageButtons.prop("disabled", true);

    let fetchArray = [];
    fetchArray.push(
        fragmentLoader(`standings-table?tournamentId=${activeStageId}&teamId=${teamId}`, null, null, true)
            .then(html => {
                $("div.standings").replaceWith(html);
            })
    )
    fetchArray.push(
        fragmentLoader(`match-button-list?tournamentId=${activeStageId}&teamId=${teamId}`, null, null, true)
            .then(html => {
                $("main .matches").replaceWith(html);
            })
    )
    Promise.all(fetchArray)
        .then(() => stageButtons.prop("disabled", false));
}

function refreshMatchPopupContent(popupId, matchId, teamId = null) {
    const teamIdParam = teamId !== null ? `&teamId=${teamId}` : "";
    const popupIdParam = popupId !== null ? `&popupId=${popupId}` : "";
    fragmentLoader(`match-popup?matchId=${matchId}${teamIdParam}`)
        .then(html => {
            $(`dialog#${popupId} .dialog-content`).html(html);
        })

    let activeStageId = null;
    let groupBody = $(`body.group`);
    if (groupBody.length > 0) {
        activeStageId = groupBody.data("id");
    }
    let stageButtons = null;
    let activeStageButton = null;
    if (teamId !== null) {
        stageButtons = $("button.teampage_switch_group");
        activeStageButton = $("button.teampage_switch_group.active");
        activeStageId = (activeStageButton.length > 0) ? activeStageButton.data("group") : null;
        stageButtons.prop("disabled", true);
    }

    let fetchArray = [];
    fetchArray.push(
        fragmentLoader(`standings-table?tournamentId=${activeStageId}${teamIdParam}`, null, null, true)
            .then(html => {
                $("div.standings").replaceWith(html);
            })
    )
    fetchArray.push(
        fragmentLoader(`match-button?matchupId=${matchId}${teamIdParam}${popupIdParam}`, null, null, true)
            .then(html => {
                $(`div.match-button-wrapper[data-matchid=${matchId}]`).replaceWith(html);
            })
    )
    Promise.all(fetchArray)
        .then(() => {
            if (teamId !== null) {
                stageButtons.prop("disabled", false)
            }
        });
}