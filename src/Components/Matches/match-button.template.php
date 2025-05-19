<?php
/** @var \App\Entities\Matchup $matchup */
/** @var \App\Entities\Team|null $currentTeam */
/** @var \App\Entities\TeamInTournament|null $team1InTournament */
/** @var \App\Entities\TeamInTournament|null $team2InTournament */

$team1Name = (is_null($team1InTournament)) ? "TBD" : $team1InTournament->nameInTournament;
$team2Name = (is_null($team2InTournament)) ? "TBD" : $team2InTournament->nameInTournament;
?>
<div class="match-button-wrapper" data-matchid="<?=$matchup->id?>" data-tournamentid="<?=$matchup->tournamentStage->rootTournament->id?>">
	<a class="button match sideext-right" href="?match=<?=$matchup->id?>" onclick="popup_match(<?=$matchup->id?>,<?=$currentTeam?->id??"null"?>,<?=$matchup->tournamentStage->rootTournament->id?>)">
		<div class="<?=implode(" ", array_filter(["teams", ($matchup->played) ? "score" : ""]))?>">
			<div class="<?=implode(" ", array_filter(["team", 1, $matchup->getTeam1Result(), (!is_null($currentTeam?->id) && $currentTeam?->id === $matchup->team1?->id) ? "current" : ""]))?>" title="<?=$team1Name?>"><?=$team1Name?></div>
			<?php if ($matchup->played) {?>
			<div class="<?=implode(" ", array_filter(["score", 1, $matchup->getTeam1Result(), (!is_null($currentTeam?->id) && $currentTeam?->id === $matchup->team1?->id) ? "current" : ""]))?>"><?=$matchup->team1Score?></div>
			<?php } ?>
			<div class="<?=implode(" ", array_filter(["team", 2, $matchup->getTeam2Result(), (!is_null($currentTeam?->id) && $currentTeam?->id === $matchup->team2?->id) ? "current" : ""]))?>" title="<?=$team2Name?>"><?=$team2Name?></div>
			<?php if ($matchup->played) {?>
			<div class="<?=implode(" ", array_filter(["score", 2, $matchup->getTeam2Result(), (!is_null($currentTeam?->id) && $currentTeam?->id === $matchup->team2?->id) ? "current" : ""]))?>"><?=$matchup->team2Score?></div>
			<?php } ?>
			<?php if (!$matchup->played) {?>
			<div class="date"><?=date_format($matchup->plannedDate, 'd M')?><br><?=date_format($matchup->plannedDate, 'H:i')?></div>
			<?php } ?>
		</div>
	</a>
	<a class="sidebutton-match" href="https://www.opleague.pro/match/<?=$matchup->id?>" target="_blank">
		<?=\App\Components\Helpers\IconRenderer::getMaterialIconDiv("open_in_new")?>
	</a>
</div>