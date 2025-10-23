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
			patchRow.find(`.patchdata-status.json`).attr('data-status', patch.data ? '1':'0');
			patchRow.find(`.patchdata-status.all-img`).attr('data-status', (patch.championWebp && patch.itemWebp && patch.spellWebp && patch.runesWebp) ? '1':'0');
			patchRow.find(`.patchdata-status.champion-img`).attr('data-status', patch.championWebp ? '1':'0');
			patchRow.find(`.patchdata-status.item-img`).attr('data-status', patch.itemWebp ? '1':'0');
			patchRow.find(`.patchdata-status.spell-img`).attr('data-status', patch.spellWebp ? '1':'0');
			patchRow.find(`.patchdata-status.runes-img`).attr('data-status', patch.runesWebp ? '1':'0');
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

$(document).on("click", ".patch-row button.patch-update[data-getimg]:not(.all-img)", async function () {
	const patchNumber = this.dataset.patch;
	const jqButton = $(this);
	setButtonUpdating(jqButton);

	await downloadImg(patchNumber, this.dataset.getimg, jqButton);

	unsetButtonUpdating(jqButton);
})

async function downloadImg(patchNumber, imgType, button=null) {
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
	let batching = 0;
	if (imgType === 'champions' || imgType === 'items') {
		batching = await fetch(`/admin/api/patches/${patchParts[0]}/${patchParts[1]}/${patchParts[2]}/${imgType}/count`, {method: 'GET'})
			.then(res => {
				if (res.ok) {
					return res.text()
				} else {
					return 0;
				}
			})
			.catch(e => console.error(e));
	}
	if (batching > 0) {
		const batchSize = 20;
		const batches = Math.ceil(batching / 20);

		let counter = 0;
		let fetches = [];
		for (let i = 0; i < batches; i++) {
			const startIndex = i * batchSize;
			const endIndex = Math.min((i+1) * batchSize-1, batching);
			const innerQueryParameter = queryParameter + (queryParameter.length > 0 ? "&" : "?") + "start=" + startIndex + "&end=" + endIndex;
			fetches.push(() =>
				fetch(`/admin/api/patches/${patchParts[0]}/${patchParts[1]}/${patchParts[2]}/imgs/${imgType}${innerQueryParameter}`, {method: 'POST'})
					.then(() => {
						counter++;
						if (button !== null) setButtonLoadingBarWidth(button, Math.round(counter / batches * 100));
					})
					.catch(e => console.error(e))
			);
		}

		const staggeredPromises = fetches.map(async (fetchCall, index) => {
			await new Promise(r => setTimeout(r, 4500 * index));
			return fetchCall();
		});
		await Promise.all(staggeredPromises);
	} else {
		await fetch(`/admin/api/patches/${patchParts[0]}/${patchParts[1]}/${patchParts[2]}/imgs/${imgType}${queryParameter}`, {method: 'POST'})
			.catch(e => console.error(e));
	}
	await refreshPatchStatus(patchNumber);
}

$(document).on("click", ".patch-row button.patch-update.all-img", async function () {
	const patchNumber = this.dataset.patch;
	const jqButton = $(this);
	setButtonUpdating(jqButton);

	const otherPatchButtons = $(`button.patch-update[data-getimg][data-patch="${patchNumber}"]`);
	setButtonUpdating(otherPatchButtons);

	const allTypes = ['champions', 'items', 'summoners', 'runes'];
	let counter = 0;
	let fetches = [];
	for (const type of allTypes) {
		const button = otherPatchButtons.filter(`[data-getimg="${type}"]`);
		fetches.push(downloadImg(patchNumber, type, button)
			.then(() => {
				counter++;
				setButtonLoadingBarWidth(jqButton, Math.round(counter / allTypes.length * 100));
				unsetButtonUpdating(button);
			})
			.catch(e => console.error(e))
		);
	}

	await Promise.all(fetches);
	unsetButtonUpdating(jqButton);
	unsetButtonUpdating(otherPatchButtons);
})




function setButtonUpdating(button) {
	setButtonLoadingBarWidth(button, 0);
	button.addClass("button-updating");
	button.prop("disabled",true);
}
function unsetButtonUpdating(button) {
	button.removeClass("button-updating");
	button.prop("disabled",false);
	setButtonLoadingBarWidth(button, 0);
}
function setButtonLoadingBarWidth(button, widthPercentage) {
	button.attr("style", `--loading-bar-width: ${widthPercentage}%`);
}