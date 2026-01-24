$(document).on('click', '.ranked-split-list .button-row button.add_ranked_split', addNewRankedSplit);
function addNewRankedSplit(){
	fetch(`/admin/ajax/fragment/ranked-split-row`, {method: 'GET'})
		.then(res => {
			if (res.ok) {
				return res.json()
			} else {
				return {"error": "Fehler beim Laden der Daten"};
			}
		})
		.then(response => {
			if (response.error) {return}
			$('.ranked-split-list .button-row').before(response.html);
		})
}

$(document).on('click', '.ranked-split-list button.save_ranked_split', async function () {await saveNewRankedSplit(this)});
async function saveNewRankedSplit(button){
	const row = $(button).closest('.ranked-split-edit');
	const season = row.find(".write_ranked_split_season input").eq(0).val();
	const split = row.find(".write_ranked_split_split input").eq(0).val() ?? null;
	const split_start = row.find(".write_ranked_split_startdate input").eq(0).val();
	const split_end = row.find(".write_ranked_split_enddate input").eq(0).val() ?? null;

	await fetch(`/admin/api/rankedsplits`, {
		method: 'POST',
		headers: {
			'Content-Type': 'application/json'
		},
		body: JSON.stringify({
			"season": season,
			"split": split,
			"split_start": split_start,
			"split_end": split_end
		})
	})
		.catch(e => console.error(e));

	await fetch(`/admin/ajax/fragment/ranked-split-row?season=${season}&split=${split??0}`, {method: 'GET'})
		.then(res => {
			if (res.ok) {
				return res.json()
			} else {
				return {"error": "Fehler beim Laden der Daten"};
			}
		})
		.then(response => {
			if (response.error) {return}
			row.replaceWith(response.html);
		})
}

$(document).on('click', '.ranked-split-list button.delete_ranked_split', async function () {await deleteRankedSplit(this)});
async function deleteRankedSplit(button){
	const row = $(button).closest('.ranked-split-edit');
	const season = row.find(".write_ranked_split_season input").eq(0).val();
	const split = row.find(".write_ranked_split_split input").eq(0).val() ?? null;

	const confirmation = confirm(`Split ${season}-${split} wirklich löschen?`);

	if (!confirmation) {return}

	await fetch(`/admin/api/rankedsplits/${season}/${split}`, {method: 'DELETE'})
		.then(res => {
			if (res.ok) {
				return res.json()
			} else {
				return {"error": "Fehler beim Laden der Daten"};
			}
		})
		.then(response => {
			if (response.error || !response.deleted) {return}
			row.remove();
		})
		.catch(e => console.error(e));
}

$(document).on('click', '.ranked-split-list .button-row button.save_ranked_split_changes', saveRankedSplitChanges)
async function saveRankedSplitChanges() {
	const list = $('.ranked-split-list');
	const all_rows = list.find('.ranked-split-edit').not('.ranked_split_write');
	const edited_rows = list.find('.ranked-split-edit').not('.ranked_split_write').has('.input_changed');
	console.log(edited_rows);
	for (const row of edited_rows) {
		const season = row.querySelector('.write_ranked_split_season input').value;
		const split = row.querySelector('.write_ranked_split_split input').value;
		const split_start = row.querySelector('.write_ranked_split_startdate input').value;
		const split_end = row.querySelector('.write_ranked_split_enddate input').value;
		await fetch(`/admin/api/rankedsplits/${season}/${split}`, {
			method: 'PUT',
			headers: {
				'Content-Type': 'application/json'
			},
			body: JSON.stringify({
				"split_start": split_start,
				"split_end": split_end
			})
		})
			.catch(e => console.error(e));
	}

	await fetch(`/admin/ajax/fragment/ranked-split-rows`, {method: 'GET'})
		.then(res => {
			if (res.ok) {
				return res.json()
			} else {
				return {"error": "Fehler beim Laden der Daten"};
			}
		})
		.then(response => {
			if (response.error) {return}
			all_rows.remove();
			list.prepend(response.html);
		})
		.catch(e => console.error(e));
}

$(document).on('click', '.ranked-split-list button.cancel_ranked_split', function(){
	$(this).closest('.ranked-split-edit').remove();
});
$(document).on('click', '.ranked-split-edit button.reset_inputs', function(){
	$(this).closest('.ranked-split-edit').find("input").each(function(i, obj){
		obj.value = obj.defaultValue;
		$(obj).removeClass("input_changed");
	});
});
$(document).on('change', '.ranked-split-edit:not(.ranked_split_write) input', function() {
	if (this.value !== this.defaultValue) {
		$(this).addClass("input_changed");
	} else {
		$(this).removeClass("input_changed");
	}
});