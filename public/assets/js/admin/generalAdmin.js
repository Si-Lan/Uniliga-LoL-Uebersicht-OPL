function toggle_maintenance_mode(turn_on = true) {
	const confirmtext = turn_on ? "Wartungsmodus aktivieren?" : "Wartungsmodus deaktivieren?";
	let continu = confirm(confirmtext);
	if (!continu) return;

	let button = $('button#maintenance-mode');
	let turn = turn_on ? "on" : "off";

	let turn_opposite = turn_on ? "off" : "on";
	button.addClass(`maintenance-${turn}`);

	button.removeClass(`maintenance-${turn_opposite}`);

	fetch(`/admin/api/maintenance/${turn}`, {method: "PUT"})
		.catch(e => console.error(e));
}
$(document).on("click", "button#maintenance-mode", function () {
	let turn = $(this).hasClass("maintenance-off");
	toggle_maintenance_mode(turn);
})