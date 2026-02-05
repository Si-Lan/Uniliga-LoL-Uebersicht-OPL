import {setButtonLoadingBarWidth, unsetButtonUpdating} from "./updatingButton";

export async function startJob(endpoint) {
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

export async function checkJobStatusRepeatedly(jobId, interval, button = null) {
    let job = null;
    while (true) {
        job = await checkJobStatus(jobId);
        if (job.error) {
            if (button !== null) unsetButtonUpdating(button, true);
            console.error(job.error);
            return null;
        }
        if (job.status !== "running" && job.status !== "queued") {
            if (button !== null) unsetButtonUpdating(button);
            break;
        }
        if (button !== null) setButtonLoadingBarWidth(button, Math.round(job.progress));
        await new Promise(r => setTimeout(r, interval));
    }
    return job;
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