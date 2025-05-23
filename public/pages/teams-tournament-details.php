<?php

use App\Components\Cards\SummonerCardContainer;
use App\Components\Matches\MatchButtonList;
use App\Components\MultiOpggButton;
use App\Components\Navigation\Header;
use App\Components\Navigation\SwitchTournamentStageButtons;
use App\Components\Navigation\TeamHeaderNav;
use App\Components\Navigation\TournamentNav;
use App\Components\Standings\StandingsTable;
use App\Components\Team\TeamRankDisplay;
use App\Enums\EventType;
use App\Enums\HeaderType;
use App\Page\PageMeta;
use App\Repositories\TeamInTournamentRepository;
use App\Repositories\TeamInTournamentStageRepository;
use App\Utilities\EntitySorter;
use App\Utilities\UserContext;

$teamInTournamentRepo = new TeamInTournamentRepository();
$teamInTournamentStageRepo = new TeamInTournamentStageRepository();

$teamInTournament = $teamInTournamentRepo->findByTeamIdAndTournamentId($_GET["team"], $_GET["tournament"]);

// alle Gruppen / Wildcard-Turniere / Playoffs, in denen das Team spielt, holen und sortieren
$teamInTournamentStages = $teamInTournamentStageRepo->findAllbyTeamInTournament($teamInTournament);
$teamInTournamentStages = EntitySorter::sortTeamInTournamentStages($teamInTournamentStages);

// Über Routing ist bereits klar, dass Team und Turnier existieren, hier wird noch geprüft, ob das Team auch im Turnier spielt
if (count($teamInTournamentStages) === 0) {
	trigger404("team-in-tournament");
    exit();
}

// initial neueste Stage auswählen, die nicht Playoffs ist
$teamInTournamentStage = end($teamInTournamentStages);
foreach ($teamInTournamentStages as $teamInTournamentStageInLoop) {
    if ($teamInTournamentStageInLoop->tournamentStage->eventType !== EventType::PLAYOFFS) {
        $teamInTournamentStage = $teamInTournamentStageInLoop;
    }
}

$pageMeta = new PageMeta(
	title: $teamInTournament->nameInTournament." | ".$teamInTournament->tournament->getShortName(),
	css: ['game'],
	bodyClass: 'team'
);

?>

<?= new Header(HeaderType::TOURNAMENT, $teamInTournament->tournament)?>

<?= new TournamentNav($teamInTournament->tournament)?>

<?= new TeamHeaderNav($teamInTournamentStage->teamInRootTournament, "details") ?>

<main>
    <div class='player-cards opgg-cards'>
        <div class='title'>
            <h3>Spieler</h3>

            <?= new MultiOpggButton($teamInTournament) ?>

            <?php
            if (UserContext::summonerCardCollapsed()) {
                echo "<button type='button' class='exp_coll_sc'><div class='material-symbol'>".file_get_contents(__DIR__."/../icons/material/unfold_more.svg")."</div>Stats ein</button>";
            } else {
                echo "<button type='button' class='exp_coll_sc'><div class='material-symbol'>".file_get_contents(__DIR__."/../icons/material/unfold_less.svg")."</div>Stats aus</button>";
            }
            ?>

            <?= new TeamRankDisplay($teamInTournament,true) ?>

        </div>
        <?= new SummonerCardContainer($teamInTournament) ?>
    </div>

    <?= new SwitchTournamentStageButtons($teamInTournamentStages, $teamInTournamentStage) ?>

    <div class='inner-content'>
        <?= new StandingsTable($teamInTournamentStage->tournamentStage,$teamInTournament->team) ?>

        <?= new MatchButtonList($teamInTournamentStage->tournamentStage,$teamInTournament) ?>

        <?php
/* TODO: Component für Popups erstellen und für MatchButtons einbinden
$curr_matchID = $_GET['match'] ?? NULL;
if ($curr_matchID != NULL) {
	$curr_matchData = $dbcn->execute_query("SELECT * FROM matchups WHERE OPL_ID = ?",[$curr_matchID])->fetch_assoc();
	$curr_games = $dbcn->execute_query("SELECT * FROM games g JOIN games_to_matches gtm on g.RIOT_matchID = gtm.RIOT_matchID WHERE OPL_ID_matches = ? ORDER BY g.RIOT_matchID",[$curr_matchID])->fetch_all(MYSQLI_ASSOC);
	$curr_team1 = $dbcn->execute_query("SELECT * FROM teams LEFT JOIN team_name_history tnh ON tnh.OPL_ID_team = teams.OPL_ID AND (update_time < ? OR ? IS NULL) WHERE OPL_ID = ? ORDER BY update_time DESC",[$tournament["dateEnd"],$tournament["dateEnd"],$curr_matchData['OPL_ID_team1']])->fetch_assoc();
	$curr_team2 = $dbcn->execute_query("SELECT * FROM teams LEFT JOIN team_name_history tnh ON tnh.OPL_ID_team = teams.OPL_ID AND (update_time < ? OR ? IS NULL) WHERE OPL_ID = ? ORDER BY update_time DESC",[$tournament["dateEnd"],$tournament["dateEnd"],$curr_matchData['OPL_ID_team2']])->fetch_assoc();

	if (!$tournament["archived"]) {
		$last_user_update_match = $dbcn->execute_query("SELECT last_update FROM updates_user_matchup WHERE OPL_ID_matchup = ?", [$curr_matchID])->fetch_column();

		$last_update_match = max($last_user_update_match,$last_cron_update);

		if ($last_update_match == NULL) {
			$updatediff_match = "unbekannt";
		} else {
			$last_update_match = strtotime($last_update_match);
			$currtime = time();
			$updatediff_match = max_time_from_timestamp($currtime-$last_update_match);
		}
	}

	echo "
                    <div class='mh-popup-bg' onclick='close_popup_match(event)' style='display: block; opacity: 1;'>
                        <div class='mh-popup'>
                            <button class='close-popup' onclick='closex_popup_match()'><span class='material-symbol'>". file_get_contents(__DIR__."/../icons/material/close.svg") ."</span></button>
                            <div class='close-button-space'></div>
                            <div class='mh-popup-buttons'>
	                            <a class='icon-link page-link' href='/turnier/$tournamentID/team/$teamID/matchhistory#{$curr_matchID}'>
	                            <div class='material-symbol icon-link-icon'>". file_get_contents(__DIR__."/../icons/material/manage_search.svg") ."</div>
	                            <span class='link-text'>In Matchhistory ansehen</span>
	                            <div class='material-symbol page-link-icon'>". file_get_contents(__DIR__."/../icons/material/chevron_right.svg") ."</div>	                            
	                            </a>";
	if (!$tournament["archived"]) {
		echo "                      <div class='updatebuttonwrapper'><button type='button' class='user_update user_update_match update_data' data-match='$curr_matchID' data-matchformat='' data-team='$teamID' data-group='{$group["OPL_ID"]}' data-tournament='{$tournamentID}'><div class='material-symbol'>". file_get_contents(__DIR__."/../icons/material/sync.svg") ."</div></button><span class='last-update'>letztes Update:<br>$updatediff_match</span></div>";
	}
    echo "                  </div>";

	echo "<span>Spieldatum: ".date("d.m.Y, H:i",strtotime($curr_matchData["plannedDate"]))."</span>";

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
	$t1score = $curr_matchData['team1Score'];
	$t2score = $curr_matchData['team2Score'];
	if ($t1score == -1 || $t2score == -1) {
		$t1score = ($t1score == -1) ? "L" : "W";
		$t2score = ($t2score == -1) ? "L" : "W";
	}
	echo "
                <h2 class='round-title'>
                    <span class='round'>Runde {$curr_matchData['playday']}: &nbsp</span>
                    <a href='/turnier/$tournamentID/team/{$curr_matchData['OPL_ID_team1']}' class='team $team1score page-link'>{$curr_team1['name']}</a>
                    <span class='score'><span class='$team1score'>{$t1score}</span>:<span class='$team2score'>{$t2score}</span></span>
                    <a href='/turnier/$tournamentID/team/{$curr_matchData['OPL_ID_team2']}' class='team $team2score page-link'>{$curr_team2['name']}</a>
                </h2>";
	if ($curr_games == null) {
		echo "<div class=\"no-game-found\">Keine Spieldaten gefunden</div>";
	}
	foreach ($curr_games as $game_i=>$curr_game) {
		echo "<div class='game game$game_i'>";
		$gameID = $curr_game['RIOT_matchID'];
		echo create_game($dbcn,$gameID,$teamID,$tournamentID);
		echo "</div>";
	}
	echo "
                        </div>
                    </div>";
} else {
	echo "   <div class='mh-popup-bg' onclick='close_popup_match(event)'>
                            <div class='mh-popup'></div>
                     </div>";
}
*/
        ?>
    </div>
</main>