<?php
/** @var mysqli $dbcn  */

use App\Core\Utilities\UserContext;
use App\UI\Components\Helpers\IconRenderer;
use App\UI\Components\Navigation\Header;
use App\UI\Enums\HeaderType;
use App\UI\Page\AssetManager;
use App\UI\Page\PageMeta;

$pageMeta = new PageMeta('Admin-Panel', bodyClass: 'admin');
AssetManager::addJsFile('/admin/scripts/main.js');

echo new Header(HeaderType::ADMIN);

?>
<h1>OPL -> Database</h1>

<main>
    <dialog class='write-result-popup dismissable-popup'>
        <div class='dialog-content'></div>
    </dialog>
    <button type="button" id="maintenance-mode" class="maintenance-<?= UserContext::isMaintenanceMode() ? 'on' : 'off' ?>">Maintenance Mode</button>
    <h2>Neues Turnier hinzufügen:</h2>
    <div id="main-selection">
        <span class="searchbar"> <label for="input-tournament-id"></label><input id="input-tournament-id" name="id" placeholder="Tournament ID" type="number"> </span>
        <button id="turnier-button-get">Turnier hinzufügen</button>
    </div>
    <dialog id="tournament-add" class="">
        <div class="dialog-content">
            <button class="close-popup"><?= IconRenderer::getMaterialIconSpan('close')?></button>
            <div class="close-button-space"></div>
        </div>
    </dialog>
    <dialog id="related-add" class="clear-on-exit">
        <div class="dialog-content">
            <button class="close-popup"><?= IconRenderer::getMaterialIconSpan('close')?></button>
        </div>
    </dialog>

    <h2>Allgemeine Verwaltung:</h2>
    <div class="general-administration">
        <button class="update_all_teams"><span>Alle Teams aktualisieren</span></button>
        <button class="update_all_player_ranks"><span>Ränge für alle Spieler aktualisieren</span></button>
        <div class="result-wrapper no-res gen-admin">
            <div class="clear-button" onclick="clear_results('gen-admin')">Clear</div>
            <div class="result-content"></div>
        </div>
        <button class="open_ranked_split_popup"><span>LoL Ranked Splits</span></button>
        <dialog id="ranked-split-popup" class="dismissable-popup">
            <div class="dialog-content">
                <button class="close-popup"><?= IconRenderer::getMaterialIconSpan('close')?></button>
                <?= create_ranked_split_list($dbcn) ?>
            </div>
        </dialog>
    </div>

    <h2>Turniere in Datenbank:</h2>
    <div class="turnier-select">
        <?= create_tournament_buttons($dbcn) ?>
    </div>
</main>
<div style="height: 200px"></div>