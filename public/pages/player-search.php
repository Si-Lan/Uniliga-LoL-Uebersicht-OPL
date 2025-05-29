<?php

use App\UI\Components\Helpers\IconRenderer;
use App\UI\Components\Navigation\Header;
use App\UI\Enums\HeaderType;
use App\UI\Page\PageMeta;

$pageMeta = new PageMeta('Spielersuche',bodyClass: 'players');

?>

<?= new Header(HeaderType::PLAYERS) ?>

<main>
<div>
    <h2>Spielersuche</h2>
    Suche nach Spielernamen oder RiotID
</div>
<div class='searchbar'>
        <span class='material-symbol search-icon' title='Suche'>
            <?= IconRenderer::getMaterialIcon('search')?>
        </span>
        <input class="search-players deletable-search" placeholder='Spieler suchen' type='search'>
        <button class='material-symbol search-clear' title='Suche leeren' type='button'>
			<?= IconRenderer::getMaterialIcon('close')?>
        </button>
    </div>
    <div class='player-popup-bg' onclick='close_popup_player(event)'>
        <div class='player-popup'></div>
    </div>
    <div class='recent-players-list'></div>
    <div class='player-list'></div>
</main>