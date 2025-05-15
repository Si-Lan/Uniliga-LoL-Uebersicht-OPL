<?php
$currentClass = ($this->teamSelected) ? "current" : null;
$shareRankClass = ($this->previousRowStanding === $this->teamInTournamentStage->standing) ? "shared-rank" : null;

$classes = implode(' ', array_filter(["standing-row", "standing-team", $currentClass]));
?>
<div class="<?= $classes ?>">
    <?php
	$classes = implode(' ', array_filter(["standing-pre", "rank", $shareRankClass]));
    ?>
    <div class="<?=$classes?>">
        <?=$this->teamInTournamentStage->standing?>
    </div>
    <div class="standing-item-wrapper">
        <?php
        echo new \App\Components\Standings\TeamLinkInRow($this->teamInTournamentStage);
        ?>
        <div class="standing-item played"><?=$this->teamInTournamentStage->played?></div>
		<?php
		switch ($this->teamInTournamentStage->tournamentStage->mostCommonBestOf) {
			case 1:
				?>
                <div class="standing-item score"><?=$this->teamInTournamentStage->wins?>-<?=$this->teamInTournamentStage->losses?></div>
				<?php
				break;
			case 3:
			case 5:
				?>
                <div class="standing-item score"><?=$this->teamInTournamentStage->wins?>-<?=$this->teamInTournamentStage->losses?></div>
                <div class="standing-item score-games">(<?=$this->teamInTournamentStage->singleWins?>-<?=$this->teamInTournamentStage->singleLosses?>)</div>
				<?php
				break;
			default:
				?>
                <div class="standing-item score"><?=$this->teamInTournamentStage->wins?>-<?=$this->teamInTournamentStage->draws?>-<?=$this->teamInTournamentStage->losses?></div>
			<?php
		}
		?>
        <div class="standing-item points"><?=$this->teamInTournamentStage->points?></div>
    </div>
</div>
<div class="divider-light"></div>