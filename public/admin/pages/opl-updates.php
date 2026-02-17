<?php

use App\Core\Utilities\UserContext;
use App\UI\Components\Admin\RankedSplit\RankedSplitList;
use App\UI\Components\Admin\TournamentEdit\TournamentEditList;
use App\UI\Components\Helpers\IconRenderer;
use App\UI\Components\Navigation\Header;
use App\UI\Components\Popups\Popup;
use App\UI\Enums\HeaderType;
use App\UI\Page\AssetManager;
use App\UI\Page\PageMeta;

$pageMeta = new PageMeta('Admin-Panel', bodyClass: 'admin opl');
AssetManager::addJsModule('admin/oplImport');
AssetManager::addJsModule('admin/generalAdmin');

echo new Header(HeaderType::ADMIN);

?>

<main>
    <h2>Neues Turnier hinzufügen:</h2>
    <div id="main-selection">
        <span class="searchbar"> <label for="input-tournament-id"></label><input id="input-tournament-id" name="tournament-id" placeholder="Tournament ID" type="number"> </span>
        <button id="turnier-button-get" type="button">Turnier hinzufügen</button>
    </div>
    <?= new Popup("tournament-add", dismissable: false, additionalClasses: ['clear-on-exit'])?>

    <h2>Allgemeine Verwaltung:</h2>
    <div class="general-administration">
        <button class="update_all_teams">
            <span>Alle Teams aktualisieren<?=IconRenderer::getMaterialIconSpan('sync')?></span>
        </button>
        <div class="result-wrapper no-res gen-admin">
            <div class="clear-button">Clear</div>
            <div class="result-content"></div>
        </div>
    </div>

    <h2>Turniere in Datenbank:</h2>
    <div class="turnier-select">
        <?= new TournamentEditList() ?>
    </div>
</main>
<div style="height: 200px"></div>