<?php
use App\Domain\Entities\Matchup;
use App\Domain\Entities\Team;
use App\Domain\Entities\Tournament;
use App\UI\Components\Helpers\IconRenderer;
use App\UI\Components\Matches\MatchButton;
use App\UI\Components\UI\PageLink;

/** @var Tournament $tournamentStage */
/** @var array<array<Matchup>> $matchesByColumn */
/** @var Team|null $selectedTeam */
/** @var int $hiddenColumnsStart */
/** @var int $hiddenColumnsEnd */
?>
<div class="bracket">
    <div class="title">
        <h3>
            Standings
            <?php
            if ($this->selectedTeam !== null) {
                $groupLinkUrl = "/turnier/".$this->tournamentStage->rootTournament->id."/".$this->tournamentStage->getUrlKey()."/".$this->tournamentStage->id;
                echo new PageLink($groupLinkUrl, $this->tournamentStage->getFullName());
            }
            ?>
        </h3>
    </div>
    <div class="elimination-bracket double-elimination-bracket" data-stage="<?= $tournamentStage->id ?>">
        <svg class="bracket-lines"></svg>
        <button type="button" class="show_columns_left"><?= IconRenderer::getMaterialIcon("chevron_left") ?></button>

        <?php foreach ($matchesByColumn as $columnNum => $column): ?>
            <div class="bracket_column <?= $columnNum < $hiddenColumnsStart || $columnNum >= $hiddenColumnsEnd ? "hidden" : "" ?>">
                <button type="button" class="hide_column">ausblenden</button>
                <div class="upper_matches column_matches">
                    <?php foreach ($column["upper"] as $match): ?>
                        <div class="bracket-match"
                             data-id="<?= $match->id ?>"
                             data-column="<?= $match->bracketColumn ?>"
                                <?php foreach ($match->bracketPrevMatchups as $i => $prevMatchup) : ?>
                                    data-prev<?= $i ?>="<?= $prevMatchup?->id ?>"
                                <?php endforeach; ?>
                                <?php foreach ($match->bracketNextMatchups as $i => $nextMatchup) : ?>
                                    data-next<?= $i ?>="<?= $nextMatchup?->id ?>"
                                <?php endforeach; ?>
                        >
                            <?= new MatchButton($match, $selectedTeam) ?>

                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="bracket-divider"></div>
                <div class="lower_matches column_matches">
                    <?php foreach ($column["lower"] as $match): ?>
                        <div class="bracket-match"
                             data-id="<?= $match->id ?>"
                             data-column="<?= $match->bracketColumn ?>"
                                <?php foreach ($match->bracketPrevMatchups as $i => $prevMatchup) : ?>
                                    data-prev<?= $i ?>="<?= $prevMatchup?->id ?>"
                                <?php endforeach; ?>
                                <?php foreach ($match->bracketNextMatchups as $i => $nextMatchup) : ?>
                                    data-next<?= $i ?>="<?= $nextMatchup?->id ?>"
                                <?php endforeach; ?>
                        >
                            <?= new MatchButton($match, $selectedTeam) ?>

                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <button type="button" class="show_columns_right"><?= IconRenderer::getMaterialIcon("chevron_right") ?></button>
    </div>
</div>