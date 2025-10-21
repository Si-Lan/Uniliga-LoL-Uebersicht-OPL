$(document).on("click", ".multi-select-dropdown>button", function () {
	$(this).closest(".multi-select-dropdown").toggleClass("multi-select-dropdown-open");
})

$(document).on("click", ".multi-select-options>label>input", function () {
	const checkbox = $(this);
	const selectionName = checkbox.val();
	const header = checkbox.closest(".multi-select-dropdown").find(".multi-select-header-text");
	if (checkbox.prop('checked')) {
		const selectionElement = $(`<span class="multi-select-header-selection" data-selection="${selectionName}">${selectionName}</span>`);
		header.append(selectionElement);
		const selectionElements = header.find(".multi-select-header-selection");
		const sortedElements = selectionElements.sort((a, b) => {
			const nameA = $(a).data("selection").toLowerCase();
			const nameB = $(b).data("selection").toLowerCase();

			const regex = /^(\d+)-(\d+)$/;
			const matchA = nameA.match(regex);
			const matchB = nameB.match(regex);
			if (matchA && matchB) {
				// Format X-X wird sortiert
				const aPart1 = parseInt(matchA[1], 10);
				const aPart2 = parseInt(matchA[2], 10);
				const bPArt1 = parseInt(matchB[1], 10);
				const bPArt2 = parseInt(matchB[2], 10);
				
				return aPart1 !== bPArt1 ? aPart1 - bPArt1 : aPart2 - bPArt2;
			} else {
				// Fallback zur alphabetischen Sortierung
				return nameA.localeCompare(nameB);
			}
		});
		header.find(".multi-select-header-selection").remove();
		header.append(sortedElements);
	} else {
		header.find(`.multi-select-header-selection[data-selection="${selectionName}"]`).remove()
	}

	const selectedElements = checkbox.closest(".multi-select-options").find("label>input:checked");
	const selectedElementsNames = selectedElements.map(function () {
		return $(this).val();
	}).get();
	console.log(selectedElementsNames);
})

$(document).on("click", function () {
	const openDropdowns = $(".multi-select-dropdown-open");
	if (openDropdowns.length > 0) {
		openDropdowns.each(function () {
			if (!$(this).is($(event.target).closest(".multi-select-dropdown"))) {
				$(this).removeClass("multi-select-dropdown-open");
			}
		})
	}
})