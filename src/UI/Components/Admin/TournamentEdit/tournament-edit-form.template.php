<?php
/** @var \App\Domain\Entities\Tournament $tournament */
/** @var bool $isNew */
/** @var array $parentIds */
/** @var array $childrenIds */
/** @var array $rankedSplits */

use App\Domain\Enums\EventFormat;
use App\Domain\Enums\EventType;
use App\UI\Components\Admin\RelatedTournamentButtonList;
use App\UI\Components\Helpers\IconRenderer;
use App\UI\Components\Popups\Popup;

$nonRootDisableAttribute = $tournament->eventType === EventType::TOURNAMENT ? '' : 'readonly';
?>

<div class="tournament-write-data-wrapper">
	<div class="tournament-write-data <?=$tournament->id?>" data-id="<?=$tournament->id?>">
		<div class="write_tournament_row wtrow-1">
			<label class="write_tournament_id"><input type="text" name="OPL_ID" value="<?=$tournament->id?>" readonly=""></label>
			<label class="write_tournament_name"><input type="text" name="name" value="<?=$tournament->name?>"></label>
			<label class="write_tournament_type">
				<span class="slct">
					<select name="eventType">
						<option value=""></option>
                        <?php foreach (EventType::cases() as $eventType): ?>
                            <option value="<?=$eventType->value?>" <?=$tournament->eventType === $eventType ? 'selected' : ''?>><?=$eventType->getPrettyName()?></option>
                        <?php endforeach; ?>
					</select>
					<?= IconRenderer::getMaterialIconSpan('arrow_drop_down')?>
				</span>
			</label>
			<label class="write_tournament_parent">Parent:<input type="text" name="OPL_ID_parent" value="<?=$tournament->directParentTournament?->id?>"></label>
			<label class="write_tournament_top_parent">Top:<input type="text" name="OPL_ID_top_parent" value="<?=$tournament->rootTournament?->id?>"></label>
		</div>
		<div class="write_tournament_row wtrow-2">
			<label class="write_tournament_split">
				<span class="slct">
					<select name="split">
						<option hidden="" value="">"Split"</option>
						<option value="winter" <?=$tournament->split === "winter" ? 'selected' : ''?>>Winter</option>
						<option value="sommer" <?=$tournament->split === "sommer" ? 'selected' : ''?>>Sommer</option>
					</select>
					<?= IconRenderer::getMaterialIconSpan('arrow_drop_down')?>
				</span>
			</label>
			<label class="write_tournament_season"><input type="number" name="season" value="<?=$tournament->season?>" placeholder="##"></label>
			<label class="write_tournament_number">Nummer:<input type="text" name="number" value="<?=$tournament->number?>" placeholder="#"></label>
			<label class="write_tournament_number2"><input type="text" name="numberRangeTo" value="<?=$tournament->numberRangeTo?>" placeholder="#"></label>
			<label class="write_tournament_startdate">Zeitraum<input type="date" name="dateStart" value="<?=$tournament->dateStart?->format('Y-m-d')?>"></label>
			<label class="write_tournament_enddate"><input type="date" name="dateEnd" value="<?=$tournament->dateEnd?->format('Y-m-d')?>"></label>
            <?php if ($tournament->isEventWithStanding()) : ?>
			<label class="write_tournament_format">
				<span class="slct">
					<select name="format">
						<option value="">Format wählen</option>
                        <?php foreach (EventFormat::cases() as $eventFormat): ?>
                            <option value="<?=$eventFormat->value?>" <?=$tournament->format === $eventFormat ? 'selected' : ''?>><?=$eventFormat->value?></option>
						<?php endforeach; ?>
					</select>
					<?= IconRenderer::getMaterialIconSpan('arrow_drop_down')?>
				</span>
			</label>
            <?php endif; ?>
		</div>
		<div class="write_tournament_row wtrow-3">
			<label class="write_tournament_show">Anzeigen:<input type="checkbox" name="deactivated" value="false" <?=$tournament->deactivated ? '' : 'checked'?>></label>
			<label class="write_tournament_finished">Beendet:<input type="checkbox" name="finished" value="true" <?=$tournament->finished ? 'checked' : ''?> <?=$nonRootDisableAttribute?>></label>
			<label class="write_tournament_archived">Archiviert:<input type="checkbox" name="archived" value="true" <?=$tournament->archived ? 'checked' : ''?> <?=$nonRootDisableAttribute?>></label>
			<label class="write_tournament_logoid">Logo:<input type="number" name="OPL_ID_logo" value="<?=$tournament->logoId?>" readonly=""></label>
            <?php if ($tournament->eventType === EventType::TOURNAMENT): ?>
                <?php
                $rankedSplits = array_map(fn($rankedSplit) => $rankedSplit->getName(),$rankedSplits);
                $selectedRankedSplits = array_map(fn($rankedSplit) => $rankedSplit->getName(),$tournament->rankedSplits);
                ?>
                <label class="write_tournament_ranked_splits">Rank-Split:
                <?=new \App\UI\Components\UI\MultiSelectDropdown("Ranked-Splits auswählen", $rankedSplits, $selectedRankedSplits)?>
                </label>
            <?php endif; ?>
		</div>
	</div>
    <?php
    $childrenPopup = new Popup("children-add-{$tournament->id}", uniqueId: true, content: "<h2>Kinder</h2>" . new RelatedTournamentButtonList($childrenIds), additionalClasses: ['related-events-dialog']);
    $parentsPopup = new Popup("parents-add-{$tournament->id}", uniqueId: true, content: "<h2>Eltern</h2>" . new RelatedTournamentButtonList($parentIds), additionalClasses: ['related-events-dialog']);
    ?>
    <?php if (!$isNew): ?>
	<div class="tournament-write-button-wrapper">
		<button class="update_tournament" type="button" data-id="<?=$tournament->id?>">Aktualisieren</button>
        <button class="get_related_events" type="button" data-relation="parents" data-id="<?=$tournament->id?>" data-dialog-id="<?=$parentsPopup->getId()?>">Eltern holen</button>
        <button class="get_related_events" type="button" data-relation="children" data-id="<?=$tournament->id?>" data-dialog-id="<?=$childrenPopup->getId()?>">Kinder holen</button>
        <button class="open-tournament-data-popup" type="button" data-id="<?=$tournament->id?>" data-dialog-id="tournament-data-popup-<?=$tournament->id?>"><span>weitere Daten holen</span></button>
	</div>
    <?php
        $popupContent = <<<HTML
        <h2>{$tournament->getSplitAndSeason()} | {$tournament->getFullName()} ({$tournament->eventType->getPrettyName()})</h2>
        <button class="get-teams" data-id="$tournament->id"><span>Teams updaten (pro Gruppe)</span></button>
        <div class="divider" style="margin: 4px 0"></div>
		<button class="get-players" data-id="$tournament->id"><span>Spieler updaten (pro Team)</span></button>
		<div class="divider" style="margin: 4px 0"></div>
		<button class="get-riotids" data-id="$tournament->id"><span>Spieler-Accounts updaten (pro Team -> pro Spieler)</span></button>
		<div class="divider" style="margin: 4px 0"></div>
		<button class="get-matchups" data-id="$tournament->id"><span>Matches updaten (pro Gruppe)</span></button>
		<div class="divider" style="margin: 4px 0"></div>
		<button class="get-results" data-id="$tournament->id"><span>Match-Ergebnisse und LoL-Spiele updaten (pro Match)</span></button>
		<button class="get-results-unplayed" data-id="$tournament->id"><span>Match-Ergebnisse und LoL-Spiele updaten (pro ungespieltem Match)</span></button>
		<div class="divider" style="margin: 4px 0"></div>
		<button class="calculate-standings" data-id="$tournament->id"><span>Tabelle aktualisieren (pro Gruppe) (Berechnung)</span></button>
HTML;
        if ($tournament->eventType === EventType::TOURNAMENT) {
            $popupContent .= <<<HTML
        <div class="divider" style="margin: 8px 0"></div>
        <button class="get-tournament-logo" data-id="$tournament->id"><span>Logo herunterladen</span></button>
HTML;
        }
    ?>
	<?= new Popup("tournament-data-popup-{$tournament->id}", noCloseButton: true, content: $popupContent, additionalClasses: ['tournament-data-popup']) ?>
    <?php else: ?>
    <div class="tournament-write-button-wrapper">
        <button class="write_tournament" type="button" data-id="<?=$tournament->id?>">Eintragen</button>
        <button class="get_related_events" type="button" data-relation="parents" data-id="<?=$tournament->id?>" data-dialog-id="<?=$parentsPopup->getId()?>">Eltern holen</button>
        <button class="get_related_events" type="button" data-relation="children" data-id="<?=$tournament->id?>" data-dialog-id="<?=$childrenPopup->getId()?>">Kinder holen</button>
    </div>
    <?php endif; ?>

	<?= $parentsPopup->render() ?>
    <?= $childrenPopup->render() ?>

    <?= new Popup("result-popup-{$tournament->id}", noCloseButton: true, additionalClasses: ['write-result-popup'])?>
</div>