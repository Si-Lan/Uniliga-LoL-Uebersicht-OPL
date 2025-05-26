<?php

use App\Domain\Repositories\TournamentRepository;
use App\Domain\Services\EntitySorter;
use App\UI\Components\Navigation\Header;
use App\UI\Components\UI\PageLink;
use App\UI\Enums\HeaderType;
use App\UI\Page\PageMeta;

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