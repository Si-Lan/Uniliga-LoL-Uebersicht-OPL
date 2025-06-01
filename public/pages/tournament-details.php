<?php

use App\Domain\Enums\EventFormat;
use App\Domain\Enums\EventType;
use App\Domain\Repositories\TournamentRepository;
use App\UI\Components\Navigation\Header;
use App\UI\Components\Navigation\TournamentNav;
use App\UI\Components\UI\PageLink;
use App\UI\Enums\HeaderType;
use App\UI\Page\PageMeta;

$tournamentRepo = new TournamentRepository();

$tournament = $tournamentRepo->findById($_GET['tournament']);

$pageMeta = new PageMeta($tournament->getShortName(),bodyClass: 'tournament');

?>

<?= new Header(HeaderType::TOURNAMENT, $tournament) ?>

<?= new TournamentNav($tournament,'overview') ?>

<?php
$leagues = $tournamentRepo->findAllByRootTournamentAndType($tournament, EventType::LEAGUE);
$leagues = array_values(array_filter($leagues, function($league) {return !$league->deactivated;}));
$groups_active = (count($leagues)>0) ? "active" : "";
$wildcards = $tournamentRepo->findAllByRootTournamentAndType($tournament, EventType::WILDCARD);
$wildcards = array_values(array_filter($wildcards, function($wildcard) {return !$wildcard->deactivated;}));
$wildcard_active = (count($wildcards)>0 && count($leagues)==0) ? "active" : "";
$playoffs = $tournamentRepo->findAllByParentTournamentAndType($tournament, EventType::PLAYOFFS);
$playoffs = array_values(array_filter($playoffs, function($playoff) {return !$playoff->deactivated;}));
$playoffs_active = (count($wildcards)==0 && count($leagues)==0) ? "active" : "";
?>
<main>
    <h2 class='pagetitle'>Turnier-Details</h2>

    <?php if (count($leagues) > 0 ? (count($wildcards) > 0 || count($playoffs) > 0) : (count($wildcards) > 0 && count($playoffs) > 0)): ?>
        <div id="tournamentpage_switch_stage_buttons">
            <?php if (count($wildcards) > 0): ?>
                <button type="button" class="tournamentpage_switch_stage <?php echo $wildcard_active?>" data-stage="wildcard">Wildcard-Turnier</button>
            <?php endif; ?>
            <?php if (count($leagues) > 0): ?>
                <button type="button" class="tournamentpage_switch_stage <?php echo $groups_active?>" data-stage="groups">Gruppenphase</button>
            <?php endif; ?>
            <?php if (count($playoffs) > 0): ?>
                <button type="button" class="tournamentpage_switch_stage <?php echo $playoffs_active?>" data-stage="playoffs">Playoffs</button>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class='divisions-list-wrapper'>
        <div class='divisions-list groups'>
            <?php foreach ($leagues as $league): ?>

                <?php if ($league->format == EventFormat::SWISS): ?>
                    <div class='division'>
                        <div class='group-title-wrapper'>
                            <h3><?= $league->getShortName() ?></h3>
                        </div>
                        <div class='groups'>
                            <div class='group'>
                                <span class="group-title">Gruppe</span>
                                <div class="divider-vert-acc"></div>
                                <?= new PageLink("/turnier/{$tournament->id}/gruppe/{$league->id}",'Details')?>
                                <div class="divider-vert-acc"></div>
                                <?= new PageLink("/turnier/{$tournament->id}/teams?liga={$league->id}", 'Teams', materialIcon: 'group')?>
                            </div>
                        </div>
                    </div>
                <?php continue; ?>
                <?php endif; ?>

                <?php
                $groups = $tournamentRepo->findAllByParentTournamentAndType($league, EventType::GROUP);
                $groups = array_values(array_filter($groups, function($group) {return !$group->deactivated;}));
                ?>
                <div class='division'>
                    <div class='group-title-wrapper'>
                        <h3><?= $league->getShortName() ?></h3>
                    </div>
                    <div class="groups">
                        <?php foreach ($groups as $g_i=>$group) :?>

                            <?php if ($g_i != 0): ?>
                                <div class="divider-light"></div>
                            <?php endif; ?>

                            <div class="group">
                                <span class="group-title"><?= $group->getShortName() ?></span>
                                <div class="divider-vert-acc"></div>
                                <?= new PageLink("/turnier/{$tournament->id}/gruppe/{$group->id}",'Details') ?>
                                <div class="divider-vert-acc"></div>
                                <?= new PageLink("/turnier/{$tournament->id}/teams?liga={$league->id}&gruppe={$group->id}", 'Teams', materialIcon: 'group') ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <div class='divisions-list wildcard'<?= (!$wildcard_active) ? " style='display: none'" : "" ?>>
            <div class='division'>
                <div class='group-title-wrapper'>
                    <h3>Wildcard</h3>
                </div>
                <div class="groups">
                    <?php foreach ($wildcards as $i=>$wildcard): ?>

                        <?php if ($i != 0): ?>
							<div class="divider-light"></div>
                        <?php endif; ?>
                        <div class="group">
                            <span class="group-title"><?= $wildcard->getShortName() ?></span>
                            <div class="divider-vert-acc"></div>
							<?= new PageLink("/turnier/{$tournament->id}/wildcard/{$wildcard->id}",'Details') ?>
                            <div class="divider-vert-acc"></div>
							<?= new PageLink("/turnier/{$tournament->id}/teams?liga={$wildcard->id}", 'Teams', materialIcon: 'group') ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <div class='divisions-list playoffs'<?= (!$playoffs_active) ? " style='display: none'" : "" ?>>
            <div class='division'>
                <div class='group-title-wrapper'>
                    <h3>Playoffs</h3>
                </div>
                <div class="groups">
					<?php foreach ($playoffs as $i=>$playoff): ?>

                        <?php if ($i != 0): ?>
                            <div class="divider-light"></div>
                        <?php endif; ?>
                        <div class="group">
                            <span class="group-title"><?= $playoff->getShortName() ?></span>
                            <div class="divider-vert-acc"></div>
							<?= new PageLink("/turnier/{$tournament->id}/playoffs/{$playoff->id}",'Details') ?>
                            <div class="divider-vert-acc"></div>
							<?= new PageLink("/turnier/{$tournament->id}/teams?liga={$playoff->id}", 'Teams', materialIcon: 'group') ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</main>