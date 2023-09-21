$(document).ready(() => {
	$("#turnier-button-get").on("click", add_tournament_to_db);
});

function create_tournament_buttons() {
	let ref_button = document.getElementsByClassName('refresh-tournaments')[0] ?? null;
	if (ref_button != null) {
		ref_button.innerHTML = "Refreshing...";
	}
	fetch(`./admin/ajax/create_admin_buttons.php`, {
		method: "GET",
	})
		.then(res => res.text())
		.then(content => {
			document.getElementsByClassName("turnier-select")[0].innerHTML = content;
		})
		.catch(e => console.error(e));
}

function add_tournament_to_db() {
	const id = $("#input-tournament-id").val();
	fetch(`./admin/ajax/get-opl-data.php`, {
		method: "GET",
		headers: {
			"type": "tournament",
			"id": id,
		}
	})
		.then(res => res.text())
		.then(result => {
			let result_wrapper = $(".turnier-get-result")
			if (result === "") {
				result_wrapper.addClass("no-res");
			} else {
				result_wrapper.removeClass("no-res");
			}
			result_wrapper.html(result);
		})
		.catch(e => console.error(e));
}