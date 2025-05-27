<?php
include_once(__DIR__."/helper.php");

function create_html_head_elements(array $css = [], array $js = [], string $title="Uniliga LoL - Übersicht", bool $head_wrap = TRUE, bool $loggedin=FALSE):string {
	$result = "";
	if ($head_wrap) {
		$result .= "<head>";
	}

	// basic content
	$result .= "<meta name='viewport' content='width=device-width, initial-scale=1'>";
	$result .= "<link rel='icon' href='https://silence.lol/favicon-dark.ico' media='(prefers-color-scheme: dark)'/>";
	$result .= "<link rel='icon' href='https://silence.lol/favicon-light.ico' media='(prefers-color-scheme: light)'/>";
	$result .= "<link rel='stylesheet' href='/styles/design2.css?5'>";
	$result .= "<script src='/scripts/jquery-3.7.1.min.js'></script>";
	$result .= "<script src='/scripts/main.js?5'></script>";
	// additional css
	if (in_array("elo",$css)) {
		$result .= "<link rel='stylesheet' href='/styles/elo-rank-colors.css'>";
	}
	if (in_array("game",$css)) {
		$result .= "<link rel='stylesheet' href='/styles/game.css'>";
	}
	if (in_array("admin",$css)) {
		$result .= "<link rel='stylesheet' href='/admin/styles/style.css'>";
	}
	if (in_array("rgapi",$css)) {
		$result .= "<link rel='stylesheet' href='/admin/styles/rgapi.css'>";
	}
	// additional js
	if (in_array("rgapi", $js) && $loggedin) {
		$result .= "<script src='/admin/scripts/rgapi.js'></script>";
	}
	if (in_array("admin", $js) && $loggedin) {
		$result .= "<script src='/admin/scripts/main.js'></script>";
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
	include_once(dirname(__DIR__,2) . "/config/data.php");
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
	include_once dirname(__DIR__,2) . "/config/data.php";
	$loginforminfo = "";
	$password = get_admin_pass();
	if (isset($_GET["login"]) && isset($_POST["keypass"]) && $_POST["keypass"] != $password) {
		$open_login = true;
		$loginforminfo = "Falsches Passwort";
	}

	$loginopen = $open_login ? "modalopen_auto" : "";

	$result = "";

	$opl_event_url = "https://www.opleague.pro/event";
	$outlinkicon = file_get_contents(dirname(__DIR__,2)."/public/icons/material/open_in_new.svg");
	if ($dbcn != NULL && $tournament_id != NULL) {
		$tournament = $dbcn->execute_query("SELECT * FROM tournaments WHERE OPL_ID = ?",[$tournament_id])->fetch_assoc();
		$t_name_clean = preg_replace("/LoL\s/i","",$tournament["name"]);
	}

	$loggedin = is_logged_in();
	$colormode = is_light_mode() ? "light" : "dark";

	if (file_exists(dirname(__DIR__,2) . "/config/maintenance.enable") && $loggedin) $result .= "<div style='text-align: center; padding: 5px 0; background-color: #7e1616'>Achtung: Wartungsmodus ist aktiviert!</div>";

	$result .= "<header class='$title'>";
	if ($home_button) {
		$result .= "
	<a href='/' class='button material-symbol'>
		".file_get_contents(dirname(__DIR__,2)."/public/icons/material/home.svg")."
	</a>";
	}
	$searchbar = "";
	if ($search_button) {
		$searchbar = "
		<div class='searchbar'>
			<span class='material-symbol search-icon' title='Suche'>
				".file_get_contents(dirname(__DIR__,2)."/public/icons/material/search.svg")."
			</span>
			<input class='search-all deletable-search' placeholder='Suche' type='search'>
			<button class='material-symbol search-clear' title='Suche leeren'>
				".file_get_contents(dirname(__DIR__,2)."/public/icons/material/close.svg")."
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
			$title_text .= "404 - Nicht gefunden";
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

	$result .= "<button type='button' class='material-symbol settings-button'>". file_get_contents(dirname(__DIR__,2)."/public/icons/material/tune.svg") ."</button>";
	if ($loggedin) {
		$result .= "
			<div class='settings-menu'>
				<a class='settings-option toggle-mode' href=''><div class='material-symbol'>". file_get_contents(dirname(__DIR__,2)."/public/icons/material/{$colormode}_mode.svg") ."</div></a>
				<a class='settings-option toor-write' href='/admin'>Admin<div class='material-symbol'>". file_get_contents(dirname(__DIR__,2)."/public/icons/material/edit_square.svg") ."</div></a>
				<a class='settings-option rgapi-write' href='/admin/rgapi'>RGAPI<div class='material-symbol'>". file_get_contents(dirname(__DIR__,2)."/public/icons/material/videogame_asset.svg") ."</div></a>
				<a class='settings-option ddragon-write' href='/admin/ddragon'>DDragon<div class='material-symbol'>". file_get_contents(dirname(__DIR__,2)."/public/icons/material/photo_library.svg") ."</div></a>
				<a class='settings-option update-log' href='/admin/updates'>Update-Logs</a>
				<a class='settings-option logout' href='?logout'>Logout<div class='material-symbol'>". file_get_contents(dirname(__DIR__,2)."/public/icons/material/logout.svg") ."</div></a>
			</div>";
	} else {
		$result .= "
			<div class='settings-menu'>
				<a class='settings-option toggle-mode' href=''><div class='material-symbol'>". file_get_contents(dirname(__DIR__,2)."/public/icons/material/{$colormode}_mode.svg") ."</div></a>
				<a class='settings-option github-link' href='https://github.com/Si-Lan/Uniliga-LoL-Uebersicht-OPL' target='_blank'>GitHub<div class='material-symbol'>". file_get_contents(dirname(__DIR__,2)."/public/img/github-mark-white.svg") ."</div></a>
				<a class='settings-option' href='https://ko-fi.com/silencelol' target='_blank'>Spenden<div class='material-symbol'>". file_get_contents(dirname(__DIR__,2)."/public/icons/material/payments.svg") ."</div></a>
				<a class='settings-option feedback' href=''>Feedback<div class='material-symbol'>". file_get_contents(dirname(__DIR__,2)."/public/icons/material/mail.svg") ."</div></a>
				<a class='settings-option login' href='?login'>Login<div class='material-symbol'>". file_get_contents(dirname(__DIR__,2)."/public/icons/material/login.svg") ."</div></a>
			</div>";
	}
	$result .= "</header>";

	$result .= "
		<dialog id='login-dialog' class='dismissable-popup $loginopen'>
			<div class='dialog-content'>
				<button class='close-popup'><span class='material-symbol'>".file_get_contents(dirname(__DIR__,2)."/public/icons/material/close.svg")."</span></button>
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
				<a href='/turnier/{$tournament_id}' class='button$overview'>
    	        	<div class='material-symbol'>". file_get_contents(dirname(__DIR__,2)."/public/icons/material/sports_esports.svg") ."</div>
        		    Turnier
            	</a>
	            <a href='/turnier/{$tournament_id}/teams$teamlink_addition' class='button$list'>
    	        	<div class='material-symbol'>". file_get_contents(dirname(__DIR__,2)."/public/icons/material/group.svg") ."</div>
        	        Teams
            	</a>
	            <a href='/turnier/{$tournament_id}/elo' class='button$elo'>
    	            <div class='material-symbol'>". file_get_contents(dirname(__DIR__,2)."/public/icons/material/stars.svg") ."</div>
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
	$ranked_split_text = ($ranked_split > 0) ? "Split $ranked_split" : "";
	$ranked_season_comb = "$ranked_season-$ranked_split";
	$next_split = get_second_ranked_split_for_tournament($dbcn,$tournament_id);
	$ranked_season_2 = $next_split["season"] ?? null;
	$ranked_split_2 = $next_split["split"] ?? null;
	$ranked_split_2_text = (($ranked_split_2??0) > 0) ? "Split $ranked_split_2" : "";
	$ranked_season_comb_2 = "$ranked_season_2-$ranked_split_2";

	$current_split = get_current_ranked_split($dbcn, $tournament_id);
	$current_split_show = explode("-",$current_split);
	if ($current_split_show[1] == "0") {
		$current_split_show = $current_split_show[0];
	} else {
		$current_split_show = $current_split;
	}

	$button1_checked = ($current_split == $ranked_season_comb) ? "checked" : "";
	$button2_checked = ($current_split == $ranked_season_comb_2) ? "checked" : "";

	$result .= "<div class='ranked-settings-wrapper'>";
	$result .= "<button type='button' class='ranked-settings'><span>$current_split_show</span><img src='/ddragon/img/ranks/emblems/unranked.webp' alt='Rank-Einstellungen'></button>";
	$result .= "<div class='ranked-settings-popover'>
					<span>Angezeigter Rang</span>
					<div>
						<input type='radio' id='ranked-split-radio-1' value='$ranked_season-$ranked_split' name='ranked-split' data-tournament='$tournament_id' $button1_checked>
						<label for='ranked-split-radio-1'>Season $ranked_season $ranked_split_text</label>
					</div>";
	if ($next_split != null) $result .= "
					<div>
						<input type='radio' id='ranked-split-radio-2' value='$ranked_season_2-$ranked_split_2' name='ranked-split' data-tournament='$tournament_id' $button2_checked>
						<label for='ranked-split-radio-2'>Season $ranked_season_2 $ranked_split_2_text</label>
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
		if ($team['OPL_ID_logo'] != NULL && file_exists(dirname(__DIR__,2)."/public/$local_team_img{$team['OPL_ID_logo']}/logo.webp")) {
			$results .= "
                                <img class='color-switch' src='/$local_team_img{$team['OPL_ID_logo']}/$logo_filename' alt='Teamlogo'>";
		} else {
			$results .= "<img class='color-switch' src='data:image/gif;base64,R0lGODlhAQABAAD/ACwAAAAAAQABAAACADs%3D' alt='Teamlogo'>";
		}
		$results .= "
                                <span class='page-link-target'>
                                	<span class='team-name'>{$team_name['name']}</span>
                                	<span class='material-symbol page-link-icon popup-icon'>
                                		".file_get_contents(dirname(__DIR__,2)."/public/icons/material/ad_group.svg")."
									</span>
                                </span>
                            </button>
                            <div class='elo-list-item rank'>";
		if ($team['avg_rank_tier'] != NULL) {
			$avg_rank = strtolower($team['avg_rank_tier']);
			$avg_rank_cap = ucfirst($avg_rank);
			$avg_rank_num = round($team['avg_rank_num'], 2);
			$results .= "
                                <img class='rank-emblem-mini' src='/ddragon/img/ranks/mini-crests/{$avg_rank}.svg' alt='$avg_rank_cap'>
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
	$local_team_img = "/img/team_logos/";
    $logo_filename = is_light_mode() ? "logo_light.webp" : "logo.webp";
	$opl_team_url = "https://www.opleague.pro/team/";
	$team_id = $team['OPL_ID'];
	$result .= "<div class='team pagetitle'>";
	if ($team['OPL_ID_logo'] != NULL && file_exists(dirname(__DIR__,2)."/public/$local_team_img{$team['OPL_ID_logo']}/logo.webp")) {
		$result .= "<img class='color-switch' alt src='$local_team_img{$team['OPL_ID_logo']}/$logo_filename'>";
	}
	$result .= "
			<div>
				<h2 class='pagetitle'><a class='page-link' href='/team/$team_id'><span class='link-text'>{$team['name']}</span><span class='material-symbol page-link-icon'>".file_get_contents(dirname(__DIR__,2)."/public/icons/material/chevron_right.svg")."</span></a></h2>
				<a href=\"$opl_team_url$team_id\" class='opl-link' target='_blank'><span class='material-symbol'>". file_get_contents(dirname(__DIR__,2)."/public/icons/material/open_in_new.svg") ."</span></a>
			</div>";
	$data_playoff = ($playoffID != null) ? "data-playoff='$playoffID'" : "";
	if ($active == "details" && !$hide_update) {
		$result .= "
				<div class='updatebuttonwrapper'>
           			<button type='button' class='user_update user_update_team update_data' data-team='$team_id' data-tournament='$tournamentID' data-group='$groupID' data-groups='$allGroupIDs' $data_playoff><div class='material-symbol'>".file_get_contents(dirname(__DIR__,2)."/public/icons/material/sync.svg")."</div></button>
					<span class='last-update'>letztes Update:<br>$updatediff</span>
				</div>";
	}
	$result .= "</div>";
	$result .= "
        <nav class='team-titlebutton-wrapper'>
           	<a href='/turnier/$tournamentID/team/$team_id' class='$details_a'><div class='material-symbol'>". file_get_contents(dirname(__DIR__,2)."/public/icons/material/info.svg") ."</div>Team-Übersicht</a>
           	<a href='/turnier/$tournamentID/team/$team_id/matchhistory' class='$matchhistory_a'><div class='material-symbol'>". file_get_contents(dirname(__DIR__,2)."/public/icons/material/manage_search.svg") ."</div>Match-History</a>
            <a href='/turnier/$tournamentID/team/$team_id/stats' class='$stats_a'><div class='material-symbol'>". file_get_contents(dirname(__DIR__,2)."/public/icons/material/monitoring.svg") ."</div>Statistiken</a>
        </nav>";
	return $result;
}

function create_dropdown(string $type, array $items):string {
	$first_key = array_key_first($items);
	$result = "<div class='button-dropdown-wrapper'>";
	$result .= "<button type='button' class='button-dropdown' data-dropdowntype='$type'>{$items[$first_key]}<span class='material-symbol'>".file_get_contents(dirname(__DIR__,2)."/public/icons/material/expand_more.svg")."</span></button>";
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
		$svg_code = file_get_contents(dirname(__DIR__,2)."/public/icons/material/expand_more.svg");
	} else {
		$svg_code = file_get_contents(dirname(__DIR__,2)."/public/icons/material/check_indeterminate_small.svg");
	}
	return "<span class='tooltip'>$maintext<span class='tooltiptext'>$tooltiptext</span><div class='material-symbol sort-direction'>".$svg_code."</div></span>";
}