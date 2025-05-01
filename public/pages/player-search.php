<?php
/** @var mysqli $dbcn  */

echo create_html_head_elements(title: "Spielersuche | Uniliga LoL - Übersicht", loggedin: is_logged_in());

?>
<body class="players <?=is_light_mode(true)?>">
<?php

echo create_header(dbcn: $dbcn, title: "players");

echo "<main>";
echo "<div><h2>Spielersuche</h2>Suche nach Spielernamen oder RiotID</div>";
echo "<div class='searchbar'>
        <span class='material-symbol search-icon' title='Suche'>
            ". file_get_contents(dirname(__FILE__)."/../icons/material/search.svg") ."
        </span>
        <input class=\"search-players deletable-search\" placeholder='Spieler suchen' type='search'>
        <button class='material-symbol search-clear' title='Suche leeren' type='button'>
            ". file_get_contents(dirname(__FILE__)."/../icons/material/close.svg") ."
        </button>
    </div>";
echo "
            <div class='player-popup-bg' onclick='close_popup_player(event)'>
                <div class='player-popup'></div>
            </div>";
echo "<div class='recent-players-list'></div>";
echo "<div class='player-list'></div>";
echo "</main>";

?>
</body>