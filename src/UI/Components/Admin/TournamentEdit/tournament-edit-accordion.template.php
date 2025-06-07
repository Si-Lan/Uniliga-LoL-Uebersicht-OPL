<?php
/** @var \App\Domain\Entities\Tournament $tournament */
/** @var array<string> $openAccordeons */
/** @var array<\App\Domain\Entities\Tournament> $events */

use App\Domain\Enums\EventType;
use App\UI\Components\Admin\TournamentEdit\TournamentEditAccordion;
use App\UI\Components\Admin\TournamentEdit\TournamentEditForm;
use App\UI\Components\Helpers\IconRenderer;

?>

<span class="tsl-heading">
	<h4>
        <?= ($tournament->eventType  !== EventType::TOURNAMENT) ? $tournament->getShortName().'-' : '' ?>
        <?= $events[0]->eventType->getPrettyNamePlural() ?>
    </h4>
	<?php $classes = implode(' ', array_filter(['toggle-turnierselect-accordeon', in_array("$tournament->id-{$events[0]->eventType->value}", $openAccordeons) ? 'open':'']))?>
	<button class='<?=$classes?>' type='button' data-id='<?=$tournament->id?>-<?=$events[0]->eventType->value?>'><?= IconRenderer::getMaterialIconDiv('expand_more')?></button>
</span>

<?php $classes = implode(' ', array_filter(['turnierselect-accordeon', "$tournament->id-{$events[0]->eventType->value}", in_array("$tournament->id-{$events[0]->eventType->value}", $openAccordeons) ? 'open':'']))?>
<div class='<?=$classes?>'>
	<div class='turnierselect-accordeon-content'>
        <?php if (!$events[0]->eventType->hasChildren()): ?>
            <?php foreach ($events as $event): ?>
                <?= new TournamentEditForm($event) ?>
            <?php endforeach; ?>
        <?php else: ?>
            <?php foreach ($events as $event): ?>
                <h5><?= $event->getShortName() ?></h5>
                <?= new TournamentEditForm($event) ?>

                <?= new TournamentEditAccordion($event, null, $openAccordeons) ?>
            <?php endforeach; ?>
        <?php endif; ?>
	</div>
</div>