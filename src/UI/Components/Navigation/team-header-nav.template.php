<?php
/** @var \App\Domain\Entities\TeamInTournament $teamInTournament */
/** @var string $activeTab */

use App\UI\Components\Helpers\IconRenderer;
use App\UI\Components\OplOutLink;
use App\UI\Components\UI\PageLink;
use App\UI\Components\UpdateButton;

?>

<div class='team pagetitle'>
    <?php
    $src = $teamInTournament->getLogoUrl();
    if ($src) {
	?>
	<img class='color-switch' alt src='<?= $src ?>'>
    <?php
	}
    ?>
	<div>
		<h2 class='pagetitle'>
            <?= new PageLink("/team/{$teamInTournament->team->getSlug()}",$teamInTournament->nameInTournament)?>
		</h2>
        <?= new OplOutLink($teamInTournament->team)?>
	</div>
	<?php
	if (!$teamInTournament->tournament->archived && $activeTab === 'details') {
        echo new UpdateButton($teamInTournament);
	}
?>
</div>
<nav class='team-titlebutton-wrapper'>
	<a href='<?=$teamInTournament->tournament->getHref()?>/team/<?=$teamInTournament->team->getSlug()?>' class='<?= $activeTab === 'details' ? 'active' : ''?>'>
        <?= IconRenderer::getMaterialIconDiv('info')?>
		Team-Übersicht
	</a>
	<a href='<?=$teamInTournament->tournament->getHref()?>/team/<?=$teamInTournament->team->getSlug()?>/matchhistory' class='<?= $activeTab === 'matchhistory' ? 'active' : ''?>'>
        <?= IconRenderer::getMaterialIconDiv('manage_search')?>
		Match-History
	</a>
	<a href='<?=$teamInTournament->tournament->getHref()?>/team/<?=$teamInTournament->team->getSlug()?>/stats' class='<?= $activeTab === 'stats' ? 'active' : ''?>'>
        <?= IconRenderer::getMaterialIconDiv('monitoring')?>
		Statistiken
	</a>
</nav>