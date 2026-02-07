<?php
/** @var Matchup $matchup */
/** @var array<MatchupChangeSuggestion> $suggestions */
/** @var array<PlayerInTeamInTournament> $team1Players */
/** @var array<PlayerInTeamInTournament> $team2Players */

use App\Domain\Entities\Matchup;
use App\Domain\Entities\MatchupChangeSuggestion;
use App\Domain\Entities\PlayerInTeamInTournament;
use App\UI\Components\Helpers\IconRenderer;
use App\UI\Components\Matches\ChangeSuggestions\GameSuggestionDetails;

?>

<div><?= count($suggestions) ?: "keine" ?> <?= count($suggestions) === 1 ? "Vorschlag wartet":"Vorschläge warten"?> auf eine Bestätigung</div>

<?php foreach ($suggestions as $suggestion): ?>
	<?= $suggestion->customTeam1Score ?>:<?= $suggestion->customTeam2Score ?>
    <div style="display: flex; flex-direction: column;">
        <?php foreach ($suggestion->addedGames as $game): ?>
            <?= new GameSuggestionDetails($game, false)?>
        <?php endforeach; ?>
    </div>
<?php endforeach; ?>

<div class="divider"></div>

<?php if (count($suggestions) > 3): ?>
	<div>
        Es gibt bereits mehrere Vorschläge für dieses Match.<br>
        Existierende Vorschläge müssen bearbeitet werden,<br>
        bevor Neue gestellt werden können.
    </div>
<?php else: ?>

<button class="open-add-suggestion-form" type="button" style="display: flex; flex-direction: row; align-items: center; gap: 8px"><?= IconRenderer::getMaterialIconSpan("add") ?></button>

<div class="divider"></div>
<div class="add-suggestion-form">
    <div style="display: flex; flex-direction: column; align-items: center; gap: 8px; overflow: hidden">

    <button type="button" class="send-suggestion" style="display: flex; align-items: center; gap: 4px"><?=IconRenderer::getMaterialIconSpan("publish")?>Vorschlag absenden</button>

    <div style="display: flex; flex-direction: row; align-items: center">
        <span style="margin-right: 12px">Score:</span>
        <label style="max-width: 40px">
            <input type="text" placeholder="<?= $matchup->getTeam1Score() ?? '' ?>" maxlength="2"
                   style="padding: 0 8px; text-align: center">
        </label>
        :
        <label style="max-width: 40px">
            <input type="text" placeholder="<?= $matchup->getTeam2Score() ?? '' ?>" maxlength="2"
                   style="padding: 0 8px; text-align: center">
        </label>
    </div>

    <div style="display: flex; flex-direction: column; gap: 4px">
        <span style="text-align: center">Spiele:</span>
        <div style="display: flex; flex-direction: row; align-items: center; justify-content: center; gap: 8px">
            <label class="slct">
                <select>
                    <option hidden="" value="" selected>Spieler auswählen</option>
                    <option disabled value=""><?= $matchup->team1->nameInTournament ?></option>
                    <?php foreach ($team1Players as $player): ?>
                        <option value="<?= $player->player->id ?>">- <?= $player->player->name ?></option>
                    <?php endforeach; ?>
                    <option disabled value=""><?= $matchup->team2->nameInTournament ?></option>
                    <?php foreach ($team2Players as $player): ?>
                        <option value="<?= $player->player->id ?>">- <?= $player->player->name ?></option>
                    <?php endforeach; ?>
                </select>
                <?= IconRenderer::getMaterialIconSpan('arrow_drop_down') ?>
            </label>
            <button class="add-suggestion-get-games" style="padding: 8px 12px" data-matchup-id="<?= $matchup->id ?>">
                Spiele suchen
            </button>
        </div>
        <div class="add-suggestion-games-list" style="display: flex; flex-direction: column"></div>
    </div>
    </div>
</div>
<?php endif; ?>