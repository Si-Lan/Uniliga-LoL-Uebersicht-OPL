<?php
/** @var \App\Entities\PlayerInTeamInTournament $playerTT */
/** @var \App\Entities\Player $player */
/** @var array<\App\Entities\PlayerSeasonRank> $playerRanks */
/** @var \App\Entities\RankedSplit $currentSplit */
/** @var \App\Entities\Patch $latestPatch */
/** @var bool $collapsed */

?>

<div class='summoner-card-wrapper'>
	<?php
	$removed = ($playerTT != null) ? $playerTT->removed : false;
	$card_classes = implode(' ',array_filter(["summoner-card", $player->id, $collapsed?"collapsed":"", $removed?"player-removed":""]));
    ?>
	<div class="<?=$card_classes?>" onclick="player_to_opgg_link('<?=$player->id?>','<?=$player->getFullRiotID()?>')">
		<input type='checkbox' name='OPGG' <?=$player->riotIdName==null ? "disabled":""?> class='opgg-checkbox' <?=($player->riotIdName!=null && !$removed)? "checked":"" ?>>
		<span class="card-player"><?=$player->name?></span>
		<div class='divider'></div>
		<div class="card-summoner">
			<?php if ($player->riotIdName != null) { ?>
			<span class="card-riotid">
				<span class="league-icon"><?= \App\Components\Helpers\IconRenderer::getLeagueIcon()?></span>
				<span class="riot-id"><?=$player->riotIdName?><span class="riot-id-tag"><?=$player->getRiotIdTagWithPrefix()?></span></span>
			</span>
			<?php
			}
			?>
            <?php
            if ($playerTT == null && $player->rank->rankTier != null) {
                ?>
            <div class="card-rank">
                <img class='rank-emblem-mini' src='/ddragon/img/ranks/mini-crests/<?=$player->rank->getRankTierLowercase()?>.svg' alt='<?=$player->rank->getRankTier()?>'>
    			<?=$player->rank->getRank()?>
            </div>
            <?php
            }
            foreach ($playerRanks as $playerRank) {
                if ($playerRank != null) {
                    $rank_classes = implode(" ", ["card-rank", "split_rank_element", "ranked-split-{$playerRank->rankedSplit->season}-{$playerRank->rankedSplit->split}"]);
                    $css_style = $currentSplit->equals($playerRank->rankedSplit) ? "" : "display: none";?>
            <div class="<?=$rank_classes?>" style="<?=$css_style?>">
                <img class='rank-emblem-mini' src='/ddragon/img/ranks/mini-crests/<?=$playerRank->rank->getRankTierLowercase()?>.svg' alt='<?=$playerRank->rank->getRankTier()?>'>
                <?=$playerRank->rank->getRank()?>
            </div>
            <?php
                }
			}
            if ($playerTT != null) {
            ?>
            <div class="played-positions">
                <?php
                foreach ($playerTT->stats->roles as $role=>$role_amount) {
                    if ($role_amount != 0) { ?>
                <div class="role-single">
                    <div class="svg-wrapper role"><?= \App\Components\Helpers\IconRenderer::getRoleIcon($role)?></div>
                    <span class="played-amount"><?=$role_amount?></span>
                </div>
                        <?php
                    }
                }
                ?>
            </div>
            <div class="played-champions">
				<?php
				foreach ($playerTT->stats->getTopChampions(5) as $champion=>$champion_amount) {
                    ?>
                <div class="champ-single">
                    <img src='/ddragon/<?=$latestPatch->patchNumber?>/img/champion/<?=$champion?>.webp' alt='<?=$champion?>'>
                    <span class="played-amount"><?=$champion_amount['games']?></span>
                </div>
				    <?php
				}
                if (count($playerTT->stats->champions) > 5) {
                ?>
                <div class="champ-single">
                    <?= \App\Components\Helpers\IconRenderer::getMaterialIconDiv("more_horiz")?>
                </div>
                <?php
				}
				?>
            </div>
            <?php
            }
            ?>
		</div>
	</div>
	<a href="javascript:void(0)" class="open-playerhistory" onclick="popup_player('<?=$player->id?>')">Spieler-Details</a>
	<a href="https://www.op.gg/summoners/euw/<?=$player->getEncodedRiotID()?>" target="_blank" class="op-gg-single"><div class='svg-wrapper op-gg'><?= \App\Components\Helpers\IconRenderer::getOPGGIcon()?></div></a>
</div>
