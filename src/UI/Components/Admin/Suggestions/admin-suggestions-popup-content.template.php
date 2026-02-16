<?php
/** @var array<\App\Domain\Entities\MatchupChangeSuggestion> $suggestions */

use App\UI\Components\Helpers\IconRenderer;

?>

<div class="admin-suggestions-list">
    <div class="suggestions-header">
        <h3>Offene Match-Änderungsvorschläge</h3>
        <span class="suggestion-count"><?= count($suggestions) ?> <?= count($suggestions) === 1 ? 'Vorschlag' : 'Vorschläge' ?></span>
    </div>

    <?php if (empty($suggestions)): ?>
        <div class="no-suggestions-message">
            <?= IconRenderer::getMaterialIconDiv('check_circle') ?>
            <p>Keine offenen Vorschläge</p>
        </div>
    <?php else: ?>
        <div class="suggestions-list-container">
            <?php
            // Gruppiere Vorschläge nach Matchup
            $groupedSuggestions = [];
            foreach ($suggestions as $suggestion) {
                $matchupId = $suggestion->matchup->id;
                if (!isset($groupedSuggestions[$matchupId])) {
                    $groupedSuggestions[$matchupId] = [
                        'matchup' => $suggestion->matchup,
                        'suggestions' => []
                    ];
                }
                $groupedSuggestions[$matchupId]['suggestions'][] = $suggestion;
            }
            ?>

            <?php foreach ($groupedSuggestions as $matchupId => $data):
                $matchup = $data['matchup'];
                $matchupSuggestions = $data['suggestions'];
                $suggestionCount = count($matchupSuggestions);
            ?>
                <div class="suggestion-item" data-matchup-id="<?= $matchupId ?>">
                    <div class="suggestion-item-header">
                        <div class="tournament-info">
                            <span class="tournament-name"><?= $matchup->tournamentStage->getRootTournament()->getSplitAndSeason() ?></span>
                            <span class="tournament-stage"><?= $matchup->tournamentStage->getFullName() ?></span>
                        </div>
                        <?php if ($suggestionCount > 1): ?>
                            <span class="multiple-suggestions-badge"><?= $suggestionCount ?> Vorschläge</span>
                        <?php endif; ?>
                    </div>

                    <div class="matchup-info">
                        <div class="teams">
                            <span class="team team1"><?= $matchup->team1?->nameInTournament ?? 'TBD' ?></span>
                            <span class="vs">vs.</span>
                            <span class="team team2"><?= $matchup->team2?->nameInTournament ?? 'TBD' ?></span>
                        </div>

                        <?php if ($matchup->played): ?>
                            <div class="current-score">
                                <span class="score-label">Aktuell:</span>
                                <span class="score"><?= $matchup->getTeam1Score() ?>:<?= $matchup->getTeam2Score() ?></span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="suggestion-meta">
                        <span class="created-at">
                            <?= IconRenderer::getMaterialIconSpan('schedule') ?>
                            <?= $matchupSuggestions[0]->createdAt?->format('d.m.Y H:i') ?>
                        </span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

