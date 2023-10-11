<?php
include_once(dirname(__FILE__)."/helper.php");

function create_html_head_elements(array $css = [], array $js = [], string $title="Uniliga LoL - Übersicht", bool $head_wrap = TRUE, bool $loggedin=FALSE):string {
	$result = "";
	if ($head_wrap) {
		$result .= "<head>";
	}

	// basic content
	$result .= "<base href='/uniliga/'>";
	$result .= "<meta name='viewport' content='width=device-width, initial-scale=1'>";
	$result .= "<link rel='icon' href='https://silence.lol/favicon-dark.ico' media='(prefers-color-scheme: dark)'/>";
	$result .= "<link rel='icon' href='https://silence.lol/favicon-light.ico' media='(prefers-color-scheme: light)'/>";
	$result .= "<link rel='stylesheet' href='styles/main.css'>";
	$result .= "<script src='scripts/jquery-3.7.1.min.js'></script>";
	$result .= "<script src='scripts/main.js'></script>";
	// additional css
	if (in_array("elo",$css)) {
		$result .= "<link rel='stylesheet' href='styles/elo-rank-colors.css'>";
	}
	if (in_array("game",$css)) {
		$result .= "<link rel='stylesheet' href='styles/game.css'>";
	}
	if (in_array("admin",$css)) {
		$result .= "<link rel='stylesheet' href='admin/styles/style.css'>";
	}
	if (in_array("rgapi",$css)) {
		$result .= "<link rel='stylesheet' href='admin/styles/rgapi.css'>";
	}
	// additional js
	if (in_array("rgapi", $js) && $loggedin) {
		$result .= "<script src='admin/scripts/rgapi.js'></script>";
	}
	if (in_array("admin", $js) && $loggedin) {
		$result .= "<script src='admin/scripts/main.js'></script>";
	}
	// title
	$result .= "<title>$title</title>";

	if ($head_wrap) {
		$result .= "</head>";
	}
	return $result;
}

function create_header(mysqli $dbcn = NULL, string $title = "home", string|int $tournament_id = NULL, bool $home_button = TRUE, bool $open_login = FALSE):string {
	include_once(dirname(__FILE__) . "/../setup/data.php");
	$password = get_admin_pass();
	$loginforminfo = "";
	$loginopen = $open_login ? "modalopen_auto" : "";

	if (isset($_GET["login"])) {
		if (isset($_POST["keypass"])) {
			if ($_POST["keypass"] != $password) {
				$loginforminfo = "Falsches Passwort";
				$loginopen = "modalopen_auto";
			} else {
				setcookie('admin-login', password_hash($password, PASSWORD_BCRYPT),time()+31536000,'/');
				$pageurl = preg_replace('~([?&])login(=*)[^&]*~', '$1', $_SERVER['REQUEST_URI']);
				$pageurl = rtrim($pageurl, "?");
				header("Location: $pageurl");
			}
		} else {
			$loginopen = "modalopen_auto";
		}
	}
	if (isset($_GET["logout"])) {
		if (isset($_COOKIE['admin-login'])) {
			unset($_COOKIE['admin-login']);
			setcookie('admin-login','',time()-3600,'/');
		}
		if (isset($_COOKIE['admin_btns'])) {
			unset($_COOKIE['admin_btns']);
			setcookie('admin_btns','',time()-3600,'/');
		}
		$pageurl = preg_replace('~([?&])logout(=*)[^&]*~', '$1', $_SERVER['REQUEST_URI']);
		$pageurl = rtrim($pageurl, "?");
		header("Location: $pageurl");
	}

	$result = "";

	$pageurl = $_SERVER['REQUEST_URI'];

	$opl_event_url = "https://www.opleague.pro/event";
	$outlinkicon = file_get_contents(dirname(__FILE__)."/../icons/material/open_in_new.svg");
	if ($dbcn != NULL && $tournament_id != NULL) {
		$tournament = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID = ?",[$tournament_id])->fetch_assoc();
		$t_name_clean = preg_replace("/LoL/","",$tournament["name"]);
	}

	$loggedin = is_logged_in();
	$colormode = is_light_mode() ? "light" : "dark";

	$result .= "<header class='$title'>";
	if ($home_button) {
		$result .= "
	<a href='.' class='homelink'>
		<div class='material-symbol'>".file_get_contents(dirname(__FILE__)."/../icons/material/home.svg")."</div>
	</a>";
	}
	$result .= "<div class='title'>";
	if ($title == "players") {
		$result .= "<h1>Uniliga LoL - Spieler</h1>";
	} elseif ($title == "tournament" && $dbcn != NULL && $tournament_id != NULL) {
		$result .= "<h1>$t_name_clean</h1>";
		$result .= "<a href='$opl_event_url/$tournament_id' target='_blank' class='toorlink'><div class='material-symbol'>$outlinkicon</div></a>";
	} elseif ($title == "admin_dd") {
		$result .= "<h1>Uniliga LoL - DDragon Updates</h1>";
	} elseif ($title == "admin") {
		$result .= "<h1>Uniliga LoL - Admin</h1>";
	} elseif ($title == "rgapi") {
		$result .= "<h1>Uniliga LoL - Riot-API-Daten</h1>";
	} else {
		$result .= "<h1>Uniliga LoL - Übersicht</h1>";
	}
	$result .= "</div>";
	$result .= "<a class='settings-button' href='$pageurl'><div class='material-symbol'>". file_get_contents(dirname(__FILE__)."/../icons/material/tune.svg") ."</div></a>";
	if ($loggedin) {
		$admin_button_state = admin_buttons_visible() ? "" : "_off";
		$result .= "
			<div class='settings-menu'>
				<a class='settings-option toggle-mode' href='$pageurl'><div class='material-symbol'>". file_get_contents(dirname(__FILE__)."/../icons/material/{$colormode}_mode.svg") ."</div></a>
				<a class='settings-option toggle-admin-b-vis' href='$pageurl'>Buttons<div class='material-symbol'>". file_get_contents(dirname(__FILE__)."/../icons/material/visibility$admin_button_state.svg") ."</div></a>
				<a class='settings-option toor-write' href='./admin'>Admin<div class='material-symbol'>". file_get_contents(dirname(__FILE__)."/../icons/material/edit_square.svg") ."</div></a>
				<a class='settings-option rgapi-write' href='./admin/rgapi'>RGAPI<div class='material-symbol'>". file_get_contents(dirname(__FILE__)."/../icons/material/videogame_asset.svg") ."</div></a>
				<a class='settings-option ddragon-write' href='./admin/ddragon'>DDragon</a>
				<a class='settings-option logout' href='$pageurl?logout'>Logout<div class='material-symbol'>". file_get_contents(dirname(__FILE__)."/../icons/material/logout.svg") ."</div></a>
			</div>";
	} else {
		$result .= "
			<div class='settings-menu'>
				<a class='settings-option toggle-mode' href='$pageurl'><div class='material-symbol'>". file_get_contents(dirname(__FILE__)."/../icons/material/{$colormode}_mode.svg") ."</div></a>
				<a class='settings-option github-link' href='https://github.com/Si-Lan/Uniliga-LoL-Uebersicht' target='_blank'>GitHub<div class='material-symbol'>". file_get_contents(dirname(__FILE__)."/../img/github-mark-white.svg") ."</div></a>
				<a class='settings-option' href='https://paypal.me/SimonlLang' target='_blank'>Spenden<div class='material-symbol'>". file_get_contents(dirname(__FILE__)."/../icons/material/payments.svg") ."</div></a>
				<a class='settings-option feedback' href=''>Feedback<div class='material-symbol'>". file_get_contents(dirname(__FILE__)."/../icons/material/mail.svg") ."</div></a>
				<a class='settings-option login' href='$pageurl?login'>Login<div class='material-symbol'>". file_get_contents(dirname(__FILE__)."/../icons/material/login.svg") ."</div></a>
			</div>";
	}
	$result .= "</header>";

	$result .= "
		<dialog id='login-dialog' class='dismissable-popup $loginopen'>
			<div class='dialog-content'>
				<button class='close-popup'><div class='material-symbol'>".file_get_contents(dirname(__FILE__)."/../icons/material/close.svg")."</div></button>
				<div class='close-button-space'></div>
				<div style='display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 40px'>
					<form action='".strtok($_SERVER['REQUEST_URI'],'?')."?login' method='post' style='display: flex; flex-direction: column; align-items: center; gap: 1em;'>
						<label class='password-label'><input type='password' name='keypass' id='keypass' placeholder='Password' /></label>
						$loginforminfo
						<input type='submit' id='submit' value='Login' />
					</form>
				</div>
			</div>
		</dialog>";

	return $result;
}

function create_tournament_nav_buttons(string|int $tournament_id, mysqli $dbcn = NULL, $active="",$division_id=NULL,$group_id=NULL):string {
	$result = "";

	$overview = $list = $elo = $group_a = "";
	if ($active == "overview") {
		$overview = " active";
	} elseif ($active == "list") {
		$list = " active";
	} elseif ($active == "elo") {
		$elo = " active";
	} elseif ($active == "group") {
		$group_a = " active";
	}
	$teamlink_addition = "";
	if ($division_id != NULL) {
		$teamlink_addition = "?liga=$division_id";
		if ($group_id != NULL) {
			$teamlink_addition .= "&gruppe=$group_id";
		}
	}
	$result .= "
		<div class='turnier-bonus-buttons'>
			<div class='turnier-nav-buttons'>
				<a href='turnier/{$tournament_id}' class='button$overview'>
    	        	<div class='material-symbol'>". file_get_contents(__DIR__."/../icons/material/sports_esports.svg") ."</div>
        		    Turnier
            	</a>
	            <a href='turnier/{$tournament_id}/teams$teamlink_addition' class='button$list'>
    	        	<div class='material-symbol'>". file_get_contents(__DIR__."/../icons/material/group.svg") ."</div>
        	        Teams
            	</a>
	            <a href='turnier/{$tournament_id}/elo' class='button$elo'>
    	            <div class='material-symbol'>". file_get_contents(__DIR__."/../icons/material/stars.svg") ."</div>
        	        Eloverteilung
            	</a>
            </div>";

	if ($group_id != NULL && $active != "group") {
		$group = $dbcn->execute_query("SELECT * FROM tournaments WHERE eventType = 'group' AND OPL_ID = ?",[$group_id])->fetch_assoc();
		$div = $dbcn->execute_query("SELECT * FROM tournaments WHERE eventType = 'league' AND OPL_ID = ?",[$group['OPL_ID_parent']])->fetch_assoc();
		if ($div["format"] === "Swiss") {
			$group_title = "Swiss-Gruppe";
		} else {
			$group_title = "Gruppe {$group['number']}";
		}
		$result .= "
			<div class='divider-vert'></div>
			<a href='turnier/{$tournament_id}/gruppe/$group_id' class='button$group_a'>
                <div class='material-symbol'>". file_get_contents(__DIR__."/../icons/material/table_rows.svg") ."</div>
                Liga ".$div['number']." - $group_title
            </a>";
	}

	$result .= "</div>";
	$result .= "<div class='divider bot-space'></div>";

	return $result;
}

function generate_elo_list($dbcn,$view,$teams,$tournamentID,$division,$group):string {
	$results = "";
	$local_team_img = "img/team_logos/";
	$opgg_logo_svg = file_get_contents(__DIR__."/../img/opgglogo.svg");
	$opgg_url = "https://www.op.gg/multisearch/euw?summoners=";
	$view_class = "";
	if ($view != NULL) {
		$view_class = " " . $view . "-teams";
	}
	$results .= "
                <div class='teams-elo-list content$view_class'>";
	if ($view == "all") {
		$results .= "
                    <h3>Alle Ligen</h3>";
	} elseif ($view == "div") {
		$results .= "
                    <h3 class='liga{$division['number']}'>Liga {$division['number']}</h3>";
	} elseif ($view == "group") {
		if ($division["format"] === "Swiss") {
			$results .= "
                    <h3 class='liga{$division['number']}'>Liga {$division['number']} - Swiss-Gruppe</h3>";
		} else {
			$results .= "
                    <h3 class='liga{$division['number']}'>Liga {$division['number']} - Gruppe {$group['number']}</h3>";
		}
	}
	$results .= "
                    <div class='elo-list-row elo-list-header'>
                        <div class='elo-list-pre-header league'>Liga #</div>
                        <a class='elo-list-item-wrapper-header'>
                        <div class='elo-list-item team'>Team</div>
                        <div class='elo-list-item rank'>avg. Rang</div>
                        </a>
                        <div class='elo-list-after-header elo-nr'>Elo</div>
                        <a class='elo-list-after-header op-gg'><div class='svg-wrapper op-gg'></div></a>
                    </div>";
	foreach ($teams as $team) {
		$curr_players = $dbcn->execute_query("SELECT p.* FROM players p JOIN players_in_teams pit on p.OPL_ID = pit.OPL_ID_player WHERE OPL_ID_team = ?",[$team['OPL_ID']])->fetch_all(MYSQLI_ASSOC);
		$curr_opgglink = $opgg_url;
		$color_class = "";
		if ($view == "all") {
			$color_class = " liga".$team['number_league'];
		} elseif ($view == "div" || $view == "group") {
			$color_class = " rank".floor($team['avg_rank_num']);
		}
		foreach ($curr_players as $i_cop => $curr_player) {
			if ($i_cop != 0) {
				$curr_opgglink .= urlencode(",");
			}
			$curr_opgglink .= urlencode($curr_player["summonerName"]);
		}
		$results .= "
                    <div class='elo-list-row elo-list-team {$team['OPL_ID']}$color_class'>
                        <div class='elo-list-pre league'>Liga {$team['number_league']}</div>
                        <a href='./team/".$team['OPL_ID']."' onclick='popup_team(\"{$team['OPL_ID']}\",\"{$tournamentID}\")' class='elo-list-item-wrapper'>
                            <div class='elo-list-item team'>";
		if ($team['OPL_ID_logo'] != NULL && file_exists(__DIR__."/../$local_team_img{$team['OPL_ID_logo']}/logo.webp")) {
			$results .= "
                                <img src='$local_team_img{$team['OPL_ID_logo']}/logo.webp' alt='Teamlogo'>";
		}
		$results .= "
                                <span>{$team['name']}</span>
                            </div>
                            <div class='elo-list-item rank'>";
		if ($team['avg_rank_tier'] != NULL) {
			$avg_rank = strtolower($team['avg_rank_tier']);
			$avg_rank_cap = ucfirst($avg_rank);
			$avg_rank_num = round($team['avg_rank_num'], 2);
			$results .= "
                                <img class='rank-emblem-mini' src='ddragon/img/ranks/mini-crests/{$avg_rank}.svg' alt='$avg_rank_cap'>
                                <span>{$avg_rank_cap} {$team['avg_rank_div']}</span>";
		} else {
			$avg_rank_num = 0.00;
		}
		$results .= "
                            </div>
                        </a>
                        <div class='elo-list-after elo-nr'>
                            <span>({$avg_rank_num})</span>
                        </div>
                        <a href='$curr_opgglink' target='_blank' class='elo-list-after op-gg'>
                            <div class='svg-wrapper op-gg'>$opgg_logo_svg</div>
                        </a>
                        
                    </div>";
	}
	$results .= "
                </div>"; // teams-elo-list

	return $results;
}

function create_standings(mysqli $dbcn, $tournament_id, $group_id, $team_id=NULL):string {
	$result = "";
	$opgg_url = "https://www.op.gg/multisearch/euw?summoners=";
	$local_img_path = "img/team_logos/";
	$opgg_logo_svg = file_get_contents(__DIR__."/../img/opgglogo.svg");
	$group = $dbcn->execute_query("SELECT * FROM tournaments WHERE eventType = 'group' AND OPL_ID = ?",[$group_id])->fetch_assoc();
	$div = $dbcn->execute_query("SELECT * FROM tournaments WHERE eventType = 'league' AND OPL_ID = ?",[$group['OPL_ID_parent']])->fetch_assoc();
	$teams_from_groupDB = $dbcn->execute_query("SELECT * FROM teams JOIN teams_in_tournaments tit ON teams.OPL_ID = tit.OPL_ID_team  WHERE tit.OPL_ID_tournament = ? ORDER BY IF((standing=0 OR standing IS NULL), 1, 0), standing",[$group['OPL_ID']])->fetch_all(MYSQLI_ASSOC);

	$result .= "<div class='standings'>";
	if ($team_id == NULL) {
		$result .= "<div class='title'><h3>Standings</h3></div>";
	} else {
		$result .= "<div class='title'><h3>Standings Liga {$div['number']} / Gruppe {$group['number']}</h3></div>";
	}
	$result .= "<div class='standings-table content'>
			<div class='standing-row standing-header'>
				<div class='standing-pre-header rank'>#</div>
				<a class='standing-item-wrapper-header'>
					<div class='standing-item team'>Team</div>
					<div class='standing-item played'>Pl</div>
					<div class='standing-item score'>W - D - L</div>
					<div class='standing-item points'>Pt</div>
					<a class='standing-after-header op-gg'><div class='svg-wrapper op-gg'></div></a>
                </a>
            </div>";
	$last_rank = -1;
	foreach ($teams_from_groupDB as $currteam) {
		$curr_players = $dbcn->execute_query("SELECT * FROM players JOIN players_in_teams pit on players.OPL_ID = pit.OPL_ID_player WHERE OPL_ID_team = ?",[$currteam['OPL_ID']])->fetch_all(MYSQLI_ASSOC);
		$curr_opgglink = $opgg_url;
		foreach ($curr_players as $i_cop=>$curr_player) {
			if ($i_cop != 0) {
				$curr_opgglink .= urlencode(",");
			}
			$curr_opgglink .= urlencode($curr_player["summonerName"]);
		}
		if ($team_id != NULL) {
			$current = ($currteam['OPL_ID'] == $team_id)? " current" : "";
		} else {
			$current = "";
		}
		$same_rank_class = "";
		if ($last_rank == $currteam['standing']) {
			$same_rank_class = " no-bg";
		}
		$result .= "<div class='standing-row standing-team$current'>
				<div class='standing-pre rank$same_rank_class'>{$currteam['standing']}</div>
				<a href='turnier/$tournament_id/team/{$currteam['OPL_ID']}' class='standing-item-wrapper'>
				<div class='standing-item team'>";
		if ($currteam['OPL_ID_logo'] != NULL && file_exists(__DIR__."/../$local_img_path{$currteam['OPL_ID_logo']}/logo.webp")) {
			$result .= "<img src='$local_img_path{$currteam['OPL_ID']}/logo.webp' alt=\"Teamlogo\">";
		}
		if ($currteam['avg_rank_tier'] != NULL) {
			$team_tier = strtolower($currteam['avg_rank_tier']);
			$team_tier_cap = ucfirst($team_tier);
			$result .= "<div class='team-name-rank'>
                        <span>{$currteam['name']}</span>
                        <span class='rank'>
                            <img class='rank-emblem-mini' src='ddragon/img/ranks/mini-crests/$team_tier.svg' alt='$team_tier_cap'>
                            $team_tier_cap ".$currteam['avg_rank_div']."
                        </span>
                      </div>
                  </div>";
		} else {
			$result .= "<span>{$currteam['name']}</span></div>";
		}
		$result .= "
                    <div class='standing-item played'>{$currteam['played']}</div>
                    <div class='standing-item score'>{$currteam['wins']} - {$currteam['draws']} - {$currteam['losses']}</div>
                    <div class='standing-item points'>{$currteam['points']}</div>
                    <a href='$curr_opgglink' target='_blank' class='standing-after op-gg'><div class='svg-wrapper op-gg'>$opgg_logo_svg</div></a>
                </a>
            </div>";
		$last_rank = $currteam['standing'];
	}
	$result .= "</div></div>";

	return $result;
}

function create_matchbutton(mysqli $dbcn,$match_id,$type,$team_id=NULL):string {
	$result = "";
	$pageurl = $_SERVER['REQUEST_URI'];
	$opl_match_url = "https://www.opleague.pro/match/";
	if ($type == "groups") {
		$match = $dbcn->execute_query("SELECT * FROM matchups WHERE OPL_ID = ?",[$match_id])->fetch_assoc();
	} elseif ($type == "playoffs") {
		return "";
		//$match = $dbcn->execute_query("SELECT * FROM playoffmatches WHERE MatchID = ?",[$match_id])->fetch_assoc();
	} else {
		return "";
	}
	$teams_from_DB = $dbcn->execute_query("SELECT * FROM teams")->fetch_all(MYSQLI_ASSOC);
	$teams = [];
	foreach ($teams_from_DB as $i=>$team) {
		$teams[$team['OPL_ID']] = array("TeamName"=>$team['name'], "imgID"=>$team['OPL_ID']);
	}

	$current1 = "";
	$current2 = "";
	if ($team_id != NULL) {
		if ($match['OPL_ID_team1'] == $team_id) {
			$current1 = " current";
		} elseif ($match['OPL_ID_team2'] == $team_id) {
			$current2 = " current";
		}
	}

	if ($match['played'] == 0) {
		$datetime = date_create($match['plannedDate']);
		$date = date_format($datetime, 'd M');
		$time = date_format($datetime, 'H:i');
		$result .= "<div class='match-button-wrapper' data-matchid='$match_id' data-matchtype='$type'>
                            <a class='button match nolink sideext-right'>
                                <div class='teams'>
                                    <div class='team 1$current1'><div class='name'>{$teams[$match['OPL_ID_team1']]['TeamName']}</div></div>
                                    <div class='team 2$current2'><div class='name'>{$teams[$match['OPL_ID_team2']]['TeamName']}</div></div>
                                </div>";
		if ($match['plannedDate'] == NULL || strtotime($match['plannedDate']) == 0) {
			$result .= "<div>vs.</div>";
		} else {
			$result .= "<div class='date'>{$date}<br>{$time}</div>";
		}
		$result .= "</a>
                          <a class='sidebutton-match' href='$opl_match_url{$match['OPL_ID']}' target='_blank'>
                            <div class='material-symbol'>". file_get_contents(__DIR__."/../icons/material/open_in_new.svg") ."</div>
                          </a>
                        </div>";
	} else {
		$t1score = $match['team1Score'];
		$t2score = $match['team2Score'];
		if ($t1score == -1 || $t2score == -1) {
			$t1score = ($t1score == -1) ? "L" : "W";
			$t2score = ($t2score == -1) ? "L" : "W";
		}
		if ($match['winner'] == $match['OPL_ID_team1']) {
			$state1 = "win";
			$state2 = "loss";
		} else if ($match['winner'] == $match['OPL_ID_team2']) {
			$state1 = "loss";
			$state2 = "win";
		} else {
			$state1 = "draw";
			$state2 = "draw";
		}
		$result .= "<div class='match-button-wrapper' data-matchid='$match_id' data-matchtype='$type'>";
		if ($team_id != NULL) {
			$result .= "<a class='button match sideext-right' href='$pageurl' onclick='popup_match(\"{$match['OPL_ID']}\",\"{$team_id}\",\"$type\")'>";
		} else {
			$result .= "<a class='button match sideext-right' href='$pageurl' onclick='popup_match(\"{$match['OPL_ID']}\",null,\"$type\")'>";
		}
		$result .= "<div class='teams score'>
				<div class='team 1 $state1$current1'><div class='name'>{$teams[$match['OPL_ID_team1']]['TeamName']}</div><div class='score'>{$t1score}</div></div>
				<div class='team 2 $state2$current2'><div class='name'>{$teams[$match['OPL_ID_team2']]['TeamName']}</div><div class='score'>{$t2score}</div></div>
			  </div>
			</a>
			<a class='sidebutton-match' href='$opl_match_url{$match['OPL_ID']}' target='_blank'>
				<div class='material-symbol'>". file_get_contents(__DIR__."/../icons/material/open_in_new.svg") ."</div>
			</a>
		</div>";
	}
	return $result;
}

function create_team_nav_buttons($tournamentID,$groupID,$team,$active,$updatediff="unbekannt"):string {
	$result = "";
	$details_a = $matchhistory_a = $stats_a = "";
	if ($active == "details") {
		$details_a = " active";
	} elseif ($active == "matchhistory") {
		$matchhistory_a = " active";
	} elseif ($active == "stats") {
		$stats_a = " active";
	}
	$local_team_img = "img/team_logos/";
	$opl_team_url = "https://www.opleague.pro/team/";
	$team_id = $team['OPL_ID'];
	$result .= "<div class='team title'>
			<div class='team-name'>";
	if ($team['OPL_ID_logo'] != NULL && file_exists(__DIR__."/../$local_team_img{$team['OPL_ID_logo']}/logo.webp")) {
		$result .= "<img alt src='$local_team_img{$team['OPL_ID_logo']}/logo.webp'>";
	}
	$result .= "
			<div>
				<h2>{$team['name']}</h2>
				<a href=\"$opl_team_url$team_id\" class='toorlink' target='_blank'><div class='material-symbol'>". file_get_contents(__DIR__."/../icons/material/open_in_new.svg") ."</div></a>
			</div>
        </div>
        <div class='team-titlebutton-wrapper'>
           	<a href='turnier/$tournamentID/team/$team_id' class='button$details_a'><div class='material-symbol'>". file_get_contents(__DIR__."/../icons/material/info.svg") ."</div>Team-Übersicht</a>
           	<a href='turnier/$tournamentID/team/$team_id/matchhistory' class='button$matchhistory_a'><div class='material-symbol'>". file_get_contents(__DIR__."/../icons/material/manage_search.svg") ."</div>Match-History</a>
            <a href='turnier/$tournamentID/team/$team_id/stats' class='button$stats_a'><div class='material-symbol'>". file_get_contents(__DIR__."/../icons/material/monitoring.svg") ."</div>Statistiken</a>
        </div>";
	if ($active == "details") {
		$result .= "
				<div class='updatebuttonwrapper'>
           			<button type='button' class='icononly user_update_team update_data' data-team='$team_id' data-tournament='$tournamentID' data-group='$groupID'><div class='material-symbol'>".file_get_contents(__DIR__."/../icons/material/sync.svg")."</div></button>
					<span>letztes Update:<br>$updatediff</span>
				</div>";
	}
	$result .= "</div>";

	return $result;
}

function create_dropdown(string $type, array $items):string {
	$first_key = array_key_first($items);
	$result = "<div class='button-dropdown-wrapper'>";
	$result .= "<button type='button' class='button-dropdown' data-dropdowntype='$type'>{$items[$first_key]}<span class='material-symbol'>".file_get_contents(__DIR__."/../icons/material/expand_more.svg")."</span></button>";
	$result .= "<div class='dropdown-selection'>";
	foreach ($items as $data_name=>$name) {
		$selected = ($data_name == $first_key) ? "selected-item" : "";
		$result .= "<button type='button' class='dropdown-selection-item $selected' data-selection='$data_name'>$name</button>";
	}
	$result .= "</div>";
	$result .=  "</div>"; // button-dropdown-wrapper
	return $result;
}

function populate_th($maintext,$tooltiptext,$init=false) {
	if ($init) {
		$svg_code = file_get_contents(__DIR__."/../icons/material/expand_more.svg");
	} else {
		$svg_code = file_get_contents(__DIR__."/../icons/material/check_indeterminate_small.svg");
	}
	return "<span class='tooltip'>$maintext<span class='tooltiptext'>$tooltiptext</span><div class='material-symbol sort-direction'>".$svg_code."</div></span>";
}

function create_game($dbcn,$gameID,$curr_team=NULL):string {
	$result = "";
	// TODO: tournamentID integrieren, falls ein game in mehreren turnieren eingetragen ist (aktuell wird einfach das erste geholt)
	$gameDB = $dbcn->execute_query("SELECT * FROM games JOIN games_in_tournament git on games.RIOT_matchID = git.RIOT_matchID WHERE games.RIOT_matchID = ?",[$gameID])->fetch_assoc();
	$team_blue_ID = $gameDB['OPL_ID_blueTeam'];
	$team_red_ID = $gameDB['OPL_ID_redTeam'];
	$team_blue = $dbcn->execute_query("SELECT * FROM teams WHERE OPL_ID = ?",[$team_blue_ID])->fetch_assoc();
	$team_red = $dbcn->execute_query("SELECT * FROM teams WHERE OPL_ID = ?",[$team_red_ID])->fetch_assoc();
	$players_blue_DB = $dbcn->execute_query("SELECT summonerName, rank_tier, rank_div, PUUID FROM players JOIN players_in_teams pit on players.OPL_ID = pit.OPL_ID_player WHERE OPL_ID_team = ?",[$team_blue['OPL_ID']])->fetch_all(MYSQLI_ASSOC);
	$players_red_DB = $dbcn->execute_query("SELECT summonerName, rank_tier, rank_div, PUUID FROM players JOIN players_in_teams pit on players.OPL_ID = pit.OPL_ID_player WHERE OPL_ID_team = ?",[$team_red['OPL_ID']])->fetch_all(MYSQLI_ASSOC);

	$tournamentID = $gameDB["OPL_ID_tournament"];

	$players_PUUID = [];
	$players = [];
	for ($i = 0; $i < count($players_blue_DB); $i++)  {
		$players[$players_blue_DB[$i]["summonerName"]] = $players_blue_DB[$i];
		$players_PUUID[$players_blue_DB[$i]['PUUID']] = $players_blue_DB[$i];
	}
	for ($i = 0; $i < count($players_red_DB); $i++)  {
		$players[$players_red_DB[$i]["summonerName"]] = $players_red_DB[$i];
		$players_PUUID[$players_red_DB[$i]['PUUID']] = $players_red_DB[$i];
	}

	if ($curr_team == $team_blue_ID) {
		$blue_curr = "current";
		$red_curr = "";
	} elseif ($curr_team == $team_red_ID) {
		$blue_curr = "";
		$red_curr = "current";
	} else {
		$blue_curr = "";
		$red_curr = "";
	}
	if ($gameDB['winningTeam'] == $team_blue_ID) {
		$score_blue = "Victory";
		$score_red = "Defeat";
		$score_blue_class = " win";
		$score_red_class = " loss";
	} else {
		$score_blue = "Defeat";
		$score_red = "Victory";
		$score_blue_class = " loss";
		$score_red_class = " win";
	}

	//$obj_icon_url = "https://raw.communitydragon.org/12.1/plugins/rcp-fe-lol-match-history/global/default/";
	$obj_icon_url = "ddragon/img/";
	$kills_icon = $obj_icon_url."kills.png";
	$obj_icons = $obj_icon_url."right_icons.png";
	$gold_icons = $obj_icon_url."icon_gold.png";
	$cs_icons = $obj_icon_url."icon_minions.png";

	$data = json_decode($gameDB['matchdata'],true);
	$participants_PUUIDs = $data['metadata']['participants'];
	$info = $data['info'];
	$participants = $info['participants'];
	for ($team_index = 0; $team_index <= 1; $team_index++) {
		for ($player_index = $team_index*5; $player_index < $team_index*5+5; $player_index++) {
			$roles = ["TOP","JUNGLE","MIDDLE","BOTTOM","UTILITY"];
			$roles_check = array("TOP"=>0,"JUNGLE"=>1,"MIDDLE"=>2,"BOTTOM"=>3,"UTILITY"=>4);
			$role = $participants[$player_index]['teamPosition'];
			if ($role != $roles[$player_index-($team_index*5)]) {
				$player_2_index = $roles_check[$role] + $team_index*5;
				$helper = $participants[$player_index];
				$participants[$player_index] = $participants[$player_2_index];
				$participants[$player_2_index] = $helper;
			}
		}
	}
	$teams = $info['teams'];

	$towers_blue = $teams[0]['objectives']['tower']['kills'];
	$towers_red = $teams[1]['objectives']['tower']['kills'];
	$inhibs_blue = $teams[0]['objectives']['inhibitor']['kills'];
	$inhibs_red = $teams[1]['objectives']['inhibitor']['kills'];
	$heralds_blue = $teams[0]['objectives']['riftHerald']['kills'];
	$heralds_red = $teams[1]['objectives']['riftHerald']['kills'];
	$dragons_blue = $teams[0]['objectives']['dragon']['kills'];
	$dragons_red = $teams[1]['objectives']['dragon']['kills'];
	$barons_blue = $teams[0]['objectives']['baron']['kills'];
	$barons_red = $teams[1]['objectives']['baron']['kills'];

	$bans_blue = $teams[0]['bans'];
	$bans_red = $teams[1]['bans'];

	$gold_blue = 0;
	$kills_blue = 0;
	$deaths_blue = 0;
	$assists_blue = 0;
	$gold_red = 0;
	$kills_red = 0;
	$deaths_red = 0;
	$assists_red = 0;
	for ($t = 0; $t < 2; $t++) {
		for ($p = 0; $p < 5; $p++) {
			$curr_player = $participants[$t*5+$p];
			if ($t == 0) {
				$gold_blue += $curr_player['goldEarned'];
				$kills_blue += $curr_player['kills'];
				$deaths_blue += $curr_player['deaths'];
				$assists_blue += $curr_player['assists'];
			} else {
				$gold_red += $curr_player['goldEarned'];
				$kills_red += $curr_player['kills'];
				$deaths_red += $curr_player['deaths'];
				$assists_red += $curr_player['assists'];
			}
		}
	}
	$gold_blue_1 = floor($gold_blue / 1000);
	$gold_blue_2 = floor($gold_blue % 1000 / 100);
	$gold_red_1 = floor($gold_red / 1000);
	$gold_red_2 = floor($gold_red % 1000 / 100);

	$patch = NULL;
	$patches = [];
	$dir = new DirectoryIterator(dirname(__FILE__) . "/../ddragon");
	foreach ($dir as $fileinfo) {
		if (!$fileinfo->isDot() && $fileinfo->getFilename() != "img" && $fileinfo->isDir()) {
			$patches[] = $fileinfo->getFilename();
		}
	}
	usort($patches, "version_compare");
	$game_patch_1 = explode(".",$info['gameVersion'])[0];
	$game_patch_2 = explode(".",$info['gameVersion'])[1];
	foreach ($patches as $patch_from_arr) {
		$patch_from_arr_1 = explode(".",$patch_from_arr)[0];
		$patch_from_arr_2 = explode(".",$patch_from_arr)[1];
		// durchlaufe Patchnummern der lokalen Patchdaten von alt nach neu
		// ist der Patch des Spiels älter als dieser Patch, oder genau dieser, setze $patch auf diesen Patch
		if ($game_patch_1 < $patch_from_arr_1 || ($game_patch_1 == $patch_from_arr_1 && $game_patch_2 <= $patch_from_arr_2)) {
			$patch = $patch_from_arr;
			break;
		}
	}
	// wurde $patch noch nicht gesetzt, muss der Patch des Spiels neuer sein, setze $patch auf den neuesten lokalen Patch
	if ($patch === NULL) {
		$patch = end($patches);
	}

	$dd_img = "ddragon/$patch/img";
	$dd_data = dirname(__FILE__)."/../ddragon/$patch/data";

	//$champion_dd = file_get_contents("https://ddragon.leagueoflegends.com/cdn/$patch/data/en_US/champion.json");
	$champion_dd = file_get_contents("$dd_data/champion.json");
	$champion_dd = json_decode($champion_dd,true);
	$champion_data = $champion_dd['data'];
	$champions_by_key = [];
	foreach ($champion_data as $champ) {
		$champions_by_key[$champ['key']] = $champ['id'];
	}

	//$runes_dd = json_decode(file_get_contents("https://ddragon.leagueoflegends.com/cdn/$patch/data/en_US/runesReforged.json"),true);
	$runes_dd = json_decode(file_get_contents("$dd_data/runesReforged.json"),true);
	$runes = [];
	for ($r = 0; $r < count($runes_dd); $r++) {
		$keystones = $runes_dd[$r]['slots'][0]['runes'];
		$keystones_new = [];
		for ($k = 0; $k < count($keystones); $k++) {
			$keystones_new[$keystones[$k]['id']] = $keystones[$k];
		}
		$runes[$runes_dd[$r]["id"]] = $runes_dd[$r];
		$runes[$runes_dd[$r]["id"]]["slots"][0]["runes"] = $keystones_new;
	}

	//$summs_cd = json_decode(file_get_contents("https://raw.communitydragon.org/latest/plugins/rcp-be-lol-game-data/global/default/v1/summoner-spells.json"),true);

	//$summs_dd = json_decode(file_get_contents("https://ddragon.leagueoflegends.com/cdn/$patch/data/en_US/summoner.json"),true);
	$summs_dd = json_decode(file_get_contents("$dd_data/summoner.json"),true);
	$summs = array_column($summs_dd['data'],"id","key");

	$game_duration = $info['gameDuration'];
	$game_duration_min = floor($game_duration / 60);
	$game_duration_sec = $game_duration % 60;
	if ($game_duration_sec < 10) {
		$game_duration_sec = "0".$game_duration_sec;
	}

	$local_team_img = "img/team_logos/";
	if ($team_blue['OPL_ID_logo'] == NULL || !file_exists(__DIR__."/../$local_team_img{$team_blue['OPL_ID_logo']}/logo.webp")) {
		$logo_blue = "";
	} else {
		$logo_blue = "<img alt='' src='$local_team_img{$team_blue['OPL_ID_logo']}/logo.webp'>";
	}
	if ($team_red['OPL_ID_logo'] == NULL || !file_exists(__DIR__."/../$local_team_img{$team_red['OPL_ID_logo']}/logo.webp")) {
		$logo_red = "";
	} else {
		$logo_red = "<img alt='' src='$local_team_img{$team_red['OPL_ID_logo']}/logo.webp'>";
	}

	$gamedate = date("d.m.y",$info["gameCreation"]/1000);
	$log_link = "https://www.leagueofgraphs.com/match/euw/".explode("_",$gameID)[1];

	$result .= "
    <div class='game-details'>
        <div class='game-row teams'>
            <a class='team 1 $blue_curr$score_blue_class' href='./turnier/$tournamentID/team/$team_blue_ID'>
                <div class='name'>$logo_blue{$team_blue['name']}</div>
                <div class='score$score_blue_class'>$score_blue</div>
            </a>
            <div class='time'>
                <div>$game_duration_min:$game_duration_sec</div>
            </div>
            <a class='team 2 $red_curr$score_red_class' href='./turnier/$tournamentID/team/$team_red_ID'>
                <div class='score$score_red_class'>$score_red</div>
                <div class='name'>{$team_red['name']}$logo_red</div>
            </a>
        </div>
        <div class='game-row team-stats'>
            <div class='stats-wrapper'>
                <span><img src='$kills_icon' class='stats kills' alt=''>$kills_blue / $deaths_blue / $assists_blue</span>
                <span><img src='$gold_icons' class='stats gold' alt=''>{$gold_blue_1}.{$gold_blue_2}k</span>
            </div>
            <div class='game-row-divider'></div>
            <div class='stats-wrapper'>
                <span><img src='$kills_icon' class='stats kills' alt=''>$kills_red / $deaths_red / $assists_red</span>
                <span><img src='$gold_icons' class='stats gold' alt=''>{$gold_red_1}.{$gold_red_2}k</span>
            </div>
        </div>
        <div class='game-row objectives'>
            <div class='obj-wrapper'>
                <span><img src='$obj_icons' class='obj obj-tower' alt=''>$towers_blue</span>
                <span><img src='$obj_icons' class='obj obj-inhib' alt=''>$inhibs_blue</span>
                <span><img src='$obj_icons' class='obj obj-herald' alt=''>$heralds_blue</span>
                <span><img src='$obj_icons' class='obj obj-dragon' alt=''>$dragons_blue</span>
                <span><img src='$obj_icons' class='obj obj-baron' alt=''>$barons_blue</span>
            </div>
            <div class='game-row-divider'></div>
            <div class='obj-wrapper'>
                <span><img src='$obj_icons' class='obj obj-tower' alt=''>$towers_red</span>
                <span><img src='$obj_icons' class='obj obj-inhib' alt=''>$inhibs_red</span>
                <span><img src='$obj_icons' class='obj obj-herald' alt=''>$heralds_red</span>
                <span><img src='$obj_icons' class='obj obj-dragon' alt=''>$dragons_red</span>
                <span><img src='$obj_icons' class='obj obj-baron' alt=''>$barons_red</span>
            </div>
        </div>";
	for ($i = 0; $i < 5; $i++) {
		$result .= "
        <div class='game-row summoners'>";
		for ($p = 0; $p < 2; $p++) {
			if ($p == 0) {
				$team_side = "blue";
			} else {
				$team_side = "red";
			}
			$player = $participants[$i+($p*5)];

			$runepage_pri = $player['perks']['styles'][0]['style'];
			$keystone = $player['perks']['styles'][0]['selections'][0]['perk'];
			$runepage_sec = $player['perks']['styles'][1]['style'];
			$keystone_img = $runes[$runepage_pri]['slots'][0]['runes'][$keystone]['icon'];
			$sec_rune_img = $runes[$runepage_sec]['icon'];
			$keystone_img = explode(".",$keystone_img)[0].".webp";
			$sec_rune_img = explode(".",$sec_rune_img)[0].".webp";

			$summ1_img = $summs[$player['summoner1Id']];
			$summ2_img = $summs[$player['summoner2Id']];

			$championId = $player['championName'];
			$champ_lvl = $player['champLevel'];

			$summoner_rank = "";
			$summoner_rank_div = "";
			$summoner_name = $player['summonerName'];
			$puuid = $player['puuid'];
			if (array_key_exists($puuid, $players_PUUID)) {
				$summoner_rank = strtolower($players_PUUID[$puuid]['rank_tier']);
				if ($summoner_rank != "master" && $summoner_rank != "grandmaster" && $summoner_rank != "challenger") {
					$summoner_rank_div = $players_PUUID[$puuid]['rank_div'];
				}
			}
			$summoner_rank_cap = ucfirst($summoner_rank);

			$kills = $player['kills'];
			$deaths = $player['deaths'];
			$assists = $player['assists'];
			$cs = $player['totalMinionsKilled'];
			$gold = $player['goldEarned'];
			$gold_1 = floor($gold / 1000);
			$gold_2 = floor($gold % 1000 / 100);

			if ($team_side == "blue") {
				$item0 = ($player['item0'] == 0)? 7050 : $player['item0'];
				$item2 = ($player['item2'] == 0)? 7050 : $player['item2'];
				$item3 = ($player['item3'] == 0)? 7050 : $player['item3'];
				$item5 = ($player['item5'] == 0)? 7050 : $player['item5'];
			} else {
				$item0 = ($player['item2'] == 0)? 7050 : $player['item2'];
				$item2 = ($player['item0'] == 0)? 7050 : $player['item0'];
				$item3 = ($player['item5'] == 0)? 7050 : $player['item5'];
				$item5 = ($player['item3'] == 0)? 7050 : $player['item3'];
			}
			$item1 = ($player['item1'] == 0)? 7050 : $player['item1'];
			$item4 = ($player['item4'] == 0)? 7050 : $player['item4'];
			$item6 = ($player['item6'] == 0)? 7050 : $player['item6'];

			$result .= "
            <div class='game-item summoner $team_side'>
                <div class='runes'>
                    <img loading='lazy' alt='' src='$dd_img/$keystone_img' class='keystone'>
                    <img loading='lazy' alt='' src='$dd_img/$sec_rune_img' class='sec-rune'>
                </div>
                <div class='summoner-spells'>
                    <img loading='lazy' alt='' src='$dd_img/spell/$summ1_img.webp' class='summ-spell'>
                    <img loading='lazy' alt='' src='$dd_img/spell/$summ2_img.webp' class='summ-spell'>
                </div>
                <div class='champion'>
                    <img loading='lazy' alt='' src='$dd_img/champion/{$championId}.webp' class='champ'>
                    <div class='champ-lvl'>$champ_lvl</div>
                </div>
                <div class='summoner-name'>
                    <div>$summoner_name</div>";
			if (array_key_exists($puuid, $players_PUUID)) {
				if ($summoner_rank != NULL) {
					$result .= "
                    <div class='summ-rank'><img loading='lazy' class='rank-emblem-mini' src='./ddragon/img/ranks/mini-crests/{$summoner_rank}.svg' alt=''> $summoner_rank_cap $summoner_rank_div</div>";
				}
			}
			$result .= "
                </div>
                <div class='player-stats'>
                    <div class='player-stats-wrapper'>
                        <span class='kills'><img loading='lazy' src='$kills_icon' class='stats kills' alt=''>$kills / $deaths / $assists</span>
                        <span class='CS'><img loading='lazy' src='$cs_icons' class='stats cs' alt=''>$cs</span>
                        <span class='gold'><img loading='lazy' src='$gold_icons' class='stats gold' alt=''>{$gold_1}.{$gold_2}k Gold</span>
                    </div>
                </div>
                <div class='items'>
                    <div class='items-wrapper'>
                        <img loading='lazy' src='$dd_img/item/{$item0}.webp' alt=''>
                        <img loading='lazy' src='$dd_img/item/{$item1}.webp' alt=''>
                        <img loading='lazy' src='$dd_img/item/{$item2}.webp' alt=''>
                        <img loading='lazy' src='$dd_img/item/{$item3}.webp' alt=''>
                        <img loading='lazy' src='$dd_img/item/{$item4}.webp' alt=''>
                        <img loading='lazy' src='$dd_img/item/{$item5}.webp' alt=''>
                        <img loading='lazy' src='$dd_img/item/{$item6}.webp' alt=''>
                    </div>
                </div>
            </div>";
			if ($p == 0) {
				$result .= "<div class='game-row-divider'></div>";
			}
		}
		$result .= "
        </div>";
	}
	$result .= "
        <div class='game-row bans'>
            <div class='bans-wrapper'>";
	foreach ($bans_blue as $ban) {
		$result .= "
                <span>
                    <img loading='lazy' src='$dd_img/champion/{$champions_by_key[$ban['championId']]}.webp' alt=''>
                    <i class='gg-block'></i>
                </span>";
	}
	$result .= "
            </div>
            <div class='game-row-divider'></div>
            <div class='bans-wrapper'>";
	foreach ($bans_red as $ban) {
		$result .= "
                <span>
                    <img loading='lazy' src='$dd_img/champion/{$champions_by_key[$ban['championId']]}.webp' alt=''>
                    <i class='gg-block'></i>
                </span>";
	}
	$result .= "
            </div>
        </div>
    </div>
    ";

	return $result;
}

function show_old_url_warning($tournamentID):string {
	$url = $_SERVER["REQUEST_URI"];
	$new_url = "/toornament$url";
	if (strlen($tournamentID) > 15) {
		return "
			<div class='warning-header'>
				<span>Der aufgerufene Link sieht nach einer ID von Toornament aus, seit dem Umzug der Uniliga auf OPLeague ist diese Seite hier zu finden: <a href='$new_url'>silence.lol$new_url</a></span>
				<button onclick='close_warningheader()'><div class='material-symbol'>".file_get_contents(__DIR__."/../icons/material/close.svg")."</div></button>
			</div>
		";
	}
	return "";
}