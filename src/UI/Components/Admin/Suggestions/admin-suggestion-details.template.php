<?php
/** @var \App\Domain\Entities\Matchup $matchup */
/** @var array<\App\Domain\Entities\MatchupChangeSuggestion> $suggestions */

use App\Domain\Repositories\GameInMatchRepository;
use App\UI\Components\Helpers\IconRenderer;
use App\UI\Components\Matches\ChangeSuggestions\GameSuggestionDetails;
use App\UI\Components\UI\PageLink;

$gameInMatchRepo = new GameInMatchRepository();
$currentGames = $gameInMatchRepo->findAllActiveByMatchup($matchup);
?>

<div class="admin-suggestion-details">
    <div class="details-header">
        <button class="back-button" id="back-to-suggestions-list">
            <?= IconRenderer::getMaterialIconSpan('chevron_left') ?>
            Zurück zur Liste
        </button>

        <div class="matchup-title">
            <div class="tournament-info">
                <span class="tournament-name"><?= $matchup->tournamentStage->getRootTournament()->getSplitAndSeason() ?></span>
                <span class="tournament-stage"><?= $matchup->tournamentStage->getFullName() ?></span>
            </div>
            <h3 class="teams">
                <?php ob_start() ?>
                <span class="team"><?= $matchup->team1?->nameInTournament ?? 'TBD' ?></span>
                <span class="vs">vs.</span>
                <span class="team"><?= $matchup->team2?->nameInTournament ?? 'TBD' ?></span>
                <?php $teamNames = ob_get_clean() ?>
                <?= new PageLink($matchup->getLink(), $teamNames) ?>
            </h3>
        </div>
    </div>

    <div class="comparison-container">
        <!-- Aktuelle Daten -->
        <div class="current-data">
            <h4><?= IconRenderer::getMaterialIconSpan('sports_esports') ?> Aktueller Stand</h4>

            <?php if ($matchup->played): ?>
                <div class="score-display current">
                    <span class="score-label">Ergebnis:</span>
                    <span class="score"><?= $matchup->getTeam1Score() ?>:<?= $matchup->getTeam2Score() ?></span>
                </div>
            <?php else: ?>
                <div class="not-played-message">
                    <?= IconRenderer::getMaterialIconSpan('warning') ?>
                    <span>Match noch nicht gespielt</span>
                </div>
            <?php endif; ?>

            <div class="games-section">
                <h5>Eingetragene Spiele (<?= count($currentGames) ?>)</h5>
                <?php if (empty($currentGames)): ?>
                    <div class="no-games-message">Keine Spiele eingetragen</div>
                <?php else: ?>
                    <div class="games-list current">
                        <?php foreach ($currentGames as $gameInMatch): ?>
                            <?= new GameSuggestionDetails($gameInMatch, selectable: false) ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Vorgeschlagene Änderungen -->
        <div class="suggested-data">
            <?php foreach ($suggestions as $index => $suggestion): ?>
                <div class="suggestion-block" data-suggestion-id="<?= $suggestion->id ?>">
                    <div class="suggestion-block-header">
                        <h4>
                            <?= IconRenderer::getMaterialIconSpan('edit_note') ?>
                            Vorschlag <?= $index + 1 ?>
                            <?php if (count($suggestions) > 1): ?>
                                <span class="suggestion-number">von <?= count($suggestions) ?></span>
                            <?php endif; ?>
                        </h4>
                        <span class="suggestion-time">
                            <?= $suggestion->createdAt?->format('d.m.Y H:i') ?>
                        </span>
                    </div>

                    <?php if ($suggestion->hasScoreChange()): ?>
                        <div class="score-display suggested">
                            <span class="score-label">Vorgeschlagenes Ergebnis:</span>
                            <span class="score">
                                <?= $suggestion->customTeam1Score ?? $matchup->getTeam1Score() ?>
                                :
                                <?= $suggestion->customTeam2Score ?? $matchup->getTeam2Score() ?>
                            </span>
                            <?php if ($matchup->played): ?>
                                <span class="score-change">
                                    (<?= $matchup->getTeam1Score() ?>:<?= $matchup->getTeam2Score() ?>
                                    <?= IconRenderer::getMaterialIconSpan('chevron_right') ?>
                                    <?= $suggestion->customTeam1Score ?? $matchup->getTeam1Score() ?>:<?= $suggestion->customTeam2Score ?? $matchup->getTeam2Score() ?>)
                                </span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <div class="games-section">
                        <h5>Vorgeschlagene Spiele (<?= count($suggestion->games) ?>)</h5>
                        <?php if (empty($suggestion->games)): ?>
                            <div class="no-games-message">Keine Spiele vorgeschlagen</div>
                        <?php else: ?>
                            <div class="games-list suggested">
                                <?php foreach ($suggestion->games as $gameInMatch): ?>
                                    <?= new GameSuggestionDetails($gameInMatch, selectable: false) ?>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Action Buttons -->
                    <div class="suggestion-actions">
                        <button class="accept-suggestion-admin" data-suggestion-id="<?= $suggestion->id ?>" data-matchup-id="<?= $matchup->id ?>">
                            <?= IconRenderer::getMaterialIconSpan('check') ?>
                            Vorschlag akzeptieren
                        </button>
                        <button class="reject-suggestion-admin" data-suggestion-id="<?= $suggestion->id ?>" data-matchup-id="<?= $matchup->id ?>">
                            <?= IconRenderer::getMaterialIconSpan('close') ?>
                            Vorschlag ablehnen
                        </button>
                    </div>
                </div>

                <?php if ($index < count($suggestions) - 1): ?>
                    <div class="suggestion-divider"></div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>
</div>




