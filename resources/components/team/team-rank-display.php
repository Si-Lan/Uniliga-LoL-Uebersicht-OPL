<?php
/** @var \App\Entities\TeamSeasonRankInTournament $teamSeasonRankInTournament */
/** @var string $classes */
/** @var string $displayStyle */
/** @var string $src */
/** @var bool $withLabel */
?>
<span class='<?= $classes ?>' style='<?=$displayStyle?>'>
	<?php if ($withLabel): ?>
    Team-Rang:
	<?php endif; ?>
    <img class='rank-emblem-mini' src='<?=$src?>' alt='<?=$teamSeasonRankInTournament->rank->getRankTier()?>'>
    <span><?= $teamSeasonRankInTournament->rank->getRank()?></span>
</span>
