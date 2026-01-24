<?php
/** @var array<\App\Domain\Entities\TeamInTournamentStage> $teamInTournamentStages */
/** @var \App\Domain\Entities\TeamInTournamentStage $activeStage */
?>

<div id='teampage_switch_group_buttons' <?= (count($teamInTournamentStages)<2) ? "style='display:none'" : "" ?>>
    <?php
    foreach ($teamInTournamentStages as $teamInTournamentStage) {
        $classes = implode(' ', array_filter(['teampage_switch_group', $teamInTournamentStage->tournamentStage->id === $activeStage->tournamentStage->id ? "active" : ""]));
        ?>
        <button type='button' class='<?=$classes?>' data-group='<?=$teamInTournamentStage->tournamentStage->id?>' data-team='<?=$teamInTournamentStage->team->id?>>'>
            <?= $teamInTournamentStage->tournamentStage->getFullName()?>
        </button>
    <?php
    }
    ?>
</div>