<?php

require_once dirname(__DIR__,2) . '/bootstrap.php';

$options = getopt('', ['db_old:', 'db_new:']);
$dbOld = $options['db_old'] ?? null;
$dbNew = $options['db_new'] ?? null;

if (!$dbOld || !$dbNew) {
	echo "Fehler: Keine alte und neue Datenbank angegeben.\n";
	echo "Verwendung: php transfer_database.php --db_old=datenbankname --db_new=datenbankname\n";
	exit(1);
}

echo "Transferiere Datenbank '$dbOld' nach '$dbNew'...\n";

$dbcn = \App\Core\DatabaseConnection::getConnection();

$dbNewSelect = $dbcn->select_db($dbNew);
$dbcn->query("SET FOREIGN_KEY_CHECKS=0");
if (!$dbNewSelect) {
	echo "neue Datenbank konnte nicht gewählt werden.";
	exit(1);
}
$dbOldSelect = $dbcn->select_db($dbOld);
if (!$dbOldSelect) {
	echo "alte Datenbank konnte nicht gewählt werden.";
	exit(1);
}

$tablesSimple = [
	"games",
	"games_to_matches",
	"local_patches",
	"lol_ranked_splits",
	"matchups",
	"players",
	"players_in_teams",
	"players_in_teams_in_tournament",
	"players_season_rank",
	"stats_players_in_tournaments",
	"stats_players_teams_tournaments",
	"stats_teams_in_tournaments",
	"teams",
	"teams_in_tournament_stages", // simpel, wenn es umbenannt wurde
	"teams_season_rank_in_tournament", // simpel, wenn view implementiert ist
	"team_logo_history",
	"team_name_history",
	"updates_cron",
	"updates_user_group",
	"updates_user_matchup",
	"updates_user_team"
];
$migrators = [
	"tournaments" => "migrateTournaments",
	"tournaments_in_ranked_splits" => "migrateTournamentsInRankedSplits",
];

foreach ($tablesSimple as $table) {
	migrateSimpleTable($dbcn, $dbOld, $dbNew, $table);
}

foreach ($migrators as $table => $migrator) {
	$migrator($dbcn, $dbOld, $dbNew);
}


function migrateSimpleTable($dbcn, $dbOld, $dbNew, $table): void {
	echo "\nKopiere $table...\n";
	$dbcn->select_db($dbOld);
	$result = $dbcn->query("SELECT * FROM `$table`");
	$dbcn->select_db($dbNew);

	$total = $result->num_rows;
	$count = 0;
	$barLength = 50;
	while ($row = $result->fetch_assoc()) {
		$columns = array_keys($row);
		$values = array_map(function($v) use ($dbcn) {
			if ($v === null) return 'null';
			return "'". $dbcn->real_escape_string($v) ."'";
		}, array_values($row));
		$dbcn->query("INSERT IGNORE INTO `$table` (`" . implode('`,`', $columns) . "`) VALUES (" . implode(",", $values) . ")");
		$count++;
		$progress = $count / $total;
		$progressBar = floor($progress * $barLength);
		echo sprintf("\r[%-{$barLength}s] %d%%", str_repeat('#', $progressBar), $progress * 100);
	}
	echo "\n→ $count Zeilen kopiert.\n";
}
function sqlValue($v, mysqli $db):string {
	return $v === null ? 'NULL' : "'" . $db->real_escape_string($v) . "'";
}

function migrateTournaments($dbcn, $dbOld, $dbNew): void {
	echo "\nKopiere tournaments...\n";
	$dbcn->select_db($dbOld);
	$result = $dbcn->query("SELECT * FROM `tournaments`");
	$dbcn->select_db($dbNew);

	$total = $result->num_rows;
	$count = 0;
	$barLength = 50;
	while ($row = $result->fetch_assoc()) {
		unset($row['ranked_season']);
		unset($row['ranked_split']);
		$columns = array_keys($row);
		$values = array_map(function($v) use ($dbcn) {
			if ($v === null) return 'null';
			return "'". $dbcn->real_escape_string($v) ."'";
		}, array_values($row));
		$dbcn->query("INSERT IGNORE INTO `tournaments` (`" . implode('`,`', $columns) . "`) VALUES (" . implode(",", $values) . ")");
		$count++;
		$progress = $count / $total;
		$progressBar = floor($progress * $barLength);
		echo sprintf("\r[%-{$barLength}s] %d%%", str_repeat('#', $progressBar), $progress * 100);
	}
	echo "\n→ $count Zeilen kopiert.\n";
}
function migrateTournamentsInRankedSplits($dbcn, $dbOld, $dbNew): void {
	echo "\nTransferiere tournaments_in_ranked_splits...\n";
	$dbcn->select_db($dbOld);
	$result = $dbcn->query("SELECT OPL_ID, ranked_season, ranked_split FROM `tournaments` WHERE ranked_season IS NOT NULL AND ranked_split IS NOT NULL");
	$insertQuery = "INSERT INTO tournaments_in_ranked_splits (OPL_ID_tournament, season, split) VALUES (?,?,?)";
	$nextSplitQuery = "SELECT season, split FROM lol_ranked_splits WHERE season > ? OR (season = ? AND split > ?) ORDER BY season, split LIMIT 1";

	$total = $result->num_rows;
	$tournamentInserts = 0;
	$newInserts = 0;
	$barLength = 50;

	while ($row = $result->fetch_assoc()) {
		$dbcn->select_db($dbNew);
		$dbcn->execute_query($insertQuery, [$row['OPL_ID'], $row['ranked_season'], $row['ranked_split']]);
		$tournamentInserts++;

		$progress = $tournamentInserts / $total;
		$progressBar = floor($progress * $barLength);
		echo sprintf("\r[%-{$barLength}s] %d%%", str_repeat('#', $progressBar), $progress * 100);

		$dbcn->select_db($dbOld);
		$nextSplit = $dbcn->execute_query($nextSplitQuery, [$row['ranked_season'], $row['ranked_season'], $row['ranked_split']]);
		if ($nextSplit->num_rows > 0) {
			$nextSplit = $nextSplit->fetch_assoc();
			$dbcn->select_db($dbNew);
			$dbcn->execute_query($insertQuery, [$row['OPL_ID'], $nextSplit['season'], $nextSplit['split']]);
			$newInserts++;
		}
	}
	echo "\n→ Für $tournamentInserts Turniere RankedSplits übertragen.\n";
	if ($newInserts > 0) {
		echo "→ $newInserts neue Turnier<->RankedSplit Einträge erstellt.\n";
	}
}