<?php
include_once __DIR__."/../setup/data.php";
include_once __DIR__."/../functions/fe-functions.php";
include_once __DIR__."/../functions/summoner-card.php";

$pass = check_login();
$lightmode = is_light_mode(true);
$logged_in = is_logged_in();
$admin_btns = admin_buttons_visible(true);

try {
	$dbcn = create_dbcn();
} catch (Exception $e) {
    echo "<!DOCTYPE html><html lang=\"de\">";
	echo create_html_head_elements(title: "Error");
	echo "<body class='$lightmode'>";
	echo create_header(title: "error");
	echo "<div style='text-align: center'>Database Connection failed</div></body>";
	exit();
}

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
?>
<!DOCTYPE html>
<html lang="de">
<?php

if ($team == NULL) {
	echo create_html_head_elements(title: "Turnier nicht gefunden | Uniliga LoL - Übersicht");
	echo "<body class='$lightmode'>";
	echo show_old_url_warning($teamID);
	echo create_header(title: "error");
	echo "<div style='text-align: center'>Kein Team unter der angegebenen ID gefunden!</div></body>";
	exit();
}

echo create_html_head_elements(title: "{$team["name"]} | Uniliga LoL - Übersicht", loggedin: $logged_in);

?>
<body class="team <?php echo "$lightmode $admin_btns"?>">
<?php

echo create_header(dbcn: $dbcn, title: "team");

$local_team_img = "img/team_logos/";
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
echo "<div class='team title'>
			<div class='team-name'>";
if ($team['OPL_ID_logo'] != NULL && file_exists(__DIR__."/../$local_team_img{$team['OPL_ID_logo']}/logo.webp")) {
	echo "<img class='color-switch' alt src='$local_team_img{$team['OPL_ID_logo']}/$logo_filename'>";
}
echo "
			<div>
				<h2>{$team['name']}</h2>
				<a href=\"$opl_team_url$teamID\" class='toorlink' target='_blank'><div class='material-symbol'>". file_get_contents(__DIR__."/../icons/material/open_in_new.svg") ."</div></a>
			</div>
        </div>";
echo "</div>";

echo "<div class='main-content'>";

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


echo "</div>";

?>
</body>
</html>
