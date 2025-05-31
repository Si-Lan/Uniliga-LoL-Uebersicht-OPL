<?php
/** @var \App\UI\Components\Games\GameDetails $this */
/** @var \App\Domain\Entities\LolGame\GameData $gameData */
/** @var \App\Domain\Entities\Team|null $currentTeam */
/** @var \App\Domain\Entities\GameInMatch $gameInMatch */
/** @var \App\Domain\Entities\Patch $patch */
/** @var \App\Domain\Entities\RankedSplit $currentSplit */

use App\UI\Components\Helpers\IconRenderer;

$teamFocus = !is_null($currentTeam);

$blueTeamScoreText = ($gameData->blueTeamWin) ? 'Victory' : 'Defeat';
$redTeamScoreText = ($gameData->redTeamWin) ? 'Victory' : 'Defeat';
$teamScoreTexts = [$blueTeamScoreText, $redTeamScoreText];

$blueTeamScoreClass = ($gameData->blueTeamWin) ? 'win' : 'loss';
$redTeamScoreClass = ($gameData->redTeamWin) ? 'win' : 'loss';
$teamScoreClasses = [$blueTeamScoreClass, $redTeamScoreClass];

$currentTeamIsBlueClass = ($teamFocus && $currentTeam->equals($gameInMatch->blueTeam->team)) ? 'current' : '';
$currentTeamIsRedClass = ($teamFocus && $currentTeam->equals($gameInMatch->redTeam->team)) ? 'current' : '';
$currentTeamClasses = [$currentTeamIsBlueClass, $currentTeamIsRedClass];

if ($teamFocus && $currentTeam->equals($gameInMatch->getWinningTeam()->team)) {
	$resultWrapperClass = 'win';
    $miniScoreText = 'Victory';
} elseif ($teamFocus && $currentTeam->equals($gameInMatch->getLosingTeam()->team)) {
	$resultWrapperClass = 'loss';
	$miniScoreText = 'Defeat';
} else {
    $resultWrapperClass = 'general';
	$miniScoreText = $gameInMatch->getWinningTeam()->nameInTournament;
}


?>

<div class="game-wrapper collapsed">
	<div class="game-details-mini <?= $resultWrapperClass ?>">

		<div class="game-information">
            <span><?= $gameData->gameStart ?></span>
			<div class="game-result-text">
				<span class="game-result <?= $resultWrapperClass ?>"><?=$miniScoreText?></span>
			</div>
			<span><?= $gameData->gameDuration ?></span>
		</div>

        <?php $classes = implode(' ', array_filter(['team', $blueTeamScoreClass, $currentTeamIsBlueClass])) ?>
        <a class="<?=$classes?>" href="/turnier/<?= $gameInMatch->matchup->tournamentStage->rootTournament->id ?>/team/<?= $gameInMatch->blueTeam->team->id ?>">
			<?php if ($gameInMatch->blueTeam->getLogoUrl(true)): ?>
                <img class="color-switch" alt="" src="<?= $gameInMatch->blueTeam->getLogoUrl(true) ?>">
			<?php endif; ?>
            <span><?= $gameInMatch->blueTeam->nameInTournament ?></span>
        </a>

        <?php foreach ([$gameData->blueTeamPlayers, $gameData->redTeamPlayers] as $teamPlayers): ?>
            <div class="players">
				<?php /** @var \App\Domain\Entities\LolGame\GamePlayerData $player */
				foreach ($teamPlayers as $player): ?>
                <?php $nameAndTag = $this->findPlayersGameNameAndTag($player); ?>
                    <div class="player">

                        <img loading="lazy" alt="" title="<?=$player->championName?>" src="/assets/ddragon/<?=$patch->patchNumber?>/img/champion/<?=$player->championName?>.webp" class="champ">
                        <div class="tooltip">
                            <span class="player-name"><?= $nameAndTag[0] ?></span>
                            <?php if ($nameAndTag[1]): ?>
                                <span class="tooltiptext riot-id"><?= $nameAndTag[0] ?>#<?= $nameAndTag[1] ?></span>
                            <?php endif; ?>
                        </div>

                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>


		<?php $classes = implode(' ', array_filter(['team', $redTeamScoreClass, $currentTeamIsRedClass])) ?>
        <a class="<?=$classes?>" href="/turnier/<?= $gameInMatch->matchup->tournamentStage->rootTournament->id ?>/team/<?= $gameInMatch->redTeam->team->id ?>">
            <?php if ($gameInMatch->redTeam->getLogoUrl(true)): ?>
                <img class="color-switch" alt="" src="<?= $gameInMatch->redTeam->getLogoUrl(true) ?>">
            <?php endif; ?>
            <span><?= $gameInMatch->redTeam->nameInTournament ?></span>
        </a>

        <button class='expand-game-details'>
            <?= IconRenderer::getMaterialIconDiv('expand_less')?>
        </button>

	</div>


	<div class="game-details">

        <div class="game-row teams">
			<?php /** @var \App\Domain\Entities\TeamInTournament $team */
			foreach ([$gameInMatch->blueTeam, $gameInMatch->redTeam] as $index=> $team): ?>
				<?php $classes = implode(' ', array_filter(['team', $index+1, $teamScoreClasses[$index], $currentTeamClasses[$index]])) ?>
                <a class="<?=$classes?>" href="/turnier/<?= $gameInMatch->matchup->tournamentStage->rootTournament->id ?>/team/<?= $team->team->id ?>">
                    <div class="name">
						<?php if ($team->getLogoUrl(true)): ?>
                            <img class="color-switch" alt="" src="<?= $team->getLogoUrl(true) ?>">
						<?php endif; ?>
						<?= $team->nameInTournament ?>
                    </div>
					<?php $classes = implode(' ', array_filter(['score', $teamScoreClasses[$index]])) ?>
                    <div class="<?=$classes?>">
						<?= $teamScoreTexts[$index] ?>
                    </div>
                </a>
                <?php if ($index == 0): ?>
                    <div class='time'>
                        <div><?= $gameData->gameDuration ?></div>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>

        <div class='game-row team-stats'>
			<?php /** @var \App\Domain\Entities\LolGame\GameTeamData $team */
			foreach ([$gameData->blueTeam, $gameData->redTeam] as $index=> $team): ?>
                <div class='stats-wrapper'>
                    <span><img src='/assets/ddragon/img/kills.png' class='stats kills' alt=''><?= $team->kills ?> / <?= $team->deaths ?> / <?= $team->assists ?></span>
                    <span><img src='/assets/ddragon/img/icon_gold.png' class='stats gold' alt=''><?= $team->getGoldEarnedFormatted() ?></span>
                </div>
	    		<?php if ($index == 0): ?><div class='game-row-divider'></div><?php endif; ?>
		    <?php endforeach; ?>
        </div>

        <div class='game-row objectives'>
			<?php /** @var \App\Domain\Entities\LolGame\GameTeamData $team */
			foreach ([$gameData->blueTeam, $gameData->redTeam] as $index=> $team): ?>
                <div class='obj-wrapper'>
                    <span><img src='/assets/ddragon/img/right_icons.png' class='obj obj-tower' alt=''><?=$team->towers?></span>
                    <span><img src='/assets/ddragon/img/right_icons.png' class='obj obj-inhib' alt=''><?=$team->inhibs?></span>
                    <span><img src='/assets/ddragon/img/right_icons.png' class='obj obj-herald' alt=''><?=$team->heralds?></span>
                    <span><img src='/assets/ddragon/img/right_icons.png' class='obj obj-dragon' alt=''><?=$team->dragons?></span>
                    <span><img src='/assets/ddragon/img/right_icons.png' class='obj obj-baron' alt=''><?=$team->barons?></span>
                </div>
				<?php if ($index == 0): ?><div class='game-row-divider'></div><?php endif; ?>
			<?php endforeach; ?>
        </div>

        <div class="game-row summoner-details">
		<?php foreach ([$gameData->blueTeamPlayers, $gameData->redTeamPlayers] as $teamIndex=>$teamPlayers): ?>
            <div class="game-item team-summoners">
				<?php /** @var \App\Domain\Entities\LolGame\GamePlayerData $player */
				foreach ($teamPlayers as $player): ?>
                    <div class="game-item summoner <?= $teamIndex == 0 ? 'blue' : 'red'?>">

                        <div class='runes'>
                            <img loading='lazy' alt='' src='<?=$patch->getRuneUrlById($player->KeystoneRuneId)?>' title='<?=$patch->getRuneNameById($player->KeystoneRuneId)?>' class='keystone'>
                            <img loading='lazy' alt='' src='<?=$patch->getRuneUrlById($player->secondaryRunePageId)?>' title='<?=$patch->getRuneNameById($player->secondaryRunePageId)?>' class='sec-rune'>
                        </div>

                        <div class='summoner-spells'>
                            <img loading='lazy' alt='' src='<?=$patch->getSummonerSpellUrlById($player->summoner1Id)?>' class='summ-spell'>
                            <img loading='lazy' alt='' src='<?=$patch->getSummonerSpellUrlById($player->summoner2Id)?>' class='summ-spell'>
                        </div>

                        <div class='champion'>
                            <img loading="lazy" alt="" title="<?=$player->championName?>" src="<?=$patch->getChampionUrlById($player->championId)?>" class="champ">
                            <div class='champ-lvl'><?= $player->championLevel ?></div>
                        </div>

						<?php $nameAndTag = $this->findPlayersGameNameAndTag($player); ?>
                        <div class="summoner-name">
                            <div class="tooltip">
                                <span class="player-name"><?= $nameAndTag[0] ?></span>
								<?php if ($nameAndTag[1]): ?>
                                    <span class="tooltiptext interactable riot-id"><?= $nameAndTag[0] ?>#<?= $nameAndTag[1] ?></span>
								<?php endif; ?>
                            </div>

							<?php

							foreach ($this->findPlayersSeasonRanks($player) as $playerRank) {
								if ($playerRank != null) {
									$rank_classes = implode(" ", ["summ-rank", "split_rank_element", "ranked-split-{$playerRank->rankedSplit->season}-{$playerRank->rankedSplit->split}"]);
									$css_style = $currentSplit->equals($playerRank->rankedSplit) ? "" : "display: none";?>
                                    <div class="<?=$rank_classes?>" style="<?=$css_style?>">
                                        <img class='rank-emblem-mini' src='/assets/ddragon/img/ranks/mini-crests/<?=$playerRank->rank->getRankTierLowercase()?>.svg' alt='<?=$playerRank->rank->getRankTier()?>'>
										<?=$playerRank->rank->getRank(false)?>
                                    </div>
									<?php
								}
							}
							?>

                        </div>

                        <div class='player-stats'>
                            <div class='player-stats-wrapper'>
                                <span class='kills'><img loading='lazy' src='/assets/ddragon/img/kills.png' class='stats kills' alt=''><?=$player->kills?> / <?=$player->deaths?> / <?=$player->assists?></span>
                                <span class='CS'><img loading='lazy' src='/assets/ddragon/img/icon_minions.png' class='stats cs' alt=''><?=$player->totalMinionsKilled?></span>
                                <span class='gold'><img loading='lazy' src='/assets/ddragon/img/icon_gold.png' class='stats gold' alt=''><?=$player->getGoldEarnedFormatted()?> Gold</span>
                            </div>
                        </div>

                        <div class='items'>
                            <div class='items-wrapper'>
                                <?php foreach ($player->itemIds as $itemId): ?>
                                    <img loading='lazy' src='<?=$patch->getItemUrlById($itemId)?>' title="<?=$patch->getItemNameById($itemId)?>" alt=''>
                                <?php endforeach; ?>
                            </div>
                        </div>

                    </div>
				<?php endforeach; ?>
            </div>
			<?php if ($teamIndex == 0): ?><div class='game-row-divider'></div><?php endif; ?>
		<?php endforeach; ?>
        </div>

        <div class='game-row bans'>
			<?php /** @var \App\Domain\Entities\LolGame\GameTeamData $team */
			foreach ([$gameData->blueTeam, $gameData->redTeam] as $index=>$team): ?>
                <div class='bans-wrapper'>
                    <?php foreach ($team->bans as $champion): ?>
                        <span>
                            <img loading='lazy' src='<?=$patch->getChampionUrlById($champion)?>' alt=''>
                            <i class='gg-block'></i>
                        </span>
					<?php endforeach; ?>
                </div>
				<?php if ($index == 0): ?><div class='game-row-divider'></div><?php endif; ?>
			<?php endforeach; ?>
        </div>

	</div>
</div>