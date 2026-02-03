$(()=> {
    drawAllBracketLines();
    window.addEventListener("resize", drawAllBracketLines);
})

function drawAllBracketLines() {
    document.querySelectorAll('.elimination-bracket').forEach(bracket => {
        drawBracketLines(bracket);
    })
}

function drawBracketLines(bracket = document.querySelector('.elimination-bracket')) {
    const svg = bracket.querySelector('.bracket-lines');
    const matches = [...bracket.querySelectorAll('.elimination-bracket>.bracket_column .bracket-match')];

    const byId = Object.fromEntries(
        matches.map(m => [m.dataset.id, m])
    );

    svg.setAttribute('viewBox', `0 0 ${bracket.clientWidth} ${bracket.clientHeight}`);
    svg.innerHTML = '';

    matches.forEach(match => {
        // keine Linie ziehen, wenn Spalte versteckt ist
        if (match.closest(".bracket_column").classList.contains("hidden")) return;

        // Linien vom Spiel aus ziehen
        let from = getAnchor(match, "right");
        ["next0", "next1"].forEach(type => {
            const targetId = match.dataset[type];
            if (!targetId || !byId[targetId]) return;

            let to;
            // Wenn die Ziel-Spalte versteckt ist, nur eine kurze Linie ziehen
            if (byId[targetId].closest(".bracket_column").classList.contains("hidden")) {
                to = { x: from.x + 32, y: from.y };
            } else {
                to = getAnchor(byId[targetId], "left");
            }

            drawPath(svg, from, to, match.dataset.id, targetId);
        });

        // Wenn ein vorheriges Spiel existiert, eine kurze Linie zu diesem Spiel ziehen
        if (!match.closest(".bracket_column:first-of-type") && match.dataset.prev0) {
            let to = getAnchor(match, "left");
            from = { x: to.x - 16, y: to.y };
            drawPath(svg, from, to, match.dataset.id, match.dataset.input);
        }
    });
}

function drawPath(svg, from, to, fromId, toId) {
    const path = document.createElementNS("http://www.w3.org/2000/svg", "path");
    path.setAttribute("d", pathBetween(from, to));
    path.setAttribute("fill", "none");
    path.setAttribute("stroke", "var(--accent)");
    path.setAttribute("stroke-width", "2");
    path.setAttribute("data-from", fromId);
    path.setAttribute("data-to", toId);

    svg.appendChild(path);
}

function getAnchor(el, side = "right") {
    const rect = el.getBoundingClientRect();
    const bracket = el.closest('.elimination-bracket');
    const bracketRect = bracket.getBoundingClientRect();

    return {
        x: (side === "right" ? rect.right : rect.left) - bracketRect.left + bracket.scrollLeft,
        y: rect.top - bracketRect.top + bracket.scrollTop + rect.height / 2
    };
}

function pathBetween(a, b) {
    const midX = (a.x + b.x) / 2;

    return `
        M ${a.x} ${a.y}
        L ${midX} ${a.y}
        L ${midX} ${b.y}
        L ${b.x} ${b.y}
    `;
}
function curvedPath(a, b) {
    const dx = (b.x - a.x) * 0.75;

    return `
        M ${a.x} ${a.y}
        C ${a.x + dx} ${a.y},
          ${b.x - dx} ${b.y},
          ${b.x} ${b.y}
    `;
}

function highlightLinesForMatch() {
    const bracketMatch = this;
    const matchId = bracketMatch.dataset.id;
    const bracket = bracketMatch.closest('.elimination-bracket');

    const allPaths = bracket.querySelectorAll('.bracket-lines path');
    const pathsOut = bracket.querySelectorAll('.bracket-lines path[data-from="' + matchId + '"]');
    const pathsIn = bracket.querySelectorAll('.bracket-lines path[data-to="' + matchId + '"]');

    allPaths.forEach(path => {
        path.classList.remove("highlighted");
    })
    pathsOut.forEach(path => {
        path.classList.add("highlighted");
        path.parentNode.appendChild(path);
    });
    pathsIn.forEach(path => {
        path.classList.add("highlighted");
        path.parentNode.appendChild(path);
    });
}
function unhighlightLinesForMatch() {
    const bracketMatch = this;
    const bracket = bracketMatch.closest('.elimination-bracket');
    const allPaths = bracket.querySelectorAll('.bracket-lines path');
    allPaths.forEach(path => {
        path.classList.remove("highlighted");
    })
}
$(document).on("mouseenter", ".bracket-match", highlightLinesForMatch);
$(document).on("mouseleave", ".bracket-match", unhighlightLinesForMatch);


$(document).on("click", "button.hide_column", hideColumn);
function hideColumn() {
    const button = this;
    const column = button.closest(".bracket_column");
    const bracket = column.closest('.elimination-bracket');
    column.classList.add("hidden")
    drawBracketLines(bracket);
}
$(document).on("click", "button.show_columns_right", showRightColumn);
function showRightColumn() {
    const button = this;
    const bracket = button.closest('.elimination-bracket');
    // unhide the first hidden column to the right of a visible column
    bracket.querySelectorAll(".bracket_column:not(.hidden)").forEach(column => {
        const nextColumn = column.nextElementSibling;
        if (nextColumn) nextColumn.classList.remove("hidden");
    });
    drawBracketLines(bracket);

}
$(document).on("click", "button.show_columns_left", showAllLeftColumns);
function showAllLeftColumns() {
    const button = this;
    const bracket = button.closest('.elimination-bracket');
    // unhide the first hidden column to the left of a visible column
    bracket.querySelectorAll(".bracket_column:not(.hidden)").forEach(column => {
        const prevColumn = column.previousElementSibling;
        if (prevColumn) prevColumn.classList.remove("hidden");
    });
    drawBracketLines(bracket);
}