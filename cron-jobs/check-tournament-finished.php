<?php
include_once __DIR__.'/../setup/data.php';
$dbcn = create_dbcn();

if ($dbcn -> connect_error){
	echo "Database Connection failed";
	exit;
}
if (isset($_GET['t']) && isset($_GET['tn'])) {
	$tournament_id = $_GET['t'];
	$tn = $_GET['tn'];
	$tournament = $dbcn->query("SELECT * FROM tournaments WHERE OPL_ID = '$tournament_id'")->fetch_assoc();
	echo "\nTournament " . ($tn) . ": {$tournament['name']} \n";
	$date_today = date("Y-m-d");
	/*
	if ($tournament['dateEnd'] != NULL && $date_today > $tournament['dateEnd']) {
		echo "\nTournament is over, running for the last time \n";
		$dbcn->query("UPDATE tournaments SET finished = true WHERE OPL_ID = $tournament_id");
	}
	*/
}
