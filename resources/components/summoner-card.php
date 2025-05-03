<?php
/** @var array{
 *     OPL_ID: int
 * } $tournament */
/** @var \App\Entity\PlayerInTeamInTournament $playerTT */
/** @var array{
 *     array{rank_tier: string, rank_div: string, rank_LP: int},
 *     array{rank_tier: string, rank_div: string, rank_LP: int}
 * } $player_rank */
/** @var string $current_split */
/** @var bool $collapsed */
/** @var string $latest_patch */

$player_tier = $player_rank[0]['rank_tier'] ?? null;
$player_div = $player_rank[0]['rank_div'] ?? null;
$player_LP = NULL;
if ($player_tier == "CHALLENGER" || $player_tier == "GRANDMASTER" || $player_tier == "MASTER") {
	$player_div = "";
	$player_LP = $player_rank[0]["rank_LP"] ?? null;
}
$player_tier_2 = $player_rank[1]['rank_tier'] ?? null;
$player_div_2 = $player_rank[1]['rank_div'] ?? null;
$player_LP_2 = NULL;
if ($player_tier_2 == "CHALLENGER" || $player_tier_2 == "GRANDMASTER" || $player_tier_2 == "MASTER") {
	$player_div_2 = "";
	$player_LP_2 = $player_rank[1]["rank_LP"] ?? null;
}

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
				<span class="league-icon"><?=\App\Helpers\IconRenderer::getLeagueIcon()?></span>
				<span class="riot-id"><?=$playerTT->player->riotIdName?><span class="riot-id-tag"><?=$playerTT->player->getRiotIdTagWithPrefix()?></span></span>
			</span>
			<?php
			}
			?>
            <?php
			if ($current_split == ($player_rank[1]["season"]??"")."-".($player_rank[1]["split"]??"")) {
				$rank_hide_1 = "display: none";
				$rank_hide_2 = "";
			} else {
				$rank_hide_1 = "";
				$rank_hide_2 = "display: none";
			}
            ?>
            <?php if ($player_tier != null) {
				if ($player_LP != NULL) {
					$player_LP = "(".$player_LP." LP)";
				} else {
					$player_LP = "";
				}
                $rank_classes = implode(" ", ["card-rank", "split_rank_element", "ranked-split-{$player_rank[0]["season"]}-{$player_rank[0]["split"]}"])?>
            <div class="<?=$rank_classes?>" style="<?=$rank_hide_1?>">
                <img class='rank-emblem-mini' src='/ddragon/img/ranks/mini-crests/<?=$player_tier?>.svg' alt='<?=ucfirst($player_tier)?>'>
                <?=ucfirst($player_tier)." ".$player_div." ".$player_LP?>
            </div>
            <?php
            }
            ?>
			<?php if ($player_tier_2 != null) {
				if ($player_LP_2 != NULL) {
					$player_LP_2 = "(".$player_LP_2." LP)";
				} else {
					$player_LP_2 = "";
				}
				$rank_classes = implode(" ", ["card-rank", "split_rank_element", "ranked-split-{$player_rank[1]["season"]}-{$player_rank[1]["split"]}"])?>
                <div class="<?=$rank_classes?>" style="<?=$rank_hide_2?>">
                    <img class='rank-emblem-mini' src='/ddragon/img/ranks/mini-crests/<?=$player_tier_2?>.svg' alt='<?=ucfirst($player_tier_2)?>'>
					<?=ucfirst($player_tier_2)." ".$player_div_2." ".$player_LP_2?>
                </div>
				<?php
			}
			?>

			<!-- Stats kommt hier noch rein -->
            <div class="played-positions">
                <?php
                foreach ($playerTT->roles as $role=>$role_amount) {
                    if ($role_amount != 0) { ?>
                <div class="role-single">
                    <div class="svg-wrapper role"><?=\App\Helpers\IconRenderer::getRoleIcon($role)?></div>
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
                    <img src='/ddragon/<?=$latest_patch?>/img/champion/<?=$champion?>.webp' alt='<?=$champion?>'>
                    <span class="played-amount"><?=$champion_amount['games']?></span>
                </div>
				    <?php
				}
                if (count($playerTT->champions) > 5) {
                ?>
                <div class="champ-single">
                    <?=\App\Helpers\IconRenderer::getMaterialIconDiv("more_horiz")?>
                </div>
                <?php
				}
				?>
            </div>

		</div>
	</div>
	<a href="javascript:void(0)" class="open-playerhistory" onclick="popup_player('<?=$playerTT->player->id?>')">Spieler-Details</a>
	<a href="https://www.op.gg/summoners/euw/<?=$playerTT->player->getEncodedRiotID()?>" target="_blank" class="op-gg-single"><div class='svg-wrapper op-gg'><?=\App\Helpers\IconRenderer::getOPGGIcon()?></div></a>
</div>
