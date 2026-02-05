import {setButtonUpdating, unsetButtonUpdating, setButtonLoadingBarWidth, finishButtonUpdating} from "../utils/updatingButton";

$(document).on('click', '#add-patch-popup button.add_patch', async function() {
	const patchNumber = this.dataset.patch;
	this.classList.add('button-updating');
	this.disabled = true;
	await addNewPatch(patchNumber);
	$(this).closest('.add-patches-row').remove();
});
async function addNewPatch(patchNumber) {
	await fetch(`/admin/api/patches`, {
		method: 'POST',
		body: JSON.stringify({
			"patch": patchNumber
		})
	})
	.catch(e => console.error(e));

	await refreshPatchList();
}

$(document).on("click", ".patch-row button.patch-delete", async function () {
	const patchNumber = this.dataset.patch;
	const cont = confirm(`Bilder zu Patch ${patchNumber} wirklich löschen?`);
	if (!cont) return;
	this.classList.add('button-updating');
	await deletePatch(patchNumber);
});
async function deletePatch(patchNumber) {
	const patchParts = patchNumber.split('.');
	if (patchParts.length !== 3) {
		return;
	}
	await fetch(`/admin/api/patches/${patchParts[0]}/${patchParts[1]}/${patchParts[2]}`, {method: 'DELETE'})
		.catch(e => console.error(e));
	await refreshPatchList();
}

async function refreshPatchList() {
	await fetch(`/admin/ajax/fragment/patches-list`, {method: 'GET'})
		.then(res => {
			if (res.ok) {
				return res.json()
			} else {
				return {"error": "Fehler beim Laden der Daten"};
			}
		})
		.then(response => {
			if (response.error) {return}
			$('.patch-row').remove();
			$('.patch-table').append(response.html);
		})
		.catch(e => console.error(e));
}
async function refreshPatchStatus(patchNumber) {
	const patchParts = patchNumber.split('.');
	if (patchParts.length !== 3) {
		return;
	}
	await fetch(`/admin/api/patches/${patchParts[0]}/${patchParts[1]}/${patchParts[2]}`, {method: 'GET'})
		.then(res => {
			if (res.ok) {
				return res.json()
			} else {
				return {"error": "Fehler beim Laden der Daten"};
			}
		})
		.then(patch => {
			if (patch.error) {return}
			const patchRow = $(`.patch-row[data-patch="${patchNumber}"]`);
			patchRow.find(`.patchdata-status.json`).attr('data-status', patch.data === null ? '' : (patch.data ? '1' : '0'));
			patchRow.find(`.patchdata-status.all-img`).attr('data-status', patch.championWebp === null && patch.itemWebp === null && patch.spellWebp === null && patch.runesWebp === null ? '' : ((patch.championWebp && patch.itemWebp && patch.spellWebp && patch.runesWebp) ? '1' : '0'));
			patchRow.find(`.patchdata-status.champion-img`).attr('data-status', patch.championWebp === null ? '' : (patch.championWebp ? '1' : '0'));
			patchRow.find(`.patchdata-status.item-img`).attr('data-status', patch.itemWebp === null ? '' : (patch.itemWebp ? '1' : '0'));
			patchRow.find(`.patchdata-status.spell-img`).attr('data-status', patch.spellWebp === null ? '' : (patch.spellWebp ? '1' : '0'));
			patchRow.find(`.patchdata-status.runes-img`).attr('data-status', patch.runesWebp === null ? '' : (patch.runesWebp ? '1' : '0'));
		})
		.catch(e => console.error(e));
}

$(document).on("click", ".patch-row button.patch-update.json", async function () {
	const patchNumber = this.dataset.patch;
	this.classList.add('button-updating');
	this.disabled = true;

	await downloadJson(patchNumber);

	this.classList.remove('button-updating');
	this.disabled = false;
})

async function downloadJson(patchNumber) {
	const patchParts = patchNumber.split('.');
	if (patchParts.length !== 3) {
		return;
	}
	await fetch(`/admin/api/patches/${patchParts[0]}/${patchParts[1]}/${patchParts[2]}/json`, {method: 'POST'})
		.catch(e => console.error(e));
	await refreshPatchStatus(patchNumber);
}

// Beim Start und alle 10s nach laufenden Jobs suchen und Buttons aktualisieren
async function updateCheckingJobs() {
    const jobs = await getRunningDdragonJobs();
    if (jobs.error) {
        return;
    }
    for (const job of jobs) {
        if (!checkingJobIds.includes(job.id)) {
            checkingJobIds.push(job.id);
        }
    }
	if (checkingJobIds.length > 0) {
		checkJobStatusForPatchesRepeatedly();
	}
}
$(function () {
    setInterval(updateCheckingJobs, 10000);
	updateCheckingJobs();
})

$(document).on("click", ".patch-row button.patch-update[data-getimg]:not(.all-img)", async function () {
	const patchNumber = this.dataset.patch;
	const jqButton = $(this);
	setButtonUpdating(jqButton);

	await downloadImg(patchNumber, this.dataset.getimg)
})

async function downloadImg(patchNumber, imgType) {
	const patchParts = patchNumber.split('.');
	if (patchParts.length !== 3) {
		return;
	}
	let queryParameter = "";

	let overwrite = $('#force-overwrite-patch-img')[0].checked;
	if (overwrite) {
		queryParameter += (queryParameter.length > 0 ? "&" : "?")
		queryParameter += 'overwrite=true';
	}

    await fetch(`/admin/api/patches/${patchParts[0]}/${patchParts[1]}/${patchParts[2]}/imgs/${imgType}${queryParameter}`, {method: 'POST'})
        .then(res => {
            if (res.ok) {
                return res.json()
            } else {
                return {"error": "Fehler beim Laden der Daten"};
            }
        })
        .then(response => {
            if (response.error) {return}
            const jobId = response['job_id'];
            if (!checkingJobIds.includes(jobId)) {
                checkingJobIds.push(jobId);
            }
            checkJobStatusForPatchesRepeatedly();
        })
        .catch(e => console.error(e));
}

$(document).on("click", ".patch-row button.patch-update.all-img", async function () {
	const patchNumber = this.dataset.patch;
	const jqButton = $(this);
	setButtonUpdating(jqButton);

	const otherPatchButtons = $(`button.patch-update[data-getimg][data-patch="${patchNumber}"]`);
	setButtonUpdating(otherPatchButtons);

	const allTypes = ['champions', 'items', 'summoners', 'runes'];
	for (const type of allTypes) {
        downloadImg(patchNumber, type);
	}
})

$(document).on("click", ".patch-header button.sync_patches", async function () {
	const jqButton = $(this);
	setButtonUpdating(jqButton);
	await syncDataForPatches(jqButton);
	finishButtonUpdating(jqButton);
})
async function syncDataForPatches(button = null) {
	const syncResult = await fetch(`/admin/api/patches/sync`, {method: 'GET'})
		.then(res => {
			if (res.ok) {
				return res.json()
			} else {
				return {"error": "Fehler beim Laden der Daten"};
			}
		})
		.catch(e => console.error(e));
	if (syncResult.error) {
		return;
	}
	const addedPatches = syncResult.added.join(", ");
	const patches = syncResult.patches;
	await refreshPatchList();

	let counter = 0;
	let updatedPatches = [];
	for (const patch of patches) {
		const patchParts = patch.patchNumber.split('.');
		if (patchParts.length !== 3) {
			continue;
		}
		const checkResult = await fetch(`/admin/api/patches/${patchParts[0]}/${patchParts[1]}/${patchParts[2]}/check`, {method: 'GET'})
			.then(res => {
				if (res.ok) {
					return res.json()
				} else {
					return {"error": "Fehler beim Laden der Daten"};
				}
			})
			.catch(e => console.error(e));
		if (!checkResult.error && checkResult.result === "updated") {
			updatedPatches.push(checkResult.entity?.patchNumber);
		}
		counter++;
		if (button !== null) setButtonLoadingBarWidth(button, Math.round(counter / patches.length * 100));
	}

	await refreshPatchList();

	$('dialog.patch-result-popup .dialog-content').html(`neue Patches: ${addedPatches === "" ? "keine" : addedPatches}<br> aktualisierte Patches: ${updatedPatches.length === 0 ? "keine" : updatedPatches.join(", ")}`);
	$('dialog.patch-result-popup')[0].showModal();
}


/**
 * Nimmt ein Array an Jobs und aktualisiert alle Buttons die zu einem laufenden Update gehören
 * @param {array} activeJobs
 */
async function updateButtonProgress(activeJobs) {
    for (const job of activeJobs) {
        const patchNumber = job.context?.patchNumber;
        if (patchNumber === null) {
            continue;
        }
        let imgClass = null;
        switch (job.action) {
            case "download_champion_images": imgClass = "champions"; break;
            case "download_item_images": imgClass = "items"; break;
            case "download_spell_images": imgClass = "summoners"; break;
            case "download_rune_images": imgClass = "runes"; break;
        }
        if (imgClass === null) {
            continue;
        }
        const button = $(`button.patch-update[data-patch="${patchNumber}"][data-getimg="${imgClass}"]`);
        if (!button.hasClass("button-updating")) setButtonUpdating(button);
        setButtonLoadingBarWidth(button, Math.round(job.progress));
        if (job.status !== "running" && job.status !== "queued") {
            unsetButtonUpdating(button);
            refreshPatchStatus(job.context["patchNumber"])
            checkingJobIds = checkingJobIds.filter(id => id !== job.id);
        }
    }

    const updateAllButtons = $(`button.patch-update[data-getimg="all"]`);
    if (updateAllButtons.length > 0) {
        for (const button of updateAllButtons) {
            const jqButton = $(button);
            const patch = button.dataset.patch;
            const allTypes = ['champions', 'items', 'summoners', 'runes'];
            let updatingButtons = 0;
            for (const type of allTypes) {
                const button = $(`button.patch-update[data-patch="${patch}"][data-getimg="${type}"]`);
                if (button.hasClass("button-updating")) updatingButtons++;
            }
            if (updatingButtons === 0) {
                unsetButtonUpdating(jqButton);
            } else {
                setButtonLoadingBarWidth(jqButton, Math.round((allTypes.length - updatingButtons) / allTypes.length * 100));
            }
        }
    }
}

let checkingJobIds = [];
let patchCheckRunning = false;

/**
 * Holt aktuellen Status für Jobs in checkingJobIds, und aktualisiert deren Buttons
 * @returns {Promise<void>}
 */
async function checkJobStatusForPatchesRepeatedly() {
    if (patchCheckRunning) {
        return;
    }
    while (checkingJobIds.length > 0) {
        patchCheckRunning = true;
        const jobs = await getJobStatusForPatches(checkingJobIds);
        if (jobs.error) {
            console.error(jobs.error);
            patchCheckRunning = false;
            return;
        }
        await updateButtonProgress(jobs);
        if (jobs.length === 0) {
            break;
        }
        await new Promise(r => setTimeout(r, 1000));
    }
    patchCheckRunning = false;
}

/**
 * Wrapper für /api/jobs?ids=...
 * @param jobIds
 * @returns {Promise<*|{error: string}>}
 */
async function getJobStatusForPatches(jobIds) {
    const jobIdsString = jobIds.join(",");
    return await fetch(`/api/jobs?ids=${jobIdsString}`)
        .then(res => {
            if (res.ok) {
                return res.json()
            } else {
                return {'error': 'Fehler beim Laden der Daten'};
            }
        })
        .catch(e => {
            console.error(e);
            return {'error': 'Fehler beim Laden der Daten'};
        });
}

/**
 * Wrapper für /api/jobs/ddragon/running
 * @returns {Promise<*|{error: string}>}
 */
async function getRunningDdragonJobs() {
    return await fetch(`/api/jobs/ddragon/running`)
        .then(res => {
            if (res.ok) {
                return res.json()
            } else {
                return {'error': 'Fehler beim Laden der Daten'};
            }
        })
        .catch(e => {
            console.error(e);
            return {'error': 'Fehler beim Laden der Daten'};
        });
}