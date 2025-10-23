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