<?php
/** @var \App\Domain\Entities\Matchup $matchup */
/** @var \App\Domain\Entities\Team|null $team */
/** @var array<\App\Domain\Entities\GameInMatch> $gamesInMatch */

use App\UI\Components\Games\GameDetails;
use App\UI\Components\Helpers\IconRenderer;
use App\UI\Components\Matches\ChangeSuggestions\AddSuggestionPopupContent;
use App\UI\Components\Matches\MatchRound;
use App\UI\Components\Popups\Popup;
use App\UI\Components\UI\PageLink;
use App\UI\Components\UpdateButton;

$changePopup = new Popup(
        "match_change_popup_{$matchup->id}",
        uniqueId: true,
        content: new AddSuggestionPopupContent($matchup)
)
?>

<?= new PageLink("https://www.opleague.pro/match/$matchup->id","In OPL öffnen", linkIcon: "open_in_new")?>
<div class="divider" style="max-width: 240px; margin-bottom: 8px"></div>
<div class="mh-popup-buttons">
    <?php if ($matchup->played): ?>
    <div style="display: flex;flex-direction: column; align-items: center;:">
        <button class="suggest-match-changes" data-dialog-id="<?=$changePopup->getId()?>" type="button" style="display: flex;flex-direction: row; align-items: center; gap: 8px"><?= IconRenderer::getMaterialIconSpan("edit_square")?><span>Änderungen vorschlagen</span></button>
        <?php if ($matchup->hasCustomChanges()): ?>
            <span style="font-size: 0.8em">Match hat Änderungen eingetragen</span>
        <?php endif; ?>
    </div>
    <?= $changePopup->render() ?>
    <?php endif; ?>

    <?php if (!$matchup->hasCustomChanges()): ?>
        <?= new UpdateButton($matchup, teamContext: $team)?>
    <?php endif; ?>
</div>
<span>Spieldatum: <?=$matchup->plannedDate?->format('d.m.Y, H:i') ?? "unbekannt"?></span>
<?= new MatchRound($matchup) ?>

<?php if (!count($gamesInMatch) && $matchup->isQualified()): ?>
    <div class="no-game-found">Match wird nicht gespielt<br>Beide Teams sind qualifiziert</div>
<?php elseif (!count($gamesInMatch) && !$matchup->played): ?>
    <div class="no-game-found">Spiel wurde noch nicht gespielt</div>
<?php elseif (!count($gamesInMatch) && $matchup->defWin): ?>
    <div class="no-game-found">Keine Spiele vorhanden (Default Win)</div>
<?php elseif (!count($gamesInMatch)): ?>
    <div class="no-game-found">Noch keine eingetragenen Spiele gefunden</div>
<?php else: ?>
    <?php foreach ($gamesInMatch as $index=>$gameInMatch): ?>
        <div class="game game<?=$index?>"><?= new GameDetails($gameInMatch,$team)?></div>
    <?php endforeach; ?>
<?php endif; ?>
