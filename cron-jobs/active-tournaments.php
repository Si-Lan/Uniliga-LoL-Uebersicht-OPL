<?php
include_once __DIR__.'/../setup/data.php';
$dbcn = create_dbcn();

$tournaments = $dbcn->query("SELECT * FROM tournaments WHERE eventType = 'tournament' AND finished = false")->fetch_all(MYSQLI_ASSOC);
$tids = [];
foreach ($tournaments as $tournament) {
	if ($tournament["OPL_ID"] == 2931) continue;
	$tids[] = $tournament['OPL_ID'];
}
echo json_encode($tids);
