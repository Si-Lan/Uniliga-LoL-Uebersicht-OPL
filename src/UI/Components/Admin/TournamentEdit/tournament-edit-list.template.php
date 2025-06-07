<?php
/** @var \App\Domain\Repositories\TournamentRepository $tournamentRepo */
/** @var array<string> $openAccordeons */

use App\Domain\Enums\EventType;
use App\Domain\Services\EntitySorter;
use App\UI\Components\Admin\TournamentEdit\TournamentEditAccordion;
use App\UI\Components\Admin\TournamentEdit\TournamentEditForm;
use App\UI\Components\Helpers\IconRenderer;

$unassignedTournaments = $tournamentRepo->findAllUnassignedTournaments();
$tournaments = EntitySorter::sortTournamentsByStartDate($tournamentRepo->findAllRootTournaments());
?>

<button class='refresh-button refresh-tournaments'>Refresh</button>
<div class='turnier-select-list'>
	<?php foreach ($tournaments as $tournament): ?>

		<span class='tsl-heading'>
			<h3><?=$tournament->name?></h3>
			<?php $classes = implode(' ', array_filter(['toggle-turnierselect-accordeon', in_array($tournament->id, $openAccordeons) ? 'open':'']))?>
			<button class='<?=$classes?>' type='button' data-id='<?=$tournament->id?>'><?= IconRenderer::getMaterialIconDiv('expand_more')?></button>
		</span>

		<?php $classes = implode(' ', array_filter(['turnierselect-accordeon', $tournament->id, in_array($tournament->id, $openAccordeons) ? 'open':'']))?>
		<div class='<?=$classes?>'>
			<div class='turnierselect-accordeon-content'>

				<?= new TournamentEditForm($tournament)?>



                <?= new TournamentEditAccordion($tournament, EventType::LEAGUE, $openAccordeons) ?>
                <?= new TournamentEditAccordion($tournament, EventType::PLAYOFFS, $openAccordeons) ?>
                <?= new TournamentEditAccordion($tournament, EventType::WILDCARD, $openAccordeons) ?>

            </div>
		</div>
	<?php endforeach; ?>

    <?php if (count($unassignedTournaments) > 0): ?>
        <h3>Nicht zugewiesene Turniere:</h3>
        <?php foreach ($unassignedTournaments as $event): ?>

            <?= new TournamentEditForm($event)?>

        <?php endforeach; ?>

    <?php endif; ?>
</div>