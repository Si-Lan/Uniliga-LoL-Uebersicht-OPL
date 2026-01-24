<?php
/** @var \App\Domain\Entities\TeamSeasonRankInTournament|\App\Domain\Entities\Team $teamRank */
/** @var string $classes */
/** @var string $displayStyle */
/** @var string $src */
/** @var bool $withLabel */
?>
<span class='<?= $classes ?>' style='<?=$displayStyle?>'>
	<?php if ($withLabel): ?>
    Team-Rang:
	<?php endif; ?>
    <img class='rank-emblem-mini' src='<?=$src?>' alt='<?=$teamRank->rank->getRankTier()?>'>
    <span><?= $teamRank->rank->getRank()?></span>
</span>
