<?php
/** @var mysqli $dbcn  */

use App\Domain\Repositories\TournamentRepository;
use App\Domain\Services\EntitySorter;
use App\UI\Components\Helpers\IconRenderer;
use App\UI\Components\Navigation\Header;
use App\UI\Enums\HeaderType;
use App\UI\Page\AssetManager;
use App\UI\Page\PageMeta;

$pageMeta = new PageMeta('Riot-API-Daten', bodyClass: 'admin');
AssetManager::addJsAsset('admin/rgapiImport.js');

echo new Header(HeaderType::ADMIN_RGAPI);

$tournamentRepo = new TournamentRepository();
$tournaments = $tournamentRepo->findAllRootTournaments();
$tournaments = EntitySorter::sortTournamentsByStartDate($tournaments);
?>

<main>
    <div class="general-administration">
        <h2>Allgemeine Verwaltung</h2>
        <button class="get_all_player_puuids">
            <span>PUUIDs für alle Spieler ohne holen<?=IconRenderer::getMaterialIconSpan('sync')?></span>
        </button>
        <button class="update_all_player_ranks">
            <span>Ränge für alle Spieler aktualisieren<?=IconRenderer::getMaterialIconSpan('sync')?></span>
        </button>
        <button class="update_all_team_ranks">
            <span>Ränge für alle Teams aktualisieren<?=IconRenderer::getMaterialIconSpan('sync')?></span>
        </button>
    </div>
    <div class="divider" style="margin-top: 28px"></div>
    <h2>Turniere:</h2>
    <div class='slct'>
        <select class="tournament-selector">
            <?php
            foreach ($tournaments as $tournament) {
                echo "<option value='".$tournament->id."'>{$tournament->getFullName()}</option>";
            }
            ?>
        </select>
        <?= IconRenderer::getMaterialIconSpan('arrow_drop_down')?>
    </div>

    <?php
	foreach ($tournaments as $index=>$tournament) {
		if ($index == 0) {
			$hiddenclass = "";
		} else {
			$hiddenclass = " hidden";
		}
		echo "<div class='writing-wrapper $hiddenclass' data-id='$tournament->id'>";
		echo "<h4>{$tournament->getFullName()}</h4>";
        if ($tournament->archived) {
            echo "<span style='color: #ff6161; text-align: center'>Dieses Turnier ist archiviert!</span>";
        }
		echo "<button class='write puuids' data-id='{$tournament->id}'><span>get PUUIDs for Players without ID</span></button>";
		echo "<button class='write puuids-all' data-id='{$tournament->id}'><span>get PUUIDs for all Players</span></button>";
		echo "<button class='write riotids-puuids' data-id='{$tournament->id}'><span>get RiotIDs for all Players</span></button>";
		echo "<button class='write get-ranks' data-id='{$tournament->id}'><span>get Ranks for Players</span></button>";
		echo "<button class='write calc-team-rank' data-id='{$tournament->id}'><span>calculate average Ranks for Teams</span></button>";
		echo "<button class='write gamedata' data-id='{$tournament->id}'><span>get Gamedata for Games without Data</span></button>";
		echo "<button class='write playerstats' data-id='{$tournament->id}'><span>calculate Playerstats</span></button>";
		echo "<button class='write teamstats' data-id='{$tournament->id}'><span>calculate Teamstats</span></button>";
		echo "<div class='result-wrapper no-res' data-id='{$tournament->id}'>
                        <div class='clear-button' data-id='{$tournament->id}'>Clear</div>
                        <div class='result-content'></div>
                      </div>";
		echo "</div>";
	}
    ?>
</main>