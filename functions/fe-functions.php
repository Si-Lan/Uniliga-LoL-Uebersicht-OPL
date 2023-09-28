<?php
include_once(dirname(__FILE__)."/helper.php");

function create_html_head_elements(array $css = [], array $js = [], string $title="Uniliga LoL - Übersicht", bool $head_wrap = TRUE, bool $loggedin=FALSE):string {
	$result = "";
	if ($head_wrap) {
		$result .= "<head>";
	}

	// basic content
	$result .= "<base href='/opl/uniliga/'>";
	$result .= "<meta name='viewport' content='width=device-width, initial-scale=1'>";
	$result .= "<link rel='icon' href='https://silence.lol/favicon-dark.ico' media='(prefers-color-scheme: dark)'/>";
	$result .= "<link rel='icon' href='https://silence.lol/favicon-light.ico' media='(prefers-color-scheme: light)'/>";
	$result .= "<link rel='stylesheet' href='styles/main.css'>";
	$result .= "<script src='scripts/jquery-3.7.1.min.js'></script>";
	$result .= "<script src='scripts/main.js'></script>";
	// additional css
	if (in_array("elo",$css)) {
		$result .= "<link rel='stylesheet' href='elo-rank-colors.css'>";
	}
	if (in_array("game",$css)) {
		$result .= "<link rel='stylesheet' href='game.css'>";
	}
	if (in_array("admin",$css)) {
		$result .= "<link rel='stylesheet' href='admin/styles/style.css'>";
	}
	// additional js
	if (in_array("rgapi", $js) && $loggedin) {
		$result .= "<script src='admin/riot-api-access/rgapi.js'></script>";
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
		$result .= "<h1>Uniliga LoL- Admin</h1>";
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
				<a class='settings-option rgapi-write' href='./admin/riot-api-access'>RGAPI<div class='material-symbol'>". file_get_contents(dirname(__FILE__)."/../icons/material/videogame_asset.svg") ."</div></a>
				<a class='settings-option ddragon-write' href='./admin/ddragon-updates'>DDragon</a>
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
			$group_title = "Gruppe {$group['Number']}";
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

//
function create_standings(mysqli $dbcn, $tournament_id, $group_id, $team_id=NULL):string {
	$result = "";
	$opgg_url = "https://www.op.gg/multisearch/euw?summoners=";
	$local_img_path = "img/team_logos/";
	$opgg_logo_svg = file_get_contents(__DIR__."/../img/opgglogo.svg");
	$group = $dbcn->execute_query("SELECT * FROM tournaments WHERE eventType = 'group' AND OPL_ID = ?",[$group_id])->fetch_assoc();
	$div = $dbcn->execute_query("SELECT * FROM tournaments WHERE eventType = 'league' AND OPL_ID = ?",[$group['OPL_ID']])->fetch_assoc();
	$teams_from_groupDB = $dbcn->execute_query("SELECT * FROM teams JOIN teams_in_tournaments tit ON teams.OPL_ID = tit.OPL_ID_team  WHERE tit.OPL_ID_tournament = ? ORDER BY CASE WHEN standing=0 THEN 1 else 0 end, standing",[$group['OPL_ID']])->fetch_all(MYSQLI_ASSOC);

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
				<a href='team/{$currteam['OPL_ID']}' class='standing-item-wrapper'>
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