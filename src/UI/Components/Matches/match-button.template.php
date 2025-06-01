<?php
/** @var \App\Domain\Entities\Matchup $matchup */
/** @var \App\Domain\Entities\Team|null $currentTeam */
/** @var bool $popupOpened */

use App\UI\Components\Popups\MatchPopupContent;
use App\UI\Components\Popups\Popup;

$team1Name = (is_null($matchup->team1)) ? "TBD" : $matchup->team1->nameInTournament;
$team2Name = (is_null($matchup->team2)) ? "TBD" : $matchup->team2->nameInTournament;

$matchPopup = new Popup(
        id: $matchup->id,
        pagePopupType: "match-popup",
        dismissable: true,
        autoOpen: $popupOpened,
        content: $popupOpened ? new matchPopupContent($matchup, $currentTeam) : ''
);
?>
<div class="match-button-wrapper" data-matchid="<?=$matchup->id?>" data-tournamentid="<?=$matchup->tournamentStage->rootTournament->id?>">
	<a class="button match sideext-right" href="?match=<?=$matchup->id?>" data-match-id="<?=$matchup->id?>" <?=(!is_null($currentTeam?->id) && $currentTeam?->id === $matchup->team1?->team->id) ? "data-team-id='{$currentTeam->id}'" : ""?> data-dialog-id="<?=$matchPopup->getId()?>">
		<div class="<?=implode(" ", array_filter(["teams", ($matchup->played) ? "score" : ""]))?>">
			<div class="<?=implode(" ", array_filter(["team", 1, $matchup->getTeam1Result(), (!is_null($currentTeam?->id) && $currentTeam?->id === $matchup->team1?->team->id) ? "current" : ""]))?>" title="<?=$team1Name?>"><?=$team1Name?></div>
			<?php if ($matchup->played) {?>
			<div class="<?=implode(" ", array_filter(["score", 1, $matchup->getTeam1Result(), (!is_null($currentTeam?->id) && $currentTeam?->id === $matchup->team1?->team->id) ? "current" : ""]))?>"><?=$matchup->team1Score?></div>
			<?php } ?>
			<div class="<?=implode(" ", array_filter(["team", 2, $matchup->getTeam2Result(), (!is_null($currentTeam?->id) && $currentTeam?->id === $matchup->team2?->team->id) ? "current" : ""]))?>" title="<?=$team2Name?>"><?=$team2Name?></div>
			<?php if ($matchup->played) {?>
			<div class="<?=implode(" ", array_filter(["score", 2, $matchup->getTeam2Result(), (!is_null($currentTeam?->id) && $currentTeam?->id === $matchup->team2?->team->id) ? "current" : ""]))?>"><?=$matchup->team2Score?></div>
			<?php } ?>
			<?php if (!$matchup->played) {?>
			<div class="date"><?=date_format($matchup->plannedDate, 'd M')?><br><?=date_format($matchup->plannedDate, 'H:i')?></div>
			<?php } ?>
		</div>
	</a>
	<a class="sidebutton-match" href="https://www.opleague.pro/match/<?=$matchup->id?>" target="_blank">
		<?= \App\UI\Components\Helpers\IconRenderer::getMaterialIconDiv("open_in_new")?>
	</a>
</div>
<?= $matchPopup->render()?>