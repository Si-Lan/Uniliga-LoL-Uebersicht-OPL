<?php
/**@var array<\App\Domain\Entities\MatchupChangeSuggestion> $matchupChangeSuggestions */


?>

<span class="no-suggestions">Keine Vorschläge</span>
<?php foreach ($matchupChangeSuggestions as $suggestion): ?>
	<a href='<?= $suggestion->getLinkToMatchup() ?>' class="suggestion-notification">
		<span>
        	<?= $suggestion->matchup->tournamentStage->getRootTournament()->getSplitAndSeason() ?> |
            <?= $suggestion->matchup->tournamentStage->getFullName() ?>:
		</span>
		<?= $suggestion->matchup->team1?->nameInTournament ?> vs. <?= $suggestion->matchup->team2?->nameInTournament ?>
	</a>
<?php endforeach; ?>
