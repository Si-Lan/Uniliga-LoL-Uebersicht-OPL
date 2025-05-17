<?php

use App\Components\Navigation\Header;
use App\Components\UI\PageLink;
use App\Enums\HeaderType;
use App\Page\PageMeta;
use App\Repositories\TournamentRepository;
use App\Utilities\EntitySorter;

$pageMeta = new PageMeta(bodyClass: 'home');

$tournamentRepo = new TournamentRepository();
$tournaments = $tournamentRepo->findAllRootTournaments();
$tournaments = EntitySorter::sortTournamentsByStartDate($tournaments);

echo new Header(HeaderType::HOME);
?>
<main>
	<div id="turnier-select">
        <?= new PageLink('/spieler','Spieler', materialIcon: 'person')?>
        <div id="turnier-liste">
		    <h2>Turniere</h2>
            <?php foreach ($tournaments as $i=>$tournament):?>
				<?= ($i != 0) ? '<div class="divider"></div>' : '' ?>
                <a href="/turnier/<?= $tournament->id ?>" class="turnier-button <?= $tournament->id ?>">
                    <?php if($tournament->getLogoUrl()): ?>
                        <img class='color-switch' alt src='<?= $tournament->getLogoUrl() ?>'>
                    <?php else: ?>
                        <img alt src="data:image/gif;base64,R0lGODlhAQABAAD/ACwAAAAAAQABAAACADs%3D">
                    <?php endif; ?>
                    <span><?= $tournament->getShortName() ?></span>
				</a>
            <?php endforeach; ?>
        </div>
	</div>
</main>