<?php
/** @var array<array<\App\Entities\Matchup>> $matchupRounds */
/** @var \App\Entities\Tournament $tournamentStage */
/** @var \App\Entities\Team|null $team */
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
            <div class="match-wrapper">
            <?php
			}

            echo $currentMatchButtonHtml;

			if ($team == null) {
			?>
            </div>
        </div>
				<?php
			}
			?>
        <?php
            $roundCounter++;
        }
        ?>
    </div>
</div>