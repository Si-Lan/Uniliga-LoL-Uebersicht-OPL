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
	$result .= "<link rel='stylesheet' href='/assets/css/design2.css?5'>";
	$result .= "<script src='/assets/js/jquery-3.7.1.min.js'></script>";
	$result .= "<script src='/assets/js/main.js?5'></script>";
	// additional css
	if (in_array("elo",$css)) {
		$result .= "<link rel='stylesheet' href='/assets/css/elo-rank-colors.css'>";
	}
	if (in_array("game",$css)) {
		$result .= "<link rel='stylesheet' href='/assets/css/game.css'>";
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
	$outlinkicon = file_get_contents(dirname(__DIR__,2)."/public/assets/icons/material/open_in_new.svg");
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
		".file_get_contents(dirname(__DIR__,2)."/public/assets/icons/material/home.svg")."
	</a>";
	}
	$searchbar = "";
	if ($search_button) {
		$searchbar = "
		<div class='searchbar'>
			<span class='material-symbol search-icon' title='Suche'>
				".file_get_contents(dirname(__DIR__,2)."/public/assets/icons/material/search.svg")."
			</span>
			<input class='search-all deletable-search' placeholder='Suche' type='search'>
			<button class='material-symbol search-clear' title='Suche leeren'>
				".file_get_contents(dirname(__DIR__,2)."/public/assets/icons/material/close.svg")."
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

	$result .= "<button type='button' class='material-symbol settings-button'>". file_get_contents(dirname(__DIR__,2)."/public/assets/icons/material/tune.svg") ."</button>";
	if ($loggedin) {
		$result .= "
			<div class='settings-menu'>
				<a class='settings-option toggle-mode' href=''><div class='material-symbol'>". file_get_contents(dirname(__DIR__,2)."/public/assets/icons/material/{$colormode}_mode.svg") ."</div></a>
				<a class='settings-option toor-write' href='/admin'>Admin<div class='material-symbol'>". file_get_contents(dirname(__DIR__,2)."/public/assets/icons/material/edit_square.svg") ."</div></a>
				<a class='settings-option rgapi-write' href='/admin/rgapi'>RGAPI<div class='material-symbol'>". file_get_contents(dirname(__DIR__,2)."/public/assets/icons/material/videogame_asset.svg") ."</div></a>
				<a class='settings-option ddragon-write' href='/admin/ddragon'>DDragon<div class='material-symbol'>". file_get_contents(dirname(__DIR__,2)."/public/assets/icons/material/photo_library.svg") ."</div></a>
				<a class='settings-option update-log' href='/admin/updates'>Update-Logs</a>
				<a class='settings-option logout' href='?logout'>Logout<div class='material-symbol'>". file_get_contents(dirname(__DIR__,2)."/public/assets/icons/material/logout.svg") ."</div></a>
			</div>";
	} else {
		$result .= "
			<div class='settings-menu'>
				<a class='settings-option toggle-mode' href=''><div class='material-symbol'>". file_get_contents(dirname(__DIR__,2)."/public/assets/icons/material/{$colormode}_mode.svg") ."</div></a>
				<a class='settings-option github-link' href='https://github.com/Si-Lan/Uniliga-LoL-Uebersicht-OPL' target='_blank'>GitHub<div class='material-symbol'>". file_get_contents(dirname(__DIR__,2)."/public/img/github-mark-white.svg") ."</div></a>
				<a class='settings-option' href='https://ko-fi.com/silencelol' target='_blank'>Spenden<div class='material-symbol'>". file_get_contents(dirname(__DIR__,2)."/public/assets/icons/material/payments.svg") ."</div></a>
				<a class='settings-option feedback' href=''>Feedback<div class='material-symbol'>". file_get_contents(dirname(__DIR__,2)."/public/assets/icons/material/mail.svg") ."</div></a>
				<a class='settings-option login' href='?login'>Login<div class='material-symbol'>". file_get_contents(dirname(__DIR__,2)."/public/assets/icons/material/login.svg") ."</div></a>
			</div>";
	}
	$result .= "</header>";

	$result .= "
		<dialog id='login-dialog' class='dismissable-popup $loginopen'>
			<div class='dialog-content'>
				<button class='close-popup'><span class='material-symbol'>".file_get_contents(dirname(__DIR__,2)."/public/assets/icons/material/close.svg")."</span></button>
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

function create_dropdown(string $type, array $items):string {
	$first_key = array_key_first($items);
	$result = "<div class='button-dropdown-wrapper'>";
	$result .= "<button type='button' class='button-dropdown' data-dropdowntype='$type'>{$items[$first_key]}<span class='material-symbol'>".file_get_contents(dirname(__DIR__,2)."/public/assets/icons/material/expand_more.svg")."</span></button>";
	$result .= "<div class='dropdown-selection'>";
	foreach ($items as $data_name=>$name) {
		$selected = ($data_name == $first_key) ? "selected-item" : "";
		$result .= "<button type='button' class='dropdown-selection-item $selected' data-selection='$data_name'>$name</button>";
	}
	$result .= "</div>";
	$result .=  "</div>"; // button-dropdown-wrapper
	return $result;
}