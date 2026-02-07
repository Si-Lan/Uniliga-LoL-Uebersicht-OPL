<?php
/** @var GameInMatch $gameInMatch */
/** @var Patch $patch */
/** @var bool $selectable */

use App\Domain\Entities\GameInMatch;
use App\Domain\Entities\Patch;

?>

<div class="game-suggestion-details" data-game-id="<?=$gameInMatch->game->id?>">
    <div class="team1" style="display: flex; flex-direction: column;">
        <span class="team-name<?=$gameInMatch->blueTeam ? " existing-team" : " unidentified-team"?>"><?= $gameInMatch->blueTeam?->nameInTournament ?? "unidentifiziert"?></span>
        <span style="text-align: center">
            <?= $gameInMatch->game->gameData->blueTeamWin ? "Win" : "Loss"?>
        </span>
        <?php foreach ($gameInMatch->game->gameData->blueTeamPlayers as $player): ?>
            <span>
                <img style="height: 20px; width: 20px" loading="lazy" alt="" title="<?=$player->championName?>" src="/assets/ddragon/<?=$patch->patchNumber?>/img/champion/<?=$player->championName?>.webp" class="champ">
                <span class="player-name"><?=$player->riotIdName?>#<?=$player->riotIdTag?></span>
                <span class="player-kda"><?=$player->kills?>/<?=$player->deaths?>/<?=$player->deaths?></span>
            </span>
        <?php endforeach; ?>
    </div>
    <div class="team2" style="display: flex; flex-direction: column;">
        <span class="team-name<?=$gameInMatch->redTeam ? " existing-team" : " unidentified-team"?>">
            <?= $gameInMatch->redTeam?->nameInTournament ?? "unidentifiziert"?>
        </span>
        <span style="text-align: center"><?= $gameInMatch->game->gameData->redTeamWin ? "Win" : "Loss"?></span>
        <?php foreach ($gameInMatch->game->gameData->redTeamPlayers as $player): ?>
            <span>
                <img style="height: 20px; width: 20px" loading="lazy" alt="" title="<?=$player->championName?>" src="/assets/ddragon/<?=$patch->patchNumber?>/img/champion/<?=$player->championName?>.webp" class="champ">
                <span class="player-name"><?=$player->riotIdName?>#<?=$player->riotIdTag?></span>
                <span class="player-kda"><?=$player->kills?>/<?=$player->deaths?>/<?=$player->deaths?></span>
            </span>
        <?php endforeach; ?>
    </div>
    <?php if ($selectable): ?>
    <label>
        <input type="checkbox">
    </label>
    <?php endif; ?>
</div>