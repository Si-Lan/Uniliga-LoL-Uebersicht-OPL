<?php
/** @var \App\Entities\TeamInTournament $teamInTournament */
/** @var string $activeTab */

use App\Components\Helpers\IconRenderer;
use App\Components\OplOutLink;
use App\Components\UI\PageLink;
use App\Components\UpdateButton;

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
            <?= new PageLink("/team/{$teamInTournament->team->id}",$teamInTournament->nameInTournament)?>
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
	<a href='/turnier/<?=$teamInTournament->tournament->id?>/team/<?=$teamInTournament->team->id?>' class='<?= $activeTab === 'details' ? 'active' : ''?>'>
        <?= IconRenderer::getMaterialIconDiv('info')?>
		Team-Übersicht
	</a>
	<a href='/turnier/<?=$teamInTournament->tournament->id?>/team/<?=$teamInTournament->team->id?>/matchhistory' class='<?= $activeTab === 'matchhistory' ? 'active' : ''?>'>
        <?= IconRenderer::getMaterialIconDiv('manage_search')?>
		Match-History
	</a>
	<a href='/turnier/<?=$teamInTournament->tournament->id?>/team/<?=$teamInTournament->team->id?>/stats' class='<?= $activeTab === 'stats' ? 'active' : ''?>'>
        <?= IconRenderer::getMaterialIconDiv('monitoring')?>
		Statistiken
	</a>
</nav>