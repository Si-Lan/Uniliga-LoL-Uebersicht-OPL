<?php
/** @var array<array<\App\Entities\Matchup>> $matchupRounds */
/** @var \App\Entities\Tournament $tournamentStage */
/** @var \App\Entities\Team|null $team */
/** @var \App\Entities\Tournament $playoffStage */
/** @var array<\App\Entities\Matchup> $matchupsPlayoff */
/** @var \App\Repositories\TeamInTournamentRepository $teamInTournamentRepository */

use App\Components\Matches\MatchButton;

?>

<div class="matches">
    <div class="title"><h3>Spiele</h3></div>
    <div class="mh-popup-bg" onclick="close_popup_match(event)">
        <div class="mh-popup"></div>
    </div>
    <div class="match-content content">
        <?php
        $roundCounter = 1;
        foreach ($matchupRounds as $matchupRound) {
            if ($tournamentStage->isEventWithRounds()) $roundCounter = $matchupRound[0]->playday;

            $currentMatchButtonHtml = '';
            foreach ($matchupRound as $matchup) {
				$currentMatchButtonHtml .= new MatchButton($matchup, $team, $teamInTournamentRepository);
			}
            ?>
            <?php
            if ($team == null) {
            ?>
        <div class="match-round">
            <h4>Runde <?= $roundCounter ?></h4>
            <div class="divider"></div>
            <?php
			}
            ?>
            <div class="match-wrapper">
                <?= $currentMatchButtonHtml ?>
            </div>
			<?php
			if ($team == null) {
			?>
        </div>
				<?php
			}
			?>
        <?php
            $roundCounter++;
        }

        if ($playoffStage != null && $team != null) {
            ?>
        <h4>Playoffs</h4>
        <?php
            foreach ($matchupsPlayoff as $matchup) {
                echo new MatchButton($matchup, $team, $teamInTournamentRepository);
            }
        }
        ?>
    </div>
</div>