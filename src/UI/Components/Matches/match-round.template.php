<?php
/** @var \App\Domain\Entities\Matchup $matchup */

use App\UI\Components\UI\PageLink;

$team1Name = (is_null($matchup->team1)) ? "TBD" : $matchup->team1->nameInTournament;
$team2Name = (is_null($matchup->team2)) ? "TBD" : $matchup->team2->nameInTournament;
?>

<h2 class="round-title">
    <?php if($matchup->tournamentStage->isEventWithRounds()): ?>
    	<span class="round">Runde <?=$matchup->playday?>: &nbsp;</span>
    <?php endif; ?>
    <?= new PageLink(
            href: "/turnier/{$matchup->tournamentStage->rootTournament->id}/team/{$matchup->team1?->team->id}",
            text: $team1Name,
            additionalClasses: ["team", $matchup->getTeam1Result()],
            linkIcon: false
    )?>
    <?php if ($matchup->played): ?>
	<span class="score">
        <span class="<?=$matchup->getTeam1Result()?>"><?=$matchup->team1Score?></span>:<span class="<?=$matchup->getTeam2Result()?>"><?=$matchup->team2Score?></span>
    </span>
    <?php else: ?>
        <span class="score">vs.</span>
    <?php endif; ?>
	<?= new PageLink(
		href: "/turnier/{$matchup->tournamentStage->rootTournament->id}/team/{$matchup->team2?->team->id}",
		text: $team2Name,
		additionalClasses: ["team", $matchup->getTeam2Result()],
		linkIcon: false
	)?>
</h2>