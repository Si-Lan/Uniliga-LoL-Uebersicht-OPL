<?php
/** @var \App\Entity\PlayerInTeamInTournament $playerTT */
/** @var array<\App\Entity\PlayerSeasonRank> $playerRanks */
/** @var \App\Entity\RankedSplit $currentSplit */
/** @var \App\Entity\Patch $latestPatch */
/** @var bool $collapsed */

?>

<div class='summoner-card-wrapper'>
	<?php $card_classes = implode(' ',array_filter(["summoner-card", $playerTT->player->id, $collapsed?"collapsed":"", ($playerTT->removed == 1) ? "player-removed" : ""])) ?>
	<div class="<?=$card_classes?>" onclick="player_to_opgg_link('<?=$playerTT->player->id?>','<?=$playerTT->player->getFullRiotID()?>')">
		<input type='checkbox' name='OPGG' <?=$playerTT->player->riotIdName==null ? "disabled":""?> class='opgg-checkbox' <?=($playerTT->player->riotIdName!=null && !$playerTT->removed)? "checked":"" ?>>
		<span class="card-player"><?=$playerTT->player->name?></span>
		<div class='divider'></div>
		<div class="card-summoner">
			<?php if ($playerTT->player->riotIdName != null) { ?>
			<span class="card-riotid">
				<span class="league-icon"><?= \App\Components\Helpers\IconRenderer::getLeagueIcon()?></span>
				<span class="riot-id"><?=$playerTT->player->riotIdName?><span class="riot-id-tag"><?=$playerTT->player->getRiotIdTagWithPrefix()?></span></span>
			</span>
			<?php
			}
			?>
            <?php
            foreach ($playerRanks as $playerRank) {
                if ($playerRank != null) {
                    $rank_classes = implode(" ", ["card-rank", "split_rank_element", "ranked-split-{$playerRank->season}-{$playerRank->split}"]);
                    $css_style = $currentSplit->equals($playerRank->rankedSplit) ? "" : "display: none";?>
            <div class="<?=$rank_classes?>" style="<?=$css_style?>">
                <img class='rank-emblem-mini' src='/ddragon/img/ranks/mini-crests/<?=$playerRank->getRankTierLC()?>.svg' alt='<?=$playerRank->getRankTierUC()?>'>
                <?=$playerRank->getFullRank()?>
            </div>
            <?php
                }
			}
            ?>

			<!-- Stats kommt hier noch rein -->
            <div class="played-positions">
                <?php
                foreach ($playerTT->roles as $role=>$role_amount) {
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
				foreach ($playerTT->getTopChampions(5) as $champion=>$champion_amount) {
                    ?>
                <div class="champ-single">
                    <img src='/ddragon/<?=$latestPatch->patchNumber?>/img/champion/<?=$champion?>.webp' alt='<?=$champion?>'>
                    <span class="played-amount"><?=$champion_amount['games']?></span>
                </div>
				    <?php
				}
                if (count($playerTT->champions) > 5) {
                ?>
                <div class="champ-single">
                    <?= \App\Components\Helpers\IconRenderer::getMaterialIconDiv("more_horiz")?>
                </div>
                <?php
				}
				?>
            </div>

		</div>
	</div>
	<a href="javascript:void(0)" class="open-playerhistory" onclick="popup_player('<?=$playerTT->player->id?>')">Spieler-Details</a>
	<a href="https://www.op.gg/summoners/euw/<?=$playerTT->player->getEncodedRiotID()?>" target="_blank" class="op-gg-single"><div class='svg-wrapper op-gg'><?= \App\Components\Helpers\IconRenderer::getOPGGIcon()?></div></a>
</div>
