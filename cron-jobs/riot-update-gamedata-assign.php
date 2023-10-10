<?php
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

echo "<br>---- get Gamedata for Games without Data <br>";
$games = $dbcn->execute_query("SELECT * FROM games WHERE matchdata IS NULL")->fetch_all(MYSQLI_ASSOC);
$gamedata_gotten = $inT = $notinT = $sorted = $notsorted = 0;
foreach ($games as $gindex=>$game) {
	if (($gindex) % 50 === 0 && $gindex != 0) {
		sleep(10);
	}
	$result = add_match_data($game["RIOT_matchID"],$tournament_id);
	$resultA = assign_and_filter_game($game["RIOT_matchID"],$tournament_id);
	$gamedata_gotten += $result["writes"];
	$notinT += $result["notUL"];
	$inT += $result["isUL"];
	$sorted += $result["sorted"];
	$notsorted += $result["notsorted"];
}
echo "-------- Gamedata for $gamedata_gotten Games written<br>";
echo "-------- $notinT Games not from Tournament<br>";
echo "-------- $inT Games from the Tournament<br>";
echo "-------- $sorted Games matched with Tournament-Games<br>";
echo "-------- $notsorted Games found no match<br>";