<?php
/** @var mysqli $dbcn  */

include_once dirname(__DIR__,2)."/src/functions/summoner-card.php";

$teamID = $_GET["team"] ?? NULL;

$team = $dbcn->execute_query("SELECT * FROM teams WHERE OPL_ID = ?", [$teamID])->fetch_assoc();
$groups_played_in = $dbcn->execute_query("SELECT * FROM teams_in_tournaments WHERE OPL_ID_team=? ORDER BY OPL_ID_group DESC",[$teamID])->fetch_all(MYSQLI_ASSOC);
$tournaments_played_in = [];
foreach ($groups_played_in as $event) {
    $event_data = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID = ?", [$event["OPL_ID_group"]])->fetch_assoc();
    if ($event_data["eventType"]=="playoffs") continue;
    $parentID = $event_data["OPL_ID_top_parent"];
    if (array_key_exists($parentID, $tournaments_played_in)) {
        $tournaments_played_in[$parentID][] = $event;
    } else {
        $tournaments_played_in[$parentID] = [$event];
    }
}
$players = $dbcn->execute_query("SELECT * FROM players JOIN players_in_teams pit on players.OPL_ID = pit.OPL_ID_player WHERE pit.OPL_ID_team = ? ", [$teamID])->fetch_all(MYSQLI_ASSOC);
$players_current = $dbcn->execute_query("SELECT * FROM players JOIN players_in_teams pit on players.OPL_ID = pit.OPL_ID_player WHERE pit.OPL_ID_team = ? AND pit.removed = false", [$teamID])->fetch_all(MYSQLI_ASSOC);

if ($team == NULL) {
	$_GET["error"] = "404";
	$_GET["404type"] = "team";
	require "error.php";
	echo "</html>";
	exit();
}

echo create_html_head_elements(title: "{$team["name"]} | Uniliga LoL - Übersicht", loggedin: is_logged_in());

?>
<body class="team general-team <?=is_light_mode(true)?>">
<?php

echo create_header(dbcn: $dbcn, title: "team");

$local_team_img = "/img/team_logos/";
$logo_filename = is_light_mode() ? "logo_light.webp" : "logo.webp";
$opl_team_url = "https://www.opleague.pro/team/";
$opgg_url = "https://www.op.gg/multisearch/euw?summoners=";
$opgg_logo_svg = file_get_contents(__DIR__."/../img/opgglogo.svg");
$opgglink = $opgg_url;
$opgg_amount = 0;
foreach ($players_current as $i=>$player) {
    if ($player["riotID_name"] == null) continue;
	if ($i != 0) {
		$opgglink .= urlencode(",");
	}
	$opgglink .= urlencode($player["riotID_name"]."#".$player["riotID_tag"]);
    $opgg_amount++;
}
echo "<div class='team pagetitle'>";
if ($team['OPL_ID_logo'] != NULL && file_exists(__DIR__."/../$local_team_img{$team['OPL_ID_logo']}/logo.webp")) {
	echo "<img class='color-switch' alt src='$local_team_img{$team['OPL_ID_logo']}/$logo_filename'>";
}
echo "
			<div>
				<h2 class='pagetitle'>{$team['name']}</h2>
				<a href=\"$opl_team_url$teamID\" class='opl-link' target='_blank'><div class='material-symbol'>". file_get_contents(__DIR__."/../icons/material/open_in_new.svg") ."</div></a>
			</div>";
echo "</div>";

echo "<main>";

echo "<div class='team-card-list'>";
foreach ($tournaments_played_in as $tournament_id=>$tournaments) {
    echo create_teamcard($dbcn,$teamID,$tournaments[0]["OPL_ID_group"]);
}
echo "</div>";

echo "
                <div class='player-cards opgg-cards'>
                    <div class='title'>
                        <h3>Aktuelle Spieler</h3>
                        <a href='$opgglink' class='button op-gg' target='_blank'><div class='svg-wrapper op-gg'>$opgg_logo_svg</div><span class='player-amount'>({$opgg_amount} Spieler)</span></a>";

echo "
                     </div>";
echo "
                    <div class='summoner-card-container'>";
foreach ($players_current as $player) {
	echo create_summonercard_general($dbcn,$player["OPL_ID"],$teamID);
}
echo "
                    </div> 
                </div>"; //summoner-card-container -then- player-cards


echo "</main>";

?>
</body>