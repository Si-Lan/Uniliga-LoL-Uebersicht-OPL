<?php
include_once __DIR__.'/../setup/data.php';
include __DIR__.'/../functions/fe-functions.php';

$dbcn = create_dbcn();

if ($dbcn -> connect_error) exit("Database Connection failed");

$tournamentID = $_SERVER['HTTP_TOURNAMENTID'] ?? $_GET['tournament'] ?? NULL;
if ($tournamentID == NULL) exit;

$type = $_SERVER['HTTP_TYPE'] ?? $_GET['type'] ?? NULL;
if ($type == NULL) exit;

$leagues = $dbcn->execute_query("SELECT * FROM tournaments WHERE eventType='league' AND OPL_ID_parent = ? ORDER BY Number",[$tournamentID])->fetch_all(MYSQLI_ASSOC);
$teams = $dbcn->execute_query("SELECT t.OPL_ID, t.name, t.OPL_ID_logo, tsr.avg_rank_div, tsr.avg_rank_tier, tsr.avg_rank_num, g.OPL_ID AS OPL_ID_group, l.OPL_ID AS OPL_ID_league, g.number AS number_group, l.number AS number_league
											FROM teams t 
    											JOIN teams_in_tournaments tit ON t.OPL_ID = tit.OPL_ID_team
    												JOIN tournaments g ON tit.OPL_ID_group = g.OPL_ID
    													JOIN tournaments l ON g.OPL_ID_parent = l.OPL_ID
											LEFT JOIN teams_tournament_rank as tsr ON tsr.OPL_ID_team = t.OPL_ID AND tsr.OPL_ID_tournament = ? AND second_ranked_split = FALSE
    										WHERE l.OPL_ID_parent = ?
	    										AND t.OPL_ID <> -1
    										ORDER BY avg_rank_num DESC", [$tournamentID, $tournamentID])->fetch_all(MYSQLI_ASSOC);

if ($type == "all") {
	echo generate_elo_list($dbcn,$type,$teams,$tournamentID,NULL,NULL);
} elseif ($type == "div") {
	foreach ($leagues as $league) {
		$teams_of_div = $dbcn->execute_query("SELECT t.OPL_ID, t.name, t.OPL_ID_logo, tsr.avg_rank_div, tsr.avg_rank_tier, tsr.avg_rank_num, g.OPL_ID AS OPL_ID_group, l.OPL_ID AS OPL_ID_league, g.number AS number_group, l.number AS number_league
													FROM teams t 
    													JOIN teams_in_tournaments tit ON t.OPL_ID = tit.OPL_ID_team
    														JOIN tournaments g ON tit.OPL_ID_group = g.OPL_ID
    															JOIN tournaments l ON g.OPL_ID_parent = l.OPL_ID
													LEFT JOIN teams_tournament_rank as tsr ON tsr.OPL_ID_team = t.OPL_ID AND tsr.OPL_ID_tournament = ? AND second_ranked_split = FALSE
    												WHERE l.OPL_ID = ?
														AND t.OPL_ID <> -1
    												ORDER BY avg_rank_num DESC", [$tournamentID,$league["OPL_ID"]])->fetch_all(MYSQLI_ASSOC);
		echo generate_elo_list($dbcn,$type,$teams_of_div,$tournamentID,$league,NULL);
	}
} elseif ($type == "group") {
	foreach ($leagues as $league) {
		$groups_of_div = $dbcn->execute_query("SELECT * FROM tournaments WHERE eventType='group' AND OPL_ID_parent = ? ORDER BY Number",[$league['OPL_ID']])->fetch_all(MYSQLI_ASSOC);
		foreach ($groups_of_div as $group) {
			$teams_of_group = $dbcn->execute_query("SELECT t.OPL_ID, t.name, t.OPL_ID_logo, tsr.avg_rank_div, tsr.avg_rank_tier, tsr.avg_rank_num, g.OPL_ID AS OPL_ID_group, l.OPL_ID AS OPL_ID_league, g.number AS number_group, l.number AS number_league
													FROM teams t 
    													JOIN teams_in_tournaments tit ON t.OPL_ID = tit.OPL_ID_team
    														JOIN tournaments g ON tit.OPL_ID_group = g.OPL_ID
    															JOIN tournaments l ON g.OPL_ID_parent = l.OPL_ID
													LEFT JOIN teams_tournament_rank as tsr ON tsr.OPL_ID_team = t.OPL_ID AND tsr.OPL_ID_tournament =  ? AND second_ranked_split = FALSE
    												WHERE g.OPL_ID = ?
    													AND t.OPL_ID <> -1
    												ORDER BY avg_rank_num DESC", [$tournamentID, $group["OPL_ID"]])->fetch_all(MYSQLI_ASSOC);
			echo generate_elo_list($dbcn,$type,$teams_of_group,$tournamentID,$league,$group);
		}
	}
}
$dbcn->close();