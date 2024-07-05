<?php
$day = date("d_m_y");
ini_set("log_errors", 1);
ini_set("error_log", "cron_logs/cron_errors/riot_gamedata_assign_$day.log");
include_once __DIR__."/../admin/functions/get-rgapi-data.php";
include_once __DIR__.'/../setup/data.php';
$dbcn = create_dbcn();

if ($dbcn->connect_error) {
	echo "Database Connection failed";
	exit;
}
if (!(isset($_GET['t']))) {
	exit;
}
$tournament_id = $_GET['t'];

echo "\n---- get Gamedata for Games without Data \n";
file_put_contents("cron_logs/cron_log_$day.log","\n----- Gamedata for Games without Data starting -----\n".date("d.m.y H:i:s")." : Gamedata for $tournament_id\n", FILE_APPEND);

$games = $dbcn->execute_query("SELECT RIOT_matchID FROM games WHERE played_at IS NULL")->fetch_all(MYSQLI_ASSOC);
$gamedata_gotten = $inT = $notinT = $sorted = $notsorted = 0;
foreach ($games as $gindex=>$game) {
	if (($gindex) % 50 === 0 && $gindex != 0) {
		file_put_contents("cron_logs/cron_log_$day.log",date("d.m.y H:i:s")." : $gindex Games done\n", FILE_APPEND);
		sleep(10);
	}
	$result = add_match_data($game["RIOT_matchID"],$tournament_id);
	$resultA = assign_and_filter_game($game["RIOT_matchID"],$tournament_id);
	$gamedata_gotten += $result["writes"];
	$notinT += $resultA["notUL"];
	$inT += $resultA["isUL"];
	$sorted += $resultA["sorted"];
	$notsorted += $resultA["notsorted"];
}

file_put_contents("cron_logs/cron_log_$day.log","Gamedata for $gamedata_gotten Games written\n$notinT Games not from Tournament\n$inT Games from the Tournament\n$sorted Games matched with Tournament-Games\n$notsorted Games found no match\n"."----- Gamedata for Games without Data done -----\n", FILE_APPEND);

echo "-------- Gamedata for $gamedata_gotten Games written\n";
echo "-------- $notinT Games not from Tournament\n";
echo "-------- $inT Games from the Tournament\n";
echo "-------- $sorted Games matched with Tournament-Games\n";
echo "-------- $notsorted Games found no match\n";