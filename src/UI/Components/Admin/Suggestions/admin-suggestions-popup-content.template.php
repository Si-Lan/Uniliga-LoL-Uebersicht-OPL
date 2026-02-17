<?php
/**
 * @var array<\App\Domain\Entities\MatchupChangeSuggestion> $suggestions
 * @var array<\App\Domain\Entities\MatchupChangeSuggestion> $changedMatchups
 * @var string $openTab
 */

use App\UI\Components\Helpers\IconRenderer;

?>

<div class="admin-suggestions-list">
    <div class="suggestions-header">
        <h3>Match-Änderungsvorschläge</h3>
        <div class="suggestions-tabs">
            <button class="suggestions-tab-btn<?=$openTab === 'suggestions' ? ' active' : '' ?>" data-tab="pending">
                Offene Vorschläge <span class="tab-count">(<?= count($suggestions) ?>)</span>
            </button>
            <button class="suggestions-tab-btn<?=$openTab === 'matchups' ? ' active' : '' ?>" data-tab="accepted">
                Geänderte Matchups <span class="tab-count">(<?= count($changedMatchups) ?>)</span>
            </button>
        </div>
    </div>

    <!-- Tab: Offene Vorschläge -->
    <div class="suggestions-tab-content<?=$openTab === 'suggestions' ? ' active' : '' ?>" data-tab-content="pending">
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

    <!-- Tab: Geänderte Matchups -->
    <div class="suggestions-tab-content<?=$openTab === 'matchups' ? ' active' : '' ?>" data-tab-content="accepted">
        <?php if (empty($changedMatchups)): ?>
            <div class="no-suggestions-message">
                <?= IconRenderer::getMaterialIconDiv('info') ?>
                <p>Keine geänderten Matchups vorhanden</p>
            </div>
        <?php else: ?>
            <?php
            // Gruppiere akzeptierte Vorschläge nach Turnier
            $matchupsByTournament = [];
            foreach ($changedMatchups as $matchup) {
                $tournament = $matchup->tournamentStage->getRootTournament();
                $tournamentKey = $tournament->id;

                if (!isset($matchupsByTournament[$tournamentKey])) {
                    $matchupsByTournament[$tournamentKey] = [
                        'tournament' => $tournament,
                        'matchups' => []
                    ];
                }

                $matchupId = $matchup->id;
                if (!isset($matchupsByTournament[$tournamentKey]['matchups'][$matchupId])) {
                    $matchupsByTournament[$tournamentKey]['matchups'][$matchupId] = $matchup;
                }
            }
            usort($matchupsByTournament, function ($a, $b) {
                return $b['tournament']->id <=> $a['tournament']->id;
            })
            ?>

            <div class="suggestions-by-tournament">
                <?php foreach ($matchupsByTournament as $tournamentData): ?>
                    <?php $tournament = $tournamentData['tournament']; ?>
                    <div class="tournament-group">
                        <h4 class="tournament-group-header">
                            <?= IconRenderer::getMaterialIconSpan('trophy') ?>
                            <?= $tournament->getSplitAndSeason() ?>
                        </h4>
                        <div class="suggestions-list-container">
                            <?php foreach ($tournamentData['matchups'] as $matchup): ?>
                                <div class="suggestion-item accepted" data-matchup-id="<?= $matchup->id ?>">
                                    <div class="suggestion-item-header">
                                        <div class="tournament-info">
                                            <span class="tournament-stage"><?= $matchup->tournamentStage->getFullName() ?></span>
                                        </div>
                                    </div>

                                    <div class="matchup-info">
                                        <div class="teams">
                                            <span class="team team1"><?= $matchup->team1?->nameInTournament ?? 'TBD' ?></span>
                                            <span class="vs">vs.</span>
                                            <span class="team team2"><?= $matchup->team2?->nameInTournament ?? 'TBD' ?></span>
                                        </div>

                                        <?php if ($matchup->played): ?>
                                            <div class="current-score">
                                                <span class="score-label">Ergebnis:</span>
                                                <span class="score"><?= $matchup->getTeam1Score() ?>:<?= $matchup->getTeam2Score() ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>




