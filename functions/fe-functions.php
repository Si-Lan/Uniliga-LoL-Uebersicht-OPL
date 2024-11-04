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
	$result .= "<link rel='stylesheet' href='styles/design2.css?4'>";
	$result .= "<script src='scripts/jquery-3.7.1.min.js'></script>";
	$result .= "<script src='scripts/main.js?4'></script>";
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

	// meta tags
	$page_title_trimmed = preg_replace("#\s\| Uniliga LoL - Übersicht$#","", $title);
	$result .= "<meta property='og:site_name' content='Silence.lol | Uniliga LoL Übersicht'>";
	$result .= "<meta property='og:title' content='$page_title_trimmed'>";
	$result .= "<meta property='og:description' content='Turnierübersicht, Matchhistory und Statistiken zu Teams und Spielern für die League of Legends Uniliga'>";
	$result .= "<meta property='og:image' content='https://silence.lol/storage/img/silence_s_logo_bg_250.png'>";
	$result .= "<meta name='theme-color' content='#e7e7e7'>";

	if ($head_wrap) {
		$result .= "</head>";
	}
	return $result;
}

function check_login():bool {
	include_once(dirname(__FILE__) . "/../setup/data.php");
	$password = get_admin_pass();
	if (isset($_GET["login"])) {
		if (isset($_POST["keypass"])) {
			if ($_POST["keypass"] != $password) {
				return false;
			} else {
				setcookie('admin-login', password_hash($password, PASSWORD_BCRYPT),time()+31536000,'/');
				$pageurl = preg_replace('~([?&])login(=*)[^&]*~', '$1', $_SERVER['REQUEST_URI']);
				$pageurl = rtrim($pageurl, "?");
				header("Location: $pageurl");
			}
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

	return true;
}

function create_header(mysqli $dbcn = NULL, string $title = "home", string|int $tournament_id = NULL, bool $home_button = TRUE, bool $search_button = TRUE, bool $open_login = FALSE):string {
	include_once __DIR__."/../setup/data.php";
	$loginforminfo = "";
	$password = get_admin_pass();
	if (isset($_GET["login"]) && isset($_POST["keypass"]) && $_POST["keypass"] != $password) {
		$open_login = true;
		$loginforminfo = "Falsches Passwort";
	}

	$loginopen = $open_login ? "modalopen_auto" : "";

	$result = "";


	$pageurl = $_SERVER['REQUEST_URI'];

	$opl_event_url = "https://www.opleague.pro/event";
	$outlinkicon = file_get_contents(dirname(__FILE__)."/../icons/material/open_in_new.svg");
	if ($dbcn != NULL && $tournament_id != NULL) {
		$tournament = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID = ?",[$tournament_id])->fetch_assoc();
		$t_name_clean = preg_replace("/LoL\s/","",$tournament["name"]);
	}

	$loggedin = is_logged_in();
	$colormode = is_light_mode() ? "light" : "dark";

	if (file_exists(__DIR__."/../setup/maintenance.enable") && $loggedin) $result .= "<div style='text-align: center; padding: 5px 0; background-color: #7e1616'>Achtung: Wartungsmodus ist aktiviert!</div>";

	$result .= "<header class='$title'>";
	if ($home_button) {
		$result .= "
	<a href='.' class='button material-symbol'>
		".file_get_contents(dirname(__FILE__)."/../icons/material/home.svg")."
	</a>";
	}
	$searchbar = "";
	if ($search_button) {
		$searchbar = "
		<div class='searchbar'>
			<span class='material-symbol search-icon' title='Suche'>
				".file_get_contents(dirname(__FILE__)."/../icons/material/search.svg")."
			</span>
			<input class='search-all deletable-search' placeholder='Suche' type='search'>
			<button class='material-symbol search-clear' title='Suche leeren'>
				".file_get_contents(dirname(__FILE__)."/../icons/material/close.svg")."
			</button>
		</div>";
	}
	$title_before_search = false;
	$title_text = "";
	switch ($title) {
		case "players":
			$title_text .= "Uniliga LoL - Spieler";
			break;
		case "tournament":
			if ($dbcn != NULL && $tournament_id != NULL) {
				$title_text .= "$t_name_clean";
				$title_text .= "<a href='$opl_event_url/$tournament_id' target='_blank' class='opl-link'><div class='material-symbol'>$outlinkicon</div></a>";
			} else {
				$title_text = "Uniliga LoL - Übersicht";
			}
			break;
		case "admin_dd":
			$title_text .= "Uniliga LoL - DDragon Updates";
			break;
		case "admin_update_log":
			$title_text .= "Uniliga LoL - Update Logs";
			break;
		case "admin":
			$title_text .= "Uniliga LoL - Admin";
			break;
		case "rgapi":
			$title_text .= "Uniliga LoL - Riot-API-Daten";
			break;
		case "maintenance":
			$title_text .= "Uniliga LoL - Übersicht - Wartung";
			break;
		case "404":
			$title_text .= "404 - Seite nicht gefunden";
			break;
		case "error":
			$title_text .= "Fehler";
			break;
		case "home":
			$title_text = "Uniliga LoL - Übersicht";
			$title_before_search = true;
			break;
		default:
			$title_text .= "Uniliga LoL - Übersicht";
	}

	if ($title_before_search) {
		$title_text = "<h1>".$title_text."</h1>";
		$result .= $title_text.$searchbar;
	} else {
		$title_text = "<h1 class='tournament-title'>".$title_text."</h1>";
		$result .= $searchbar.$title_text;
	}

	$result .= "<button type='button' class='material-symbol settings-button'>". file_get_contents(dirname(__FILE__)."/../icons/material/tune.svg") ."</button>";
	if ($loggedin) {
		$result .= "
			<div class='settings-menu'>
				<a class='settings-option toggle-mode' href='$pageurl'><div class='material-symbol'>". file_get_contents(dirname(__FILE__)."/../icons/material/{$colormode}_mode.svg") ."</div></a>
				<a class='settings-option toor-write' href='./admin'>Admin<div class='material-symbol'>". file_get_contents(dirname(__FILE__)."/../icons/material/edit_square.svg") ."</div></a>
				<a class='settings-option rgapi-write' href='./admin/rgapi'>RGAPI<div class='material-symbol'>". file_get_contents(dirname(__FILE__)."/../icons/material/videogame_asset.svg") ."</div></a>
				<a class='settings-option ddragon-write' href='./admin/ddragon'>DDragon<div class='material-symbol'>". file_get_contents(dirname(__FILE__)."/../icons/material/photo_library.svg") ."</div></a>
				<a class='settings-option update-log' href='./admin/updates'>Update-Logs</a>
				<a class='settings-option logout' href='$pageurl?logout'>Logout<div class='material-symbol'>". file_get_contents(dirname(__FILE__)."/../icons/material/logout.svg") ."</div></a>
			</div>";
	} else {
		$result .= "
			<div class='settings-menu'>
				<a class='settings-option toggle-mode' href='$pageurl'><div class='material-symbol'>". file_get_contents(dirname(__FILE__)."/../icons/material/{$colormode}_mode.svg") ."</div></a>
				<a class='settings-option github-link' href='https://github.com/Si-Lan/Uniliga-LoL-Uebersicht-OPL' target='_blank'>GitHub<div class='material-symbol'>". file_get_contents(dirname(__FILE__)."/../img/github-mark-white.svg") ."</div></a>
				<a class='settings-option' href='https://ko-fi.com/silencelol' target='_blank'>Spenden<div class='material-symbol'>". file_get_contents(dirname(__FILE__)."/../icons/material/payments.svg") ."</div></a>
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

function create_tournament_nav_buttons(string|int $tournament_id, mysqli $dbcn, $active="",$division_id=NULL,$group_id=NULL):string {
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
		<nav class='turnier-bonus-buttons'>
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

	/*
	if ($group_id != NULL && $active != "group") {
		$group = $dbcn->execute_query("SELECT * FROM tournaments WHERE (eventType = 'group' OR (eventType = 'league' AND format = 'swiss') OR eventType = 'wildcard') AND OPL_ID = ?",[$group_id])->fetch_assoc();
		$div = $dbcn->execute_query("SELECT * FROM tournaments WHERE eventType = 'league' AND OPL_ID = ?",[$group['OPL_ID_parent']])->fetch_assoc();
		$group_url_segment = "gruppe";
		if ($group["eventType"] === "wildcard") {
			$wildcard_numbers_combined = ($group["numberRangeTo"] == null) ? $group["number"] : $group["number"]."-".$group["numberRangeTo"];
			$group_title = "Wildcard-Turnier Liga ".$wildcard_numbers_combined;
			$group_url_segment = "wildcard";
		} elseif ($group["format"] === "swiss") {
			$group_title = "Swiss-Gruppe";
			$div = $group;
		} else {
			$group_title = "Gruppe {$group['number']}";
		}
		$result .= "
			<div class='divider-vert'></div>
			<a href='turnier/{$tournament_id}/$group_url_segment/$group_id' class='button$group_a'>
                <div class='material-symbol'>". file_get_contents(__DIR__."/../icons/material/table_rows.svg") ."</div>";
		if ($group["eventType"] == "wildcard") {
			$result .= $group_title;
		} else {
			$result .= "Liga ".$div['number']." - $group_title";
		}
		$result .= "</a>";
	}
	*/

	$tournament = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID = ?", [$tournament_id])->fetch_assoc();
	$ranked_season = $tournament["ranked_season"];
	$ranked_split = $tournament["ranked_split"];
	$ranked_season_comb = "$ranked_season-$ranked_split";
	$next_split = get_second_ranked_split_for_tournament($dbcn,$tournament_id);
	$ranked_season_2 = $next_split["season"] ?? null;
	$ranked_split_2 = $next_split["split"] ?? null;
	$ranked_season_comb_2 = "$ranked_season_2-$ranked_split_2";

	$current_split = get_current_ranked_split($dbcn, $tournament_id);

	$button1_checked = ($current_split == $ranked_season_comb) ? "checked" : "";
	$button2_checked = ($current_split == $ranked_season_comb_2) ? "checked" : "";

	$result .= "<div class='ranked-settings-wrapper'>";
	$result .= "<button type='button' class='ranked-settings'><span>$current_split</span><img src='ddragon/img/ranks/emblems/unranked.webp' alt='Rank-Einstellungen'></button>";
	$result .= "<div class='ranked-settings-popover'>
					<span>Angezeigter Rang</span>
					<div>
						<input type='radio' id='ranked-split-radio-1' value='$ranked_season-$ranked_split' name='ranked-split' data-tournament='$tournament_id' $button1_checked>
						<label for='ranked-split-radio-1'>Season $ranked_season Split $ranked_split</label>
					</div>";
	if ($next_split != null) $result .= "
					<div>
						<input type='radio' id='ranked-split-radio-2' value='$ranked_season_2-$ranked_split_2' name='ranked-split' data-tournament='$tournament_id' $button2_checked>
						<label for='ranked-split-radio-2'>Season $ranked_season_2 Split $ranked_split_2</label>
					</div>";
	$result .= "</div>";
	$result .= "</div>";

	$result .= "</nav>";

	return $result;
}

function generate_elo_list(mysqli $dbcn,$view,$tournamentID,$divisionID=null,$groupID=null,$second_ranked_split=false):string {
	$division = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID = ?", [$divisionID])->fetch_assoc();
	$group = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID = ?", [$groupID])->fetch_assoc();
	$results = "";
	$local_team_img = "img/team_logos/";
    $logo_filename = is_light_mode() ? "logo_light.webp" : "logo.webp";
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
		$teams = $dbcn->execute_query("SELECT t.OPL_ID, t.name, t.OPL_ID_logo, ttr.avg_rank_div, ttr.avg_rank_tier, ttr.avg_rank_num, tit.OPL_ID_group
												FROM teams t
												    JOIN teams_in_tournaments tit ON t.OPL_ID = tit.OPL_ID_team
												LEFT JOIN teams_tournament_rank as ttr ON ttr.OPL_ID_team = t.OPL_ID AND ttr.OPL_ID_tournament = ? AND second_ranked_split = ?
												WHERE tit.OPL_ID_group IN (SELECT OPL_ID FROM tournaments WHERE (eventType='group' OR (eventType='league' AND format='swiss')) AND OPL_ID_top_parent = ?) AND t.OPL_ID > -1
												ORDER BY ttr.avg_rank_num DESC", [$tournamentID,$second_ranked_split,$tournamentID])->fetch_all(MYSQLI_ASSOC);
	} elseif ($view == "div") {
		$results .= "
                    <h3 class='liga{$division['number']}'>Liga {$division['number']}</h3>";
		$teams = $dbcn->execute_query("SELECT *
												FROM teams t
													JOIN teams_in_tournaments tit ON t.OPL_ID = tit.OPL_ID_team
												LEFT JOIN teams_tournament_rank as ttr ON ttr.OPL_ID_team = t.OPL_ID AND ttr.OPL_ID_tournament = ? AND second_ranked_split = ?
												WHERE (tit.OPL_ID_group IN (SELECT OPL_ID FROM tournaments WHERE OPL_ID_parent = ?) OR tit.OPL_ID_group = ?) AND t.OPL_ID > -1
												ORDER BY ttr.avg_rank_num DESC", [$tournamentID,$second_ranked_split,$division["OPL_ID"],$division["OPL_ID"]])->fetch_all(MYSQLI_ASSOC);
	} elseif ($view == "group") {
		if ($division["format"] === "swiss") {
			$results .= "
                    <h3 class='liga{$division['number']}'>Liga {$division['number']} - Swiss-Gruppe</h3>";
		} else {
			$results .= "
                    <h3 class='liga{$division['number']}'>Liga {$division['number']} - Gruppe {$group['number']}</h3>";
		}
		$teams = $dbcn->execute_query("SELECT *
												FROM teams t
													JOIN teams_in_tournaments tit ON t.OPL_ID = tit.OPL_ID_team
												LEFT JOIN teams_tournament_rank as ttr ON ttr.OPL_ID_team = t.OPL_ID AND ttr.OPL_ID_tournament = ? AND second_ranked_split = ?
												WHERE tit.OPL_ID_group = ? AND t.OPL_ID > -1
												ORDER BY ttr.avg_rank_num DESC", [$tournamentID,$second_ranked_split,$group["OPL_ID"]])->fetch_all(MYSQLI_ASSOC);
	} elseif ($view == "all-wildcard") {
        $results .= "
                    <h3>Wildcard-Turniere</h3>";
        $teams = $dbcn->execute_query("SELECT *
                                                FROM teams t
                                                    JOIN teams_in_tournaments tit ON t.OPL_ID = tit.OPL_ID_team
                                                LEFT JOIN teams_tournament_rank as ttr ON ttr.OPL_ID_team = t.OPL_ID AND ttr.OPL_ID_tournament = ? AND second_ranked_split = ?
                                                WHERE tit.OPL_ID_group IN (SELECT OPL_ID FROM tournaments WHERE OPL_ID_top_parent = ? AND eventType = 'wildcard') AND t.OPL_ID > -1
                                                ORDER BY ttr.avg_rank_num DESC", [$tournamentID,$second_ranked_split,$tournamentID])->fetch_all(MYSQLI_ASSOC);
    } elseif ($view == "wildcard") {
        $comb_wc_num = ($division["numberRangeTo"] == null) ? $division["number"]."  " : $division["number"]."-".$division["numberRangeTo"];
        $results .= "
                    <h3>Wildcard-Turnier Liga $comb_wc_num</h3>";
        $teams = $dbcn->execute_query("SELECT *
                                                FROM teams t
                                                    JOIN teams_in_tournaments tit ON t.OPL_ID = tit.OPL_ID_team
                                                LEFT JOIN teams_tournament_rank as ttr ON ttr.OPL_ID_team = t.OPL_ID AND ttr.OPL_ID_tournament = ? AND second_ranked_split = ?
                                                WHERE tit.OPL_ID_group = ? AND t.OPL_ID > -1
                                                ORDER BY ttr.avg_rank_num DESC", [$tournamentID,$second_ranked_split,$divisionID])->fetch_all(MYSQLI_ASSOC);
    }
	$results .= "
                    <div class='elo-list-row elo-list-header'>
                        <div class='elo-list-pre-header league'>Liga #</div>
                        <div class='elo-list-item-wrapper-header'>
	                        <div class='elo-list-item team'>Team</div>
    	                    <div class='elo-list-item rank'>avg. Rang</div>
    	                    <div class='elo-list-item elo-nr'>Elo</div>
                        </div>
                    </div>";
	$tournament = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID = ?", [$tournamentID])->fetch_assoc();
	foreach ($teams as $i=>$team) {
		if ($i != 0) $results .= "<div class='divider-light'></div>";
        if (str_contains($view, "wildcard")) {
            $league = $dbcn->execute_query("SELECT * FROM tournaments WHERE eventType='wildcard' AND OPL_ID = ?", [$team["OPL_ID_group"]])->fetch_assoc();
        } else {
            $league = $dbcn->execute_query("SELECT * FROM tournaments WHERE eventType = 'league' AND (OPL_ID = ? OR OPL_ID = (SELECT OPL_ID_parent FROM tournaments WHERE OPL_ID = ?))", [$team["OPL_ID_group"],$team["OPL_ID_group"]])->fetch_assoc();
        }
		$team_name = $dbcn->execute_query("SELECT * FROM team_name_history WHERE OPL_ID_team = ? AND (update_time < ? OR ? IS NULL) ORDER BY update_time DESC", [$team["OPL_ID"],$tournament["dateEnd"],$tournament["dateEnd"]])->fetch_assoc();
		$curr_players = $dbcn->execute_query(
			"SELECT p.*, pit.removed
				FROM players p
				    JOIN players_in_teams_in_tournament pit
				        ON p.OPL_ID = pit.OPL_ID_player
				               AND pit.OPL_ID_tournament = ?
				WHERE OPL_ID_team = ?",
			[$tournamentID, $team['OPL_ID']]
		)->fetch_all(MYSQLI_ASSOC);
		$curr_opgglink = $opgg_url;
		$color_class = "";
		if ($view == "all" || $view == "all-wildcard") {
			$color_class = " liga".$league['number'];
		} elseif ($view == "div" || $view == "group" || $view == "wildcard") {
			$color_class = " rank".floor($team['avg_rank_num']??0);
		}
		foreach ($curr_players as $i_cop => $curr_player) {
			if ($curr_player["removed"] || $curr_player["riotID_name"] == null) continue;
			if ($i_cop != 0) {
				$curr_opgglink .= urlencode(",");
			}
			$curr_opgglink .= urlencode($curr_player["riotID_name"]."#".$curr_player["riotID_tag"]);
		}
		$results .= "
                    <div class='elo-list-row elo-list-team {$team['OPL_ID']}$color_class'>";
        if (str_contains($view, "wildcard")) {
            $comb_wc_num = ($league["numberRangeTo"] == null) ? $league["number"]."  " : $league["number"]."-".$league["numberRangeTo"];
            $results .= "<div class='elo-list-pre league'>Wc Liga $comb_wc_num</div>";
        } else {
            $results .= "<div class='elo-list-pre league'>Liga {$league['number']}</div>";
        }
        $results .= "
                        <div class='elo-list-item-wrapper'>
                            <button type='button' onclick='popup_team({$team['OPL_ID']},$tournamentID)' class='elo-list-item team page-link'>";
		if ($team['OPL_ID_logo'] != NULL && file_exists(__DIR__."/../$local_team_img{$team['OPL_ID_logo']}/logo.webp")) {
			$results .= "
                                <img class='color-switch' src='$local_team_img{$team['OPL_ID_logo']}/$logo_filename' alt='Teamlogo'>";
		} else {
			$results .= "<img class='color-switch' src='data:image/gif;base64,R0lGODlhAQABAAD/ACwAAAAAAQABAAACADs%3D' alt='Teamlogo'>";
		}
		$results .= "
                                <span class='page-link-target'>
                                	<span class='team-name'>{$team_name['name']}</span>
                                	<span class='material-symbol page-link-icon popup-icon'>
                                		".file_get_contents(__DIR__."/../icons/material/ad_group.svg")."
									</span>
                                </span>
                            </button>
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
                        
                        <div class='elo-list-item elo-nr'>
                            <span>({$avg_rank_num})</span>
                        </div>
                        </div>
                    </div>";
	}
	$results .= "
                </div>"; // teams-elo-list

	return $results;
}

function create_matchhistory(mysqli $dbcn, $tournament_ID, $group_ID, $team_ID) {
	$tournament = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID = ? AND eventType = 'tournament'", [$tournament_ID])->fetch_assoc();
	$group = $dbcn->execute_query("SELECT * FROM tournaments WHERE (eventType='group' OR (eventType = 'league' AND format = 'swiss') OR eventType='wildcard') AND OPL_ID = ?", [$group_ID])->fetch_assoc();

	$teams_from_groupDB = $dbcn->execute_query("SELECT * FROM teams JOIN teams_in_tournaments tit on teams.OPL_ID = tit.OPL_ID_team WHERE OPL_ID_group = ?", [$group["OPL_ID"]])->fetch_all(MYSQLI_ASSOC);
	$teams_from_group = [];
	foreach ($teams_from_groupDB as $i=>$team_from_group) {
		$teams_from_group[$team_from_group['OPL_ID']] = $team_from_group;
	}

	$matches = $dbcn->execute_query("SELECT * FROM matchups WHERE OPL_ID_tournament = ? AND (OPL_ID_team1 = ? OR OPL_ID_team2 = ?) AND played IS TRUE", [$group["OPL_ID"],$team_ID,$team_ID])->fetch_all(MYSQLI_ASSOC);

	foreach ($matches as $m=>$match) {
		$games = $dbcn->execute_query("SELECT * FROM games g JOIN games_to_matches gtm on g.RIOT_matchID = gtm.RIOT_matchID WHERE OPL_ID_matches = ? ORDER BY g.RIOT_matchID",[$match['OPL_ID']])->fetch_all(MYSQLI_ASSOC);
		$team1 = $teams_from_group[$match['OPL_ID_team1']];
		$team2 = $teams_from_group[$match['OPL_ID_team2']];
		$team1name = $dbcn->execute_query("SELECT * FROM team_name_history WHERE OPL_ID_team = ? AND (update_time < ? OR ? IS NULL) ORDER BY update_time DESC", [$team1["OPL_ID"],$tournament["dateEnd"],$tournament["dateEnd"]])->fetch_assoc();
		$team2name = $dbcn->execute_query("SELECT * FROM team_name_history WHERE OPL_ID_team = ? AND (update_time < ? OR ? IS NULL) ORDER BY update_time DESC", [$team2["OPL_ID"],$tournament["dateEnd"],$tournament["dateEnd"]])->fetch_assoc();

		if ($match['winner'] == $match['OPL_ID_team1']) {
			$team1score = "win";
			$team2score = "loss";
		} elseif ($match['winner'] == $match['OPL_ID_team2']) {
			$team1score = "loss";
			$team2score = "win";
		} else {
			$team1score = "draw";
			$team2score = "draw";
		}
		if ($m != 0) {
			echo "<div class='divider rounds'></div>";
		}
		echo "<div id='{$match['OPL_ID']}' class='round-wrapper'>";
		echo "
                <h2 class='round-title'>
                    <span class='round'>Runde {$match['playday']}: &nbsp</span>
                    <span class='team $team1score'>{$team1name['name']}</span>
                    <span class='score'><span class='$team1score'>{$match['team1Score']}</span>:<span class='$team2score'>{$match['team2Score']}</span></span>
                    <span class='team $team2score'>{$team2name['name']}</span>
                </h2>";
		if ($games == NULL) {
			echo "</div>";
			continue;
		}
		foreach ($games as $game) {
			$gameID = $game['RIOT_matchID'];
			echo create_game($dbcn,$gameID,$team_ID,tournamentID: $tournament_ID);
		}
		echo "</div>";
	}
}

function create_standings(mysqli $dbcn, $tournament_id, $group_id, $team_id=NULL):string {
	$result = "";
	$opgg_url = "https://www.op.gg/multisearch/euw?summoners=";
	$local_img_path = "img/team_logos/";
    $logo_filename = is_light_mode() ? "logo_light.webp" : "logo.webp";
	$opgg_logo_svg = file_get_contents(__DIR__."/../img/opgglogo.svg");
	$group = $dbcn->execute_query("SELECT * FROM tournaments WHERE (eventType = 'group' OR (eventType = 'league' AND format = 'swiss') OR eventType = 'wildcard' OR eventType = 'playoffs') AND OPL_ID = ?",[$group_id])->fetch_assoc();
	$div = $dbcn->execute_query("SELECT * FROM tournaments WHERE eventType = 'league' AND OPL_ID = ?",[$group['OPL_ID_parent']])->fetch_assoc();
	$teams_from_groupDB = $dbcn->execute_query("SELECT teams.*, tit.*, ttr.*, ttr2.avg_rank_tier as avg_rank_tier_2, ttr2.avg_rank_div as avg_rank_div_2
														FROM teams
														    JOIN teams_in_tournaments tit
														        ON teams.OPL_ID = tit.OPL_ID_team
															LEFT JOIN teams_tournament_rank ttr
																ON teams.OPL_ID = ttr.OPL_ID_team
																	AND ttr.OPL_ID_tournament = ?
																	AND ttr.second_ranked_split = false
															LEFT JOIN teams_tournament_rank ttr2
																ON teams.OPL_ID = ttr2.OPL_ID_team
																	AND ttr2.OPL_ID_tournament = ?
																	AND ttr2.second_ranked_split = true
														WHERE tit.OPL_ID_group = ?
															AND teams.OPL_ID > -1
														ORDER BY IF((standing=0 OR standing IS NULL), 1, 0), standing",[$tournament_id,$tournament_id,$group_id])->fetch_all(MYSQLI_ASSOC);
	$tournament = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID = ?", [$tournament_id])->fetch_assoc();
	$ranked_split_1 = "{$tournament['ranked_season']}-{$tournament['ranked_split']}";
	$ranked_split_2 = get_second_ranked_split_for_tournament($dbcn,$tournament_id,string:true);
	$current_split = get_current_ranked_split($dbcn,$tournament_id);
	if ($current_split == $ranked_split_2) {
		$rank_hide_1 = "display: none";
		$rank_hide_2 = "";
	} else {
		$rank_hide_1 = "";
		$rank_hide_2 = "display: none";
	}

	$result .= "<div class='standings'>";
	if ($team_id == NULL) {
		$result .= "<div class='title'><h3>Standings</h3></div>";
	} elseif ($group["eventType"] == "wildcard") {
		$wildcard_numbering = ($group["numberRangeTo"] == null) ? "{$group['number']}" : "{$group['number']}-{$group["numberRangeTo"]}";
		$result .= "<div class='title'>
						<h3>
							Standings 
							<a href='turnier/$tournament_id/wildcard/{$group['OPL_ID']}' class='page-link'>
								<span class='link-text'>Wildcard-Turnier Liga $wildcard_numbering</span>
								<span class='material-symbol page-link-icon'>".file_get_contents(__DIR__."/../icons/material/chevron_right.svg")."</span>
							</a>
						</h3>
					</div>";
	} elseif ($group["format"] == "swiss") {
		$result .= "<div class='title'>
						<h3>
							Standings 
							<a href='turnier/$tournament_id/gruppe/{$group['OPL_ID']}' class='page-link'>
								<span class='link-text'>Liga {$group['number']}</span>
								<span class='material-symbol page-link-icon'>".file_get_contents(__DIR__."/../icons/material/chevron_right.svg")."</span>
							</a>
						</h3>
					</div>";
	} else {
		$result .= "<div class='title'>
						<h3>
							Standings 
							<a href='turnier/$tournament_id/gruppe/{$group['OPL_ID']}' class='page-link'>
								<span class='link-text'>Liga {$div['number']} / Gruppe {$group['number']}</span>
								<span class='material-symbol page-link-icon'>".file_get_contents(__DIR__."/../icons/material/chevron_right.svg")."</span>
							</a>
						</h3>
					</div>";
	}
	$result .= "<div class='standings-table content'>
			<div class='standing-row standing-header'>
				<div class='standing-pre-header rank'>#</div>
				<div class='standing-item-wrapper-header'>
					<div class='standing-item team'>Team</div>
					<div class='standing-item played'>Pl</div>
					<div class='standing-item score'>W - D - L</div>
					<div class='standing-item points'>Pt</div>
                </div>
            </div>";
	$last_rank = -1;
	foreach ($teams_from_groupDB as $currteam) {
		$curr_players = $dbcn->execute_query(
			"SELECT *
					FROM players
					    JOIN players_in_teams_in_tournament pit
					        on players.OPL_ID = pit.OPL_ID_player
								AND pit.OPL_ID_tournament = ?
					WHERE OPL_ID_team = ?",
			[$tournament_id, $currteam['OPL_ID']]
		)->fetch_all(MYSQLI_ASSOC);
		$curr_opgglink = $opgg_url;
		foreach ($curr_players as $i_cop=>$curr_player) {
			if ($curr_player["removed"] || $curr_player["riotID_name"] == null) continue;
			if ($i_cop != 0) {
				$curr_opgglink .= urlencode(",");
			}
			$curr_opgglink .= urlencode($curr_player["riotID_name"]."#".$curr_player["riotID_tag"]);
		}
		$team_name_now = $dbcn->execute_query("SELECT name FROM team_name_history WHERE OPL_ID_team = ? AND (update_time < ? OR ? IS NULL) ORDER BY update_time DESC", [$currteam["OPL_ID"],$tournament["dateEnd"],$tournament["dateEnd"]])->fetch_column();
		$currteam["name"] = $team_name_now;
		if ($team_id != NULL) {
			$current = ($currteam['OPL_ID'] == $team_id)? " current" : "";
		} else {
			$current = "";
		}
		$same_rank_class = "";
		if ($last_rank == $currteam['standing']) {
			$same_rank_class = " shared-rank";
		}
		$result .= "<div class='standing-row standing-team$current'>
				<div class='standing-pre rank$same_rank_class'>{$currteam['standing']}</div>
				<div class='standing-item-wrapper'>
				<a class='standing-item team page-link' href='turnier/$tournament_id/team/{$currteam['OPL_ID']}'>";
		if ($currteam['OPL_ID_logo'] != NULL && file_exists(__DIR__."/../$local_img_path{$currteam['OPL_ID_logo']}/logo.webp")) {
			$result .= "<img class='color-switch' src='$local_img_path{$currteam['OPL_ID']}/$logo_filename' alt=\"Teamlogo\">";
		} else {
			$result .= "<img class='color-switch' src='data:image/gif;base64,R0lGODlhAQABAAD/ACwAAAAAAQABAAACADs%3D' alt=\"Teamlogo\">";

		}
		if ($currteam['avg_rank_tier'] != NULL || $currteam['avg_rank_tier_2'] != NULL) {
			$result .= "<div class='team-name-rank'>";
			$result .= "
                        <span class='page-link-target'>
                        	<span class='team-name' title='{$currteam['name']}'>
                        		{$currteam['name']}
							</span>
							<span class='material-symbol page-link-icon'>".file_get_contents(__DIR__."/../icons/material/chevron_right.svg")."</span>
                        </span>";
			if ($currteam['avg_rank_tier'] != NULL) {
				$team_tier = strtolower($currteam['avg_rank_tier']);
				$team_tier_cap = ucfirst($team_tier);
				$result .= "<span class='rank split_rank_element ranked-split-$ranked_split_1' style='$rank_hide_1' >
                            <img class='rank-emblem-mini' src='ddragon/img/ranks/mini-crests/$team_tier.svg' alt='$team_tier_cap'>
                            $team_tier_cap ".$currteam['avg_rank_div']."
                        </span>";
			}
			if ($currteam['avg_rank_tier_2'] != NULL) {
				$team_tier = strtolower($currteam['avg_rank_tier_2']);
				$team_tier_cap = ucfirst($team_tier);
				$result .= "<span class='rank split_rank_element ranked-split-$ranked_split_2' style='$rank_hide_2'>
                            <img class='rank-emblem-mini' src='ddragon/img/ranks/mini-crests/$team_tier.svg' alt='$team_tier_cap'>
                            $team_tier_cap ".$currteam['avg_rank_div_2']."
                        </span>";
			}
			$result .= "</div>
                  </a>";
		} else {
			$result .= "<span class='page-link-target'>
                        	<span class='team-name' title='{$currteam['name']}'>
                        		{$currteam['name']}
							</span>
							<span class='material-symbol page-link-icon'>".file_get_contents(__DIR__."/../icons/material/chevron_right.svg")."</span>
                        </span>
                        </a>";
		}
		$result .= "
                    <div class='standing-item played'>{$currteam['played']}</div>
                    <div class='standing-item score'>{$currteam['wins']}-{$currteam['draws']}-{$currteam['losses']}</div>
                    <div class='standing-item points'>{$currteam['points']}</div>
                </div>
            </div>";
		$last_rank = $currteam['standing'];
		$result .= "<div class='divider-light'></div>";
	}
	$result .= "</div></div>";

	return $result;
}

function create_matchbutton(mysqli $dbcn,$match_id,$type,$tournament_id,$team_id=NULL):string {
	$result = "";
	$pageurl = $_SERVER['REQUEST_URI'];
	$opl_match_url = "https://www.opleague.pro/match/";
	$type = ($type == "group") ? "groups" : $type;
	if ($type == "groups" || $type == "playoffs" || $type == "wildcard") {
		$match = $dbcn->execute_query("SELECT * FROM matchups WHERE OPL_ID = ?",[$match_id])->fetch_assoc();
	} else {
		return "";
	}
	$teams_from_DB = $dbcn->execute_query("SELECT * FROM teams")->fetch_all(MYSQLI_ASSOC);
	$teams = [];
	foreach ($teams_from_DB as $i=>$team) {
		$teams[$team['OPL_ID']] = array("TeamName"=>$team['name'], "imgID"=>$team['OPL_ID']);
	}

	$tournament = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID = ?", [$tournament_id])->fetch_assoc();

	if ($match["OPL_ID_team1"] != "") {
		$team1Name = $dbcn->execute_query("SELECT name FROM team_name_history WHERE OPL_ID_team = ? AND (update_time < ? OR ? IS NULL) ORDER BY update_time DESC", [$match["OPL_ID_team1"],$tournament["dateEnd"],$tournament["dateEnd"]])->fetch_column();
	} else {
		$team1Name = "TBD";
	}
	if ($match["OPL_ID_team2"] != "") {
		$team2Name = $dbcn->execute_query("SELECT name FROM team_name_history WHERE OPL_ID_team = ? AND (update_time < ? OR ? IS NULL) ORDER BY update_time DESC", [$match["OPL_ID_team2"],$tournament["dateEnd"],$tournament["dateEnd"]])->fetch_column();
	} else {
		$team2Name = "TBD";
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
		$playdate = ($match['plannedDate'] == NULL || strtotime($match['plannedDate']) == 0) ? "vs." : "$date<br>$time";
		$result .= "<div class='match-button-wrapper' data-matchid='$match_id' data-matchtype='$type' data-tournamentid='$tournament_id'>
                            <a class='button match sideext-right'>
                                <div class='teams'>
                                    <div class='team 1$current1' title='$team1Name'>$team1Name</div>
                                    <div class='team 2$current2' title='$team2Name'>$team2Name</div>
                                    <div class='date'>$playdate</div>
                                </div>";
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
		$result .= "<div class='match-button-wrapper' data-matchid='$match_id' data-matchtype='$type' data-tournamentid='$tournament_id'>";
		$team_id_pass = ($team_id != null) ? "\"$team_id\"" : "null";
		$tournament_id_pass = ($tournament_id != null) ? "\"$tournament_id\"" : "null";
		$result .= "<a class='button match sideext-right' href='$pageurl' onclick='popup_match({$match['OPL_ID']},$team_id_pass,\"$type\",$tournament_id_pass)'>";
		$result .= "<div class='teams score'>
				<div class='team 1 $state1$current1' title='$team1Name'>$team1Name</div>
				<div class='score 1 $state1$current1'>{$t1score}</div>
				<div class='team 2 $state2$current2' title='$team2Name'>$team2Name</div>
				<div class='score 2 $state2$current2'>{$t2score}</div>
			  </div>
			</a>
			<a class='sidebutton-match' href='$opl_match_url{$match['OPL_ID']}' target='_blank'>
				<div class='material-symbol'>". file_get_contents(__DIR__."/../icons/material/open_in_new.svg") ."</div>
			</a>
		</div>";
	}
	return $result;
}

function create_matchlist(mysqli $dbcn,$tournamentID,$eventID):string {
	$result = "";

	$tournament = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID = ?", [$tournamentID])->fetch_assoc();
	$event = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID = ?", [$eventID])->fetch_assoc();

	$result .= "<div class='matches'>
                <div class='title'><h3>Spiele</h3></div>";

	$curr_matchID = $_GET['match'] ?? NULL;
	if ($curr_matchID != NULL) {
		$curr_matchData = $dbcn->execute_query("SELECT * FROM matchups WHERE OPL_ID = ?",[$curr_matchID])->fetch_assoc();
		$curr_games = $dbcn->execute_query("SELECT * FROM games g JOIN games_to_matches gtm on g.RIOT_matchID = gtm.RIOT_matchID WHERE OPL_ID_matches = ? ORDER BY g.RIOT_matchID",[$curr_matchID])->fetch_all(MYSQLI_ASSOC);
		$curr_team1 = $dbcn->execute_query("SELECT * FROM teams LEFT JOIN team_name_history tnh ON tnh.OPL_ID_team = teams.OPL_ID AND (update_time < ? OR ? IS NULL) WHERE OPL_ID = ? ORDER BY update_time DESC",[$tournament["dateEnd"],$tournament["dateEnd"],$curr_matchData['OPL_ID_team1']])->fetch_assoc();
		$curr_team2 = $dbcn->execute_query("SELECT * FROM teams LEFT JOIN team_name_history tnh ON tnh.OPL_ID_team = teams.OPL_ID AND (update_time < ? OR ? IS NULL) WHERE OPL_ID = ? ORDER BY update_time DESC",[$tournament["dateEnd"],$tournament["dateEnd"],$curr_matchData['OPL_ID_team2']])->fetch_assoc();

		if (!$tournament["archived"]) {
			$last_user_update_match = $dbcn->execute_query("SELECT last_update FROM updates_user_matchup WHERE OPL_ID_matchup = ?", [$curr_matchID])->fetch_column();
			$last_cron_update = $dbcn->execute_query("SELECT last_update FROM updates_cron WHERE OPL_ID_tournament = ?", [$tournamentID])->fetch_column();

			$last_update_match = max($last_user_update_match,$last_cron_update);

			if ($last_update_match == NULL) {
				$updatediff_match = "unbekannt";
			} else {
				$last_update_match = strtotime($last_update_match);
				$currtime = time();
				$updatediff_match = max_time_from_timestamp($currtime-$last_update_match);
			}
		}

		$result .= "
                    <div class='mh-popup-bg' onclick='close_popup_match(event)' style='display: block; opacity: 1;'>
                        <div class='mh-popup'>
                            <div class='close-button' onclick='closex_popup_match()'><div class='material-symbol'>". file_get_contents(__DIR__."/../icons/material/close.svg") ."</div></div>
                            <div class='close-button-space'></div>
                            <div class='mh-popup-buttons'>";
		if (!$tournament["archived"]) {
			$result .= "                      <div class='updatebuttonwrapper'><button type='button' class='icononly user_update_match update_data' data-match='$curr_matchID' data-matchformat='groups' data-group='$eventID'><div class='material-symbol'>". file_get_contents(__DIR__."/../icons/material/sync.svg") ."</div></button><span>letztes Update:<br>$updatediff_match</span></div>";
		}
		$result .= "                  </div>";
		if ($curr_matchData['winner'] == $curr_matchData['OPL_ID_team1']) {
			$team1score = "win";
			$team2score = "loss";
		} elseif ($curr_matchData['winner'] == $curr_matchData['OPL_ID_team2']) {
			$team1score = "loss";
			$team2score = "win";
		} else {
			$team1score = "draw";
			$team2score = "draw";
		}
		$result .= "
                <h2 class='round-title'>
                    <span class='round'>Runde {$curr_matchData['playday']}: &nbsp</span>
                    <span class='team $team1score'>{$curr_team1['name']}</span>
                    <span class='score'><span class='$team1score'>{$curr_matchData['team1Score']}</span>:<span class='$team2score'>{$curr_matchData['team2Score']}</span></span>
                    <span class='team $team2score'>{$curr_team2['name']}</span>
                </h2>";
		if ($curr_games == null) {
			$result .= "<div class=\"no-game-found\">Keine Spieldaten gefunden</div>";
		}
		foreach ($curr_games as $game_i=>$curr_game) {
			$result .= "<div class='game game$game_i'>";
			$gameID = $curr_game['RIOT_matchID'];
			$result .= create_game($dbcn,$gameID,tournamentID: $tournamentID);
			$result .= "</div>";
		}
		$result .= "
                        </div>
                    </div>";
	} else {
		$result .= "   <div class='mh-popup-bg' onclick='close_popup_match(event)'>
                            <div class='mh-popup'></div>
                     </div>";
	}


	if ($event["format"] == "double-elimination" || $event["format"] == "single-elimination") {
		$matches = $dbcn->execute_query("
                                        SELECT *
                                        FROM matchups
                                        WHERE OPL_ID_tournament = ?
                                          AND NOT ((OPL_ID_team1 IS NULL || matchups.OPL_ID_team1 < 0) AND (OPL_ID_team2 IS NULL OR OPL_ID_team2 < 0))
                                        ORDER BY plannedDate",[$eventID])->fetch_all(MYSQLI_ASSOC);
		$matches_grouped = [];
		foreach ($matches as $match) {
			if ($match['plannedDate'] == null) continue;
			$plannedDate = new DateTime($match['plannedDate']);
			$plannedDay = $plannedDate->format("Y-m-d H");
			$matches_grouped[$plannedDay][] = $match;
		}
	} else {
		$matches = $dbcn->execute_query("SELECT * FROM matchups WHERE OPL_ID_tournament = ? ORDER BY playday",[$eventID])->fetch_all(MYSQLI_ASSOC);
		$matches_grouped = [];
		foreach ($matches as $match) {
			$matches_grouped[$match['playday']][] = $match;
		}
	}

	$eventType = ($event["eventType"] == "group" || ($event["eventType"] == "league" && $event["format"] == "swiss")) ? "groups" : $event["eventType"];

	$result .= "<div class='match-content content'>";
	$roundCounter = 0;
	foreach ($matches_grouped as $roundNum=>$round) {
		$roundCounter++;
		if ($event["format"] == "double-elimination" || $event["format"] == "single-elimination") $roundNum = $roundCounter;
		$result .= "<div class='match-round'>
                    <h4>Runde $roundNum</h4>
                    <div class='divider'></div>
                    <div class='match-wrapper'>";
		foreach ($round as $match) {
			$result .= create_matchbutton($dbcn,$match['OPL_ID'],$eventType,$tournamentID);
		}
		$result .= "</div>";
		$result .= "</div>"; // match-round
	}
	$result .= "</div>"; // match-content
	$result .= "</div>"; // matches

	return $result;
}

function create_team_nav_buttons($tournamentID,$groupID,$team,$active,$allGroupIDs=null,$playoffID=null,$updatediff="unbekannt", bool $hide_update = false):string {
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
    $logo_filename = is_light_mode() ? "logo_light.webp" : "logo.webp";
	$opl_team_url = "https://www.opleague.pro/team/";
	$team_id = $team['OPL_ID'];
	$result .= "<div class='team pagetitle'>";
	if ($team['OPL_ID_logo'] != NULL && file_exists(__DIR__."/../$local_team_img{$team['OPL_ID_logo']}/logo.webp")) {
		$result .= "<img class='color-switch' alt src='$local_team_img{$team['OPL_ID_logo']}/$logo_filename'>";
	}
	$result .= "
			<div>
				<h2 class='pagetitle'><a class='page-link' href='team/$team_id'><span class='link-text'>{$team['name']}</span><span class='material-symbol page-link-icon'>".file_get_contents(__DIR__."/../icons/material/chevron_right.svg")."</span></a></h2>
				<a href=\"$opl_team_url$team_id\" class='opl-link' target='_blank'><span class='material-symbol'>". file_get_contents(__DIR__."/../icons/material/open_in_new.svg") ."</span></a>
			</div>";
	$data_playoff = ($playoffID != null) ? "data-playoff='$playoffID'" : "";
	if ($active == "details" && !$hide_update) {
		$result .= "
				<div class='updatebuttonwrapper'>
           			<button type='button' class='user_update user_update_team update_data' data-team='$team_id' data-tournament='$tournamentID' data-group='$groupID' data-groups='$allGroupIDs' $data_playoff><div class='material-symbol'>".file_get_contents(__DIR__."/../icons/material/sync.svg")."</div></button>
					<span>letztes Update:<br>$updatediff</span>
				</div>";
	}
	$result .= "</div>";
	$result .= "
        <nav class='team-titlebutton-wrapper'>
           	<a href='turnier/$tournamentID/team/$team_id' class='$details_a'><div class='material-symbol'>". file_get_contents(__DIR__."/../icons/material/info.svg") ."</div>Team-Übersicht</a>
           	<a href='turnier/$tournamentID/team/$team_id/matchhistory' class='$matchhistory_a'><div class='material-symbol'>". file_get_contents(__DIR__."/../icons/material/manage_search.svg") ."</div>Match-History</a>
            <a href='turnier/$tournamentID/team/$team_id/stats' class='$stats_a'><div class='material-symbol'>". file_get_contents(__DIR__."/../icons/material/monitoring.svg") ."</div>Statistiken</a>
        </nav>";
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

function create_game(mysqli $dbcn,$gameID,$curr_team=NULL,$tournamentID=null, $return="minimized"):string {
	$result_minimized = "";
	$result = "";
	// TODO: tournamentID integrieren, falls ein game in mehreren turnieren eingetragen ist (aktuell wird einfach das erste geholt)
	//$gameDB = $dbcn->execute_query("SELECT * FROM games JOIN games_in_tournament git on games.RIOT_matchID = git.RIOT_matchID WHERE games.RIOT_matchID = ?",[$gameID])->fetch_assoc();
	$gameDB = $dbcn->execute_query("SELECT * FROM games g JOIN games_to_matches gtm on g.RIOT_matchID = gtm.RIOT_matchID WHERE g.RIOT_matchID = ?", [$gameID])->fetch_assoc();
	if ($gameDB == null || $gameDB["matchdata"] == null) return "";
	$matchup = $dbcn->execute_query("SELECT * FROM matchups WHERE OPL_ID IN (SELECT OPL_ID_matches FROM games_to_matches WHERE RIOT_matchID = ?)", [$gameID])->fetch_assoc();
	$team_blue_ID = $gameDB['OPL_ID_blueTeam'];
	$team_red_ID = $gameDB['OPL_ID_redTeam'];
	$team_blue = $dbcn->execute_query("SELECT * FROM teams WHERE OPL_ID = ?",[$team_blue_ID])->fetch_assoc();
	$team_red = $dbcn->execute_query("SELECT * FROM teams WHERE OPL_ID = ?",[$team_red_ID])->fetch_assoc();
	if ($tournamentID == null) {
		$players_blue_DB = $dbcn->execute_query("SELECT PUUID, riotID_name, riotID_tag, rank_tier, rank_div
														FROM players
														    JOIN players_in_teams_in_tournament pit on players.OPL_ID = pit.OPL_ID_player
														WHERE OPL_ID_team = ?",[$team_blue['OPL_ID']])->fetch_all(MYSQLI_ASSOC);
		$players_red_DB = $dbcn->execute_query("SELECT PUUID, riotID_name, riotID_tag, rank_tier, rank_div
														FROM players
														    JOIN players_in_teams_in_tournament pit on players.OPL_ID = pit.OPL_ID_player
														WHERE OPL_ID_team = ?",[$team_red['OPL_ID']])->fetch_all(MYSQLI_ASSOC);
	} else {
		$players_blue_DB = $dbcn->execute_query("SELECT PUUID, riotID_name, riotID_tag, psr.rank_tier, psr.rank_div
														FROM players
														    JOIN players_in_teams_in_tournament pit on players.OPL_ID = pit.OPL_ID_player
															LEFT JOIN players_season_rank psr on psr.OPL_ID_player = players.OPL_ID AND psr.season = (SELECT tournaments.season FROM tournaments WHERE tournaments.OPL_ID = ?)
														WHERE OPL_ID_team = ?",[$tournamentID,$team_blue['OPL_ID']])->fetch_all(MYSQLI_ASSOC);
		$players_red_DB = $dbcn->execute_query("SELECT PUUID, riotID_name, riotID_tag, psr.rank_tier, psr.rank_div
														FROM players
														    JOIN players_in_teams_in_tournament pit on players.OPL_ID = pit.OPL_ID_player
															LEFT JOIN players_season_rank psr on psr.OPL_ID_player = players.OPL_ID AND psr.season = (SELECT tournaments.season FROM tournaments WHERE tournaments.OPL_ID = ?)
														WHERE OPL_ID_team = ?",[$tournamentID,$team_red['OPL_ID']])->fetch_all(MYSQLI_ASSOC);
	}

	//$tournamentID = $gameDB["OPL_ID_tournament"];
	$tournament = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID = (SELECT OPL_ID_top_parent FROM tournaments WHERE OPL_ID = ?)", [$matchup["OPL_ID_tournament"]])->fetch_assoc();
	$tournamentID = $tournament["OPL_ID"];

	//$tournament = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID = ?", [$tournamentID])->fetch_assoc();

	$team_name_blue = $dbcn->execute_query("SELECT * FROM team_name_history WHERE OPL_ID_team = ? AND (update_time < ? OR ? IS NULL) ORDER BY update_time DESC", [$team_blue_ID,$tournament["dateEnd"],$tournament["dateEnd"]])->fetch_assoc();
	$team_name_red = $dbcn->execute_query("SELECT * FROM team_name_history WHERE OPL_ID_team = ? AND (update_time < ? OR ? IS NULL) ORDER BY update_time DESC", [$team_red_ID,$tournament["dateEnd"],$tournament["dateEnd"]])->fetch_assoc();

	$players_PUUID = [];
	for ($i = 0; $i < count($players_blue_DB); $i++)  {
		$players_PUUID[$players_blue_DB[$i]['PUUID']] = $players_blue_DB[$i];
	}
	for ($i = 0; $i < count($players_red_DB); $i++)  {
		$players_PUUID[$players_red_DB[$i]['PUUID']] = $players_red_DB[$i];
	}

	$data = json_decode($gameDB['matchdata'],true);
	$info = $data['info'];
	$participants = $info['participants'];
	for ($team_index = 0; $team_index <= 1; $team_index++) {
		for ($player_index = $team_index*5; $player_index < $team_index*5+5; $player_index++) {
			$roles = ["TOP","JUNGLE","MIDDLE","BOTTOM","UTILITY"];
			$roles_check = array("TOP"=>0,"JUNGLE"=>1,"MIDDLE"=>2,"BOTTOM"=>3,"UTILITY"=>4);
			$role = $participants[$player_index]['teamPosition'];
			if ($role != $roles[$player_index-($team_index*5)] && $role !== "") {
				$player_2_index = $roles_check[$role] + $team_index*5;
				$helper = $participants[$player_index];
				$participants[$player_index] = $participants[$player_2_index];
				$participants[$player_2_index] = $helper;
			}
		}
	}
	$teams = $info['teams'];


	if ($info['teams'][0]['win']) {
		$winning_team = $team_blue;
		$score_blue = "Victory";
		$score_red = "Defeat";
		$score_blue_class = " win";
		$score_red_class = " loss";
	} else {
		$winning_team = $team_red;
		$score_blue = "Defeat";
		$score_red = "Victory";
		$score_blue_class = " loss";
		$score_red_class = " win";
	}

	$score_current_class = "";
	$blue_curr = "";
	$red_curr = "";
	$general_class = "";
	if ($curr_team == $team_blue_ID) {
		$score_current_class = ($info['teams'][0]['win']) ? "win" : "loss";
		$blue_curr = "current";
		$score_text = $score_blue;
	} elseif ($curr_team == $team_red_ID) {
		$score_current_class = ($info['teams'][1]['win']) ? "win" : "loss";
		$red_curr = "current";
		$score_text = $score_red;
	} else {
		$general_class = "general";
		$score_text = $winning_team["name"];
	}

	$obj_icon_url = "ddragon/img/";
	$kills_icon = $obj_icon_url."kills.png";
	$obj_icons = $obj_icon_url."right_icons.png";
	$gold_icons = $obj_icon_url."icon_gold.png";
	$cs_icons = $obj_icon_url."icon_minions.png";



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

	$champion_dd = file_get_contents("$dd_data/champion.json");
	$champion_dd = json_decode($champion_dd,true);
	$champion_data = $champion_dd['data'];
	$champions_by_key = [];
	foreach ($champion_data as $champ) {
		$champions_by_key[$champ['key']] = $champ['id'];
	}

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

	$summs_dd = json_decode(file_get_contents("$dd_data/summoner.json"),true);
	$summs = array_column($summs_dd['data'],"id","key");

	$game_duration = $info['gameDuration'];
	$game_duration_min = floor($game_duration / 60);
	$game_duration_sec = $game_duration % 60;
	if ($game_duration_sec < 10) {
		$game_duration_sec = "0".$game_duration_sec;
	}

	$local_team_img = "img/team_logos/";
    $logo_filename = is_light_mode() ? "logo_light.webp" : "logo.webp";
	if ($team_blue['OPL_ID_logo'] == NULL || !file_exists(__DIR__."/../$local_team_img{$team_blue['OPL_ID_logo']}/logo.webp")) {
		$logo_blue = "";
	} else {
		$logo_blue = "<img class='color-switch' alt='' src='$local_team_img{$team_blue['OPL_ID_logo']}/$logo_filename'>";
	}
	if ($team_red['OPL_ID_logo'] == NULL || !file_exists(__DIR__."/../$local_team_img{$team_red['OPL_ID_logo']}/logo.webp")) {
		$logo_red = "";
	} else {
		$logo_red = "<img class='color-switch' alt='' src='$local_team_img{$team_red['OPL_ID_logo']}/$logo_filename'>";
	}

	$gamedate = date("d.m.y",intval($info["gameCreation"]/1000));

	$result_minimized .= "<div class='game-details-mini $general_class $score_current_class'>";
	// gamedetails
	$result_minimized .= "<div class='game-information'>
							<span>$gamedate</span>
							<div class='game-result-text'>";
	//if ($curr_team != $team_blue_ID && $curr_team != $team_red_ID) $result_minimized .= "<div class='tooltip'><span class='winning-team'>{$winning_team["name"]}</span><span class='tooltiptext'>{$winning_team["name"]}</span></div>";
	$result_minimized .= "<span class='game-result $score_current_class'>$score_text</span>
</div>
							<span>$game_duration_min:$game_duration_sec</span>
						</div>";
	// team blue
	$result_minimized .= "<a class='team $score_blue_class $blue_curr' href='./turnier/$tournamentID/team/$team_blue_ID'>$logo_blue<span>{$team_name_blue["name"]}</span></a>";

	// players
	for ($t = 0; $t < 2; $t++) {
		$result_minimized .= "<div class='players'>";
		for ($p = 0; $p < 5; $p++) {
			$result_minimized .= "<div class='player'>";
			$player = $participants[$p+($t*5)];
			$championId = $player['championName'];
			$result_minimized .= "<img loading='lazy' alt='' title='{$player['championName']}' src='$dd_img/champion/{$championId}.webp' class='champ'>";

			$riotIdName = "";
			$riotIdTag = "";

			if (array_key_exists("riotIdGameName", $player) && $player["riotIdGameName"] != "") {
				$riotIdName = $player["riotIdGameName"];
				$riotIdTag = $player["riotIdTagline"];
			} else {
				if (array_key_exists($player["puuid"], $players_PUUID)) {
					$riotIdName = $players_PUUID[$player["puuid"]]['riotID_name'];
					$riotIdTag = $players_PUUID[$player["puuid"]]['riotID_tag'];
				} else {
					$riotIdName = $player["summonerName"];
				}
			}
			if ($riotIdTag != "") {
				$result_minimized .= "<div class='tooltip'><span class='player-name'>$riotIdName</span><span class='tooltiptext riot-id'>$riotIdName#$riotIdTag</span></div>";
			} else {
				$result_minimized .= "<div>$riotIdName</div>";
			}

			$result_minimized .= "</div>";
		}

		$result_minimized .= "</div>";
	}

	//team red
	$result_minimized .= "<a class='team $score_red_class $red_curr' href='./turnier/$tournamentID/team/$team_red_ID'>$logo_red<span>{$team_name_red["name"]}</span></a>";

	// expand button
	$result_minimized .= "<button class='expand-game-details'><div class='material-symbol'>".file_get_contents(dirname(__FILE__)."/../icons/material/expand_less.svg")."</div></button>";

	$result_minimized .= "</div>";

	$result .= "
    <div class='game-details'>
        <div class='game-row teams'>
            <a class='team 1 $blue_curr$score_blue_class' href='./turnier/$tournamentID/team/$team_blue_ID'>
                <div class='name'>$logo_blue{$team_name_blue["name"]}</div>
                <div class='score$score_blue_class'>$score_blue</div>
            </a>
            <div class='time'>
                <div>$game_duration_min:$game_duration_sec</div>
            </div>
            <a class='team 2 $red_curr$score_red_class' href='./turnier/$tournamentID/team/$team_red_ID'>
                <div class='score$score_red_class'>$score_red</div>
                <div class='name'>{$team_name_red["name"]}$logo_red</div>
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

			$riotIdName = "";
			$riotIdTag = "";

			if (array_key_exists("riotIdGameName", $player) && $player["riotIdGameName"] != "") {
				$riotIdName = $player["riotIdGameName"];
				$riotIdTag = $player["riotIdTagline"];
			} else {
				if (array_key_exists($player["puuid"], $players_PUUID)) {
					$riotIdName = $players_PUUID[$player["puuid"]]['riotID_name'];
					$riotIdTag = $players_PUUID[$player["puuid"]]['riotID_tag'];
				} else {
					$riotIdName = $player["summonerName"];
				}
			}

			$summoner_rank = "";
			$summoner_rank_div = "";
			$puuid = $player['puuid'];
			if (array_key_exists($puuid, $players_PUUID)) {
				$summoner_rank = strtolower($players_PUUID[$puuid]['rank_tier'] ?? "");
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
                <div class='summoner-name'>";
			if ($riotIdTag != "") {
				$result .= "<div class='tooltip'>$riotIdName<span class='tooltiptext interactable riot-id'>$riotIdName#$riotIdTag</span></div>";
			} else {
				$result .= "<div>$riotIdName</div>";
			}
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

	if ($return == "details") return $result;
	return "<div class='game-wrapper collapsed'>".$result_minimized.$result."</div>";
}

function show_old_url_warning($tournamentID):string {
	$url_root = "https://silence.lol";
	$url = $_SERVER["REQUEST_URI"];
	$new_url = "/toornament$url";
	if (strlen($tournamentID) > 15) {
		return "
			<div class='warning-header'>
				<span>Meintest du <a href='$new_url'>$url_root$new_url</a>?</span>
				<button onclick='close_warningheader()'><div class='material-symbol'>".file_get_contents(__DIR__."/../icons/material/close.svg")."</div></button>
			</div>
		";
	}
	return "";
}

function create_playercard(mysqli $dbcn, $playerID, $teamID, $tournamentID, $detail_stats=true) {
    $logo_filename = is_light_mode() ? "logo_light.webp" : "logo.webp";
	$player = $dbcn->execute_query("SELECT * FROM players_in_teams_in_tournament ptt LEFT JOIN players p on p.OPL_ID = ptt.OPL_ID_player LEFT JOIN stats_players_teams_tournaments spit on ptt.OPL_ID_player = spit.OPL_ID_player AND ptt.OPL_ID_team = spit.OPL_ID_team AND ptt.OPL_ID_tournament = spit.OPL_ID_tournament WHERE ptt.OPL_ID_player = ? AND ptt.OPL_ID_team = ? AND ptt.OPL_ID_tournament = ?", [$playerID, $teamID, $tournamentID])->fetch_assoc();
	$player_rank = $dbcn->execute_query("SELECT * FROM players_season_rank WHERE OPL_ID_player = ? AND season = (SELECT tournaments.ranked_season FROM tournaments WHERE tournaments.OPL_ID = ?) AND split = (SELECT tournaments.ranked_split FROM tournaments WHERE tournaments.OPL_ID = ?)", [$playerID, $tournamentID, $tournamentID])->fetch_assoc();
	$tournament = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID = ?", [$tournamentID])->fetch_assoc();
    $team = $dbcn->execute_query("SELECT * FROM teams WHERE OPL_ID = ?", [$teamID])->fetch_assoc();
	$team_name = $dbcn->execute_query("SELECT * FROM team_name_history WHERE OPL_ID_team = ? AND (update_time < ? OR ? IS NULL) ORDER BY update_time DESC", [$team["OPL_ID"],$tournament["dateEnd"],$tournament["dateEnd"]])->fetch_assoc();
	$team_in_tournament = $dbcn->execute_query("SELECT * FROM teams_in_tournaments WHERE OPL_ID_team = ? AND OPL_ID_group IN (SELECT OPL_ID FROM tournaments WHERE (eventType = 'group' OR (eventType='league' AND format='swiss')) AND OPL_ID_top_parent = ?)", [$teamID, $tournamentID])->fetch_assoc();
	if ($team_in_tournament == null) {
		$team_in_tournament = $dbcn->execute_query("SELECT * FROM teams_in_tournaments WHERE OPL_ID_team = ? AND OPL_ID_group IN (SELECT OPL_ID FROM tournaments WHERE eventType = 'wildcard' AND OPL_ID_top_parent = ?)", [$teamID, $tournamentID])->fetch_assoc();
	}
	$group = $dbcn->execute_query("SELECT * FROM tournaments WHERE (eventType='group' OR (eventType='league' AND format='swiss') OR eventType = 'wildcard') AND OPL_ID = ?", [$team_in_tournament["OPL_ID_group"]])->fetch_assoc();
	$league = $dbcn->execute_query("SELECT * FROM tournaments WHERE eventType='league' AND OPL_ID = ?", [$group["OPL_ID_parent"]])->fetch_assoc();
	if ($group["format"]=="swiss") $league = $group;
	if ($detail_stats) {
		$roles = $player['roles'] != null ? json_decode($player['roles'], true) : null;
		$champions = $player['champions'] != null ? json_decode($player['champions'], true) : null;
		$rendered_rows = 0;
		if ($roles != null && $champions != null) {
			if (array_sum($roles)>0 && count($champions)>0) {
				$rendered_rows = 2;
			} elseif (array_sum($roles)>0 || count($champions)>0) {
				$rendered_rows = 1;
			}
		}
		$result = "<div class='player-card' data-details='$rendered_rows'>";
	} else {
		$result = "<div class='player-card'>";
	}

	// Turnier-Titel
	$result .= "<a class='player-card-div player-card-tournament' href='turnier/{$tournament["OPL_ID"]}'>";
	if ($tournament["OPL_ID_logo"] != NULL) {
		$result .= "<img class='color-switch' alt='' src='img/tournament_logos/{$tournament["OPL_ID_logo"]}/$logo_filename'>";
	}
	$result .= ucfirst($tournament["split"])." ".$tournament["season"];
	$result .= "</a>";
	// Spielername und Summonername
	/*
	$result .= "<div class='player-card-div player-card-name'>
					<span>{$player["name"]}</span>";
    if ($player["riotID_name"] != null) {
        $result .= "<a href='https://op.gg/summoners/euw/{$player["riotID_name"]}-{$player["riotID_tag"]}' target='_blank'><div class='material-symbol'>".file_get_contents(dirname(__FILE__)."/../icons/material/person.svg")."</div>{$player["riotID_name"]}#{$player["riotID_tag"]}</a>";
    }
	$result .= "</div>";
	*/
	// Teamname
	$result .= "<a class='player-card-div player-card-team' href='turnier/$tournamentID/team/{$team["OPL_ID"]}'>";
	if ($team["OPL_ID_logo"] != NULL && file_exists(dirname(__FILE__)."/../img/team_logos/{$team["OPL_ID_logo"]}/$logo_filename")) {
		$result .= "<img class='color-switch' alt='' src='img/team_logos/{$team["OPL_ID_logo"]}/$logo_filename'>";
		$result .= "<span>{$team_name["name"]}</span>";
	} else {
		$result .= "<span class='player-card-nologo'>{$team_name["name"]}</span>";
	}
	$result .= "</a>";
	// Team Details im Turnier
	$group_title = ($group["format"] == "swiss") ? "Swiss-Gruppe" : " Gruppe {$group["number"]}";
	if ($group["eventType"] == "wildcard") {
		$wc_comb_num = ($group["numberRangeTo"] == null) ? $group["number"] : $group["number"]."-".$group["numberRangeTo"];
		$result .= "<a class='player-card-div player-card-group' href='turnier/$tournamentID/wildcard/{$group["OPL_ID"]}'>";
		$result .= "<span> Wildcard Liga $wc_comb_num</span>";
	} else {
		$result .= "<a class='player-card-div player-card-group' href='turnier/$tournamentID/gruppe/{$group["OPL_ID"]}'>";
		$result .= "<span>Liga {$league["number"]} - $group_title</span>";
	}
	$result .= "</a>";
	// detailed Stats
	if ($detail_stats) {
		$rank_tier = strtolower($player_rank["rank_tier"]??"");
		$rank_div = $player_rank["rank_div"]??null;
		$LP = NULL;
		if ($rank_tier == "CHALLENGER" || $rank_tier == "GRANDMASTER" || $rank_tier == "MASTER") {
			$rank_div = "";
			$LP = $player_rank["leaguePoints"]??null;
		}
		if ($LP != NULL) {
			$LP = "(".$LP." LP)";
		} else {
			$LP = "";
		}

		// Rang
		if ($player_rank["rank_tier"]??null != NULL) {
			$result .= "<div class='player-card-div player-card-rank'><img class='rank-emblem-mini' src='ddragon/img/ranks/mini-crests/$rank_tier.svg' alt='".ucfirst($rank_tier)."'>".ucfirst($rank_tier)." $rank_div $LP</div>";
		} else {
			$result .= "<div class='player-card-div player-card-rank'>kein Rang</div>";
		}

		// roles
		if ($roles != null && array_sum($roles) > 0) {
			$result .= "<div class='player-card-div player-card-roles'>";
			foreach ($roles as $role => $role_amount) {
				if ($role_amount != 0) {
					$result .= "
				<div class='role-single'>
					<div class='svg-wrapper role'>" . file_get_contents(dirname(__FILE__) . "/../ddragon/img/positions/position-$role-light.svg") . "</div>
					<span class='played-amount'>$role_amount</span>
				</div>";
				}
			}
			$result .= "</div>";
		}

		// champs
		if ($champions != null && count($champions) > 0) {
			$result .= "<div class='player-card-div player-card-champs'>";
			arsort($champions);
			$champs_cut = FALSE;
			if (count($champions) > 5) {
				$champions = array_slice($champions, 0, 5);
				$champs_cut = TRUE;
			}

			$patches = [];
			$dir = new DirectoryIterator(dirname(__FILE__) . "/../ddragon");
			foreach ($dir as $fileinfo) {
				if (!$fileinfo->isDot() && $fileinfo->getFilename() != "img") {
					$patches[] = $fileinfo->getFilename();
				}
			}
			usort($patches, "version_compare");
			$patch = end($patches);

			foreach ($champions as $champion => $champion_amount) {
				$result .= "
			<div class='champ-single'>
				<img src='./ddragon/{$patch}/img/champion/{$champion}.webp' alt='$champion'>
				<span class='played-amount'>" . $champion_amount['games'] . "</span>
			</div>";
			}
			if ($champs_cut) {
				$result .= "
		<div class='champ-single'>
			<div class='material-symbol'>" . file_get_contents(dirname(__FILE__) . "/../icons/material/more_horiz.svg") . "</div>
		</div>";
			}
			$result .= "</div>";
		}
		// erweiterungs button
		$result .= "<a class='player-card-div player-card-more' href='#' onclick='expand_playercard(this)'><div class='material-symbol'>".file_get_contents(dirname(__FILE__)."/../icons/material/expand_more.svg")."</div> mehr Infos</a>";
	}

	$result .= "</div>";
	return $result;
}

function create_player_overview(mysqli $dbcn,$playerid,$onplayerpage=false):string {
	$result = "";
    $teams_played_in = $dbcn->execute_query("SELECT * FROM players_in_teams_in_tournament WHERE OPL_ID_player = ? ORDER BY OPL_ID_tournament DESC", [$playerid])->fetch_all(MYSQLI_ASSOC);
	$player = $dbcn->execute_query("SELECT * FROM players WHERE OPL_ID = ?", [$playerid])->fetch_assoc();
	if (!$onplayerpage) $result .= "<a href='spieler/$playerid' class='button toplayer'><div class='material-symbol'>".file_get_contents(__DIR__."/../icons/material/person.svg")."</div>Zur Spielerseite</a>";
	$result .= "<div class='player-ov-titlewrapper'><h2 class='player-ov-name'>{$player["name"]}</h2><a href='https://www.opleague.pro/user/$playerid' class='toorlink' target='_blank'><div class='material-symbol'>".file_get_contents(__DIR__."/../icons/material/open_in_new.svg")."</div></a></div>";
	$result .= "<div class='divider'></div>";
	if ($player["riotID_name"] != null) {
		$result .= "<div class='player-ov-riotid-wrapper'>";
		$result .= "<a class='player-ov-riotid tooltip' href='https://op.gg/summoners/euw/{$player["riotID_name"]}-{$player["riotID_tag"]}' target='_blank'><span class='league-icon'>".file_get_contents(dirname(__FILE__)."/../icons/LoL_Icon_Flat.svg")."</span><span>{$player["riotID_name"]}#{$player["riotID_tag"]}</span><span class='tooltiptext linkinfo'><span class='material-symbol'>".file_get_contents(__DIR__."/../icons/material/open_in_new.svg")."</span>OP.GG</span></a>";
		$rank_tier = strtolower($player["rank_tier"]??"");
		$rank_div = $player["rank_div"];
		$LP = NULL;
		if ($rank_tier == "CHALLENGER" || $rank_tier == "GRANDMASTER" || $rank_tier == "MASTER") {
			$rank_div = "";
			$LP = $player["leaguePoints"];
		}
		if ($LP != NULL) {
			$LP = "(".$LP." LP)";
		} else {
			$LP = "";
		}
		if ($player["rank_tier"] != NULL) {
			$result .= "<div class='player-rank'><img class='rank-emblem-mini' src='ddragon/img/ranks/mini-crests/$rank_tier.svg' alt='".ucfirst($rank_tier)."'>".ucfirst($rank_tier)." $rank_div $LP</div>";
		} else {
			$result .= "<div class='player-rank'>kein Rang</div>";
		}
		$result .= "</div>";
	}
    if (count($teams_played_in) >= 2) {
        $result .= "<div class='player-ov-buttons'>";
		$result .= "<a href='#' class='button expand-pcards' title='Ausklappen' onclick='expand_all_playercards()'><div class='material-symbol'>".file_get_contents(dirname(__FILE__)."/../icons/material/unfold_more.svg")."</div></a>";
		$result .= "<a href='#' class='button expand-pcards' title='Einklappen' onclick='expand_all_playercards(true)'><div class='material-symbol'>".file_get_contents(dirname(__FILE__)."/../icons/material/unfold_less.svg")."</div></a>";
		$result .= "</div>";
    }
	$result .= "<div class='player-popup-content'>";
    foreach ($teams_played_in as $team) {
		$result .= create_playercard($dbcn, $playerid, $team["OPL_ID_team"], $team["OPL_ID_tournament"]);
    }
	$result .= "</div>";
	return $result;
}

function create_player_search_cards_from_search (mysqli $dbcn, string $search) {
    $players = $dbcn->execute_query("SELECT OPL_ID FROM players WHERE riotID_name LIKE ? OR name LIKE ?",["%".$search."%","%".$search."%"]);
    $playerids= array();
    while ($id = $players->fetch_column()) {
        $playerids[] = $id;
    }
    create_player_search_cards($dbcn,$playerids);
}
function create_player_search_cards(mysqli $dbcn, array $playerids, bool $remove_from_recents=false) {
    if ($playerids == NULL) {
        return;
    }
    $players = array();
    foreach ($playerids AS $playerid) {
        $player = $dbcn->execute_query("SELECT * FROM players WHERE OPL_ID = ?",[$playerid])->fetch_assoc();
        $players[] = $player;
    }
    $player_cards = "";

    foreach ($players as $player) {
        $player_cards .= "<div class='player-ov-card-wrapper'>";
        $player_cards .= "<a class='player-ov-card' href='/uniliga/spieler' onclick='popup_player(\"".$player["OPL_ID"]."\",true)'>";
        $player_cards .= "<span>".$player["name"]."</span>";
        if ($player["riotID_name"] != null) {
            $player_cards .= "<div class='divider'></div>";
            $player_cards .= "<span>".$player["riotID_name"]."#".$player["riotID_tag"]."</span>";
        }
        $player_cards .= "</a>";
        if ($remove_from_recents) {
            $player_cards .= "<a class='x-remove-recent-player' href='/uniliga/spieler' onclick='remove_recent_player(\"".$player["OPL_ID"]."\")'><div class='material-symbol'>".file_get_contents(dirname(__FILE__)."/../icons/material/close.svg")."</div></a>";
        }
        $player_cards .= "</div>";
    }

    echo $player_cards;
}

function search_all(mysqli $dbcn, string $search_input) {
	$input_array = str_split($search_input);
	$input_array = implode("%",$input_array);
	$players = $dbcn->execute_query("SELECT OPL_ID, name, riotID_name, riotID_tag FROM players WHERE riotID_name LIKE ? OR name LIKE ?",["%".$input_array."%","%".$input_array."%"])->fetch_all(MYSQLI_ASSOC);
	$teams = $dbcn->execute_query("SELECT OPL_ID, name FROM teams WHERE name LIKE ?",["%".$input_array."%"])->fetch_all(MYSQLI_ASSOC);

	$remaining_search_results = array();
	foreach ($players as $player) {
		$player["type"] = "player";
		$remaining_search_results[$player["OPL_ID"]] = $player;
	}
	foreach ($teams as $team) {
		$team["type"] = "team";
		$remaining_search_results[$team["OPL_ID"]] = $team;
	}

	$starting_hits = array();
	foreach ($remaining_search_results as $i=>$result) {
		if (str_starts_with(strtolower($result["name"]), strtolower($search_input)) || strpos(strtolower($result["name"]), " ".strtolower($search_input))) {
			$starting_hits[] = $result;
			unset($remaining_search_results[$i]);
		}
	}

	$contain_hits = array();
	foreach ($remaining_search_results as $i=>$result) {
		if (strpos(strtolower($result["name"]), strtolower($search_input))) {
			$contain_hits[] = $result;
			unset($remaining_search_results[$i]);
		}
	}

	$compare_searchresults = function($a,$b) use ($search_input) {
		if ($a["type"] == "player") {
			$a_compare = min(levenshtein($search_input,$a["name"]??""), levenshtein($search_input,$a["riotID_name"]??""));
		} else {
			$a_compare = levenshtein($search_input,$a["name"]??"");
		}
		if ($b["type"] == "player") {
			$b_compare = min(levenshtein($search_input,$b["name"]??""), levenshtein($search_input,$b["riotID_name"]??""));
		} else {
			$b_compare = levenshtein($search_input,$b["name"]??"",1,1,1);
		}
		return $a_compare <=> $b_compare;
	};

	usort($starting_hits, $compare_searchresults);
	usort($contain_hits, $compare_searchresults);
	usort($remaining_search_results, $compare_searchresults);

	$all_results = array_merge($starting_hits,$contain_hits);
	foreach ($remaining_search_results as $result) {
		$all_results[] = $result;
	}

	return $all_results;
}

function create_teamcard(mysqli $dbcn, $teamID, $tournamentID) {
	$logo_filename = is_light_mode() ? "logo_light.webp" : "logo.webp";
	$logo_filename_square = is_light_mode() ? "logo_light_square.webp" : "logo_square.webp";
	$team = $dbcn->execute_query("SELECT * FROM teams WHERE OPL_ID = ?", [$teamID])->fetch_assoc();
	$team_in_tournament = $dbcn->execute_query("SELECT * FROM teams_in_tournaments WHERE OPL_ID_team = ? AND OPL_ID_group = ?", [$teamID,$tournamentID])->fetch_assoc();
	$tournament = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID=?",[$tournamentID])->fetch_assoc();
	if ($tournament["eventType"] == "group") {
		$league = $dbcn->execute_query("SELECT * FROM tournaments WHERE eventType = 'league' AND OPL_ID=?",[$tournament["OPL_ID_parent"]])->fetch_assoc();
		$parent_tournament = $dbcn->execute_query("SELECT * FROM tournaments WHERE eventType = 'tournament' AND OPL_ID = ?",[$league["OPL_ID_parent"]])->fetch_assoc();
	} elseif ($tournament["eventType"] == "league" && $tournament["format"] == "swiss") {
		$league = $tournament;
		$parent_tournament = $dbcn->execute_query("SELECT * FROM tournaments WHERE eventType = 'tournament' AND OPL_ID = ?",[$tournament["OPL_ID_top_parent"]])->fetch_assoc();
	} elseif ($tournament["eventType"] == "wildcard") {
		$league = $tournament;
		$parent_tournament = $dbcn->execute_query("SELECT * FROM tournaments WHERE eventType = 'tournament' AND OPL_ID = ?",[$tournament["OPL_ID_top_parent"]])->fetch_assoc();
	} else {
		//TODO: playoffs
		return "";
	}
	$team_name_current = $dbcn->execute_query("SELECT * FROM team_name_history WHERE OPL_ID_team = ? AND (update_time < ? OR ? IS NULL) ORDER BY update_time DESC",[$teamID,$tournament["dateEnd"],$tournament["dateEnd"]])->fetch_assoc();
	$team_logo_current = $dbcn->execute_query("SELECT * FROM team_logo_history WHERE OPL_ID_team = ? AND (update_time < ? OR ? IS NULL) ORDER BY update_time DESC",[$teamID,$tournament["dateEnd"],$tournament["dateEnd"]])->fetch_assoc();
	$team_season_rank = $dbcn->execute_query("SELECT * FROM teams_tournament_rank WHERE OPL_ID_team = ? AND OPL_ID_tournament = ? AND second_ranked_split = FALSE",[$teamID,$tournament["OPL_ID"]])->fetch_assoc();
	$rank_tier = strtolower($team_season_rank["avg_rank_tier"]??"");
	$rank_div = $team_season_rank["avg_rank_div"]??null;
	$players = $dbcn->execute_query("SELECT * FROM players_in_teams_in_tournament ptt LEFT JOIN players p ON ptt.OPL_ID_player = p.OPL_ID LEFT JOIN stats_players_teams_tournaments sptt ON p.OPL_ID = sptt.OPL_ID_player AND sptt.OPL_ID_tournament = ptt.OPL_ID_tournament AND sptt.OPL_ID_team = ptt.OPL_ID_team WHERE ptt.OPL_ID_team = ? AND ptt.OPL_ID_tournament = ?", [$teamID,$parent_tournament["OPL_ID"]])->fetch_all(MYSQLI_ASSOC);
	$player_amount = count($players);
	$players_by_role = ["top"=>[],"jungle"=>[],"middle"=>[],"bottom"=>[],"utility"=>[],"none"=>[]];
	foreach ($players as $player) {
		if ($player["roles"] == null) {
			$players_by_role["none"][] = $player;
			continue;
		}
		$roles = json_decode($player["roles"],true);
		asort($roles);
		$roles = array_reverse($roles);
		$player["roles"] = $roles;
		if ($roles[array_key_first($roles)] == 0) {
			$players_by_role["none"][] = $player;
			continue;
		}
		$players_by_role[array_key_first($roles)][] = $player;
	}

	$result = "<div class='team-card'>";
	// Turnier-Titel
	$result .= "<a class='team-card-div team-card-tournament' href='turnier/{$parent_tournament["OPL_ID"]}'>";
	if ($tournament["OPL_ID_logo"] != NULL) {
		$result .= "<img class='color-switch' alt='' src='img/tournament_logos/{$tournament["OPL_ID_logo"]}/$logo_filename'>";
	}
	$result .= ucfirst($tournament["split"])." ".$tournament["season"];
	$result .= "</a>";

	// Liga und Gruppe
	$league_number = ($league["numberRangeTo"] == NULL) ? $league["number"] : $league["number"]."-".$league["numberRangeTo"];
	$group_title = ($tournament["eventType"] == "wildcard") ? "Wildcard-Turnier" : (($tournament["format"] == "swiss") ? "Swiss-Gruppe" : "Gruppe {$tournament["number"]}");
	$group_url_segment = ($tournament["eventType"] == "wildcard") ? "wildcard" : "gruppe";
	$result .= "<a class='team-card-div team-card-league' href='turnier/{$parent_tournament["OPL_ID"]}/$group_url_segment/{$tournament["OPL_ID"]}'>";
	$result .= "Liga ".$league_number." - $group_title";
	$result .= "</a>";

	// Link zu Teamseite
	$logo_dir = (($team_logo_current["dir_key"]??-1) == -1) ? "" : $team_logo_current["dir_key"]."/";
	$result .= "<a class='team-card-div team-card-teampage' href='turnier/{$parent_tournament["OPL_ID"]}/team/$teamID'>";
	if ($team["OPL_ID_logo"] != NULL && file_exists(dirname(__FILE__)."/../img/team_logos/{$team["OPL_ID_logo"]}/$logo_dir$logo_filename_square")) {
		$result .= "<img class='color-switch' alt='' src='img/team_logos/{$team["OPL_ID_logo"]}/$logo_dir$logo_filename_square'>";
		$result .= "<span>{$team_name_current["name"]}</span>";
	} else {
		$result .= "<span class='team-card-nologo'>{$team_name_current["name"]}</span>";
	}
	$result .= "</a>";

	// Standing
	if (($team_in_tournament["standing"]??null) != null) {
		$result .= "<div class='team-card-div team-card-standings'>{$team_in_tournament["standing"]}.Platz : {$team_in_tournament["wins"]}-{$team_in_tournament["draws"]}-{$team_in_tournament["losses"]}</div>";
	}

	// Rang
	if (($team_season_rank["avg_rank_tier"]??null) != null) {
		$result .= "<div class='team-card-div team-card-rank'><img class='rank-emblem-mini' src='ddragon/img/ranks/mini-crests/$rank_tier.svg' alt='".ucfirst($rank_tier)."'>".ucfirst($rank_tier)." $rank_div</div>";
	}

	// Spieler
	$result .= "<button type='button' class='team-card-div team-card-playeramount'><div class='material-symbol'>". file_get_contents(__DIR__."/../icons/material/person.svg") ."</div>$player_amount Spieler</button>";
	$result .= "<div class='team-card-div team-card-players-wrapper'>";
	$result .= "<div class='team-card-players'>";
	foreach ($players_by_role as $role_players) {
		foreach ($role_players as $player) {
			$result .= "<a class='fancy-link-underline-parent' href='spieler/{$player["OPL_ID"]}'>";
			if ($player["roles"] != null) {
				$result .= "<div class='team-card-players-roles'>";
				foreach ($player["roles"] as $role=>$role_amount) {
					if ($role_amount == 0) continue;
					$result.= "<span class='svg-wrapper role'>".file_get_contents(__DIR__."/../ddragon/img/positions/position-$role-light.svg")."</span>";
				}
				$result .= "</div>";
			}
			$result .= "<span class='fancy-link-underline-target'>{$player["name"]}</span></a>";
		}
	}
	$result .= "</div>";
	$result .= "</div>";

	$result .= "</div>";
	return $result;
}