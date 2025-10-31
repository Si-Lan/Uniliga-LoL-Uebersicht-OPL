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
		echo "<div class='writing-wrapper ".$tournament->id.$hiddenclass."'>";
		echo "<h2>{$tournament->getFullName()}</h2>";
        if ($tournament->archived) {
            echo "<span style='color: #ff6161; text-align: center'>Dieses Turnier ist archiviert!</span>";
        }
		echo "<button class='write puuids {$tournament->id}' onclick='get_puuids(\"{$tournament->id}\")'>get PUUIDs for Players without ID</button>";
		echo "<button class='write puuids-all {$tournament->id}' onclick='get_puuids(\"{$tournament->id}\",false)'>get PUUIDs for all Players</button>";
		echo "<button class='write riotids-puuids {$tournament->id}' onclick='get_riotids_by_puuids(\"{$tournament->id}\")'>get RiotIDs for all Players</button>";
		echo "<button class='write get-ranks {$tournament->id}' onclick='get_ranks(\"{$tournament->id}\")'>get Ranks for Players</button>";
		echo "<button class='write calc-team-rank {$tournament->id}' onclick='get_average_team_ranks(\"{$tournament->id}\")'>calculate average Ranks for Teams</button>";
		echo "<button class='write gamedata {$tournament->id}' onclick='get_game_data(\"{$tournament->id}\")'>get Gamedata for Games without Data</button>";
		echo "<button class='write get-pstats {$tournament->id}' onclick='get_stats_for_players(\"{$tournament->id}\")'>calculate Playerstats</button>";
		echo "<button class='write teamstats {$tournament->id}' onclick='get_teamstats(\"{$tournament->id}\")'>calculate Teamstats</button>";
		echo "<div class='result-wrapper no-res {$tournament->id}'>
                        <div class='clear-button' onclick='clear_results(\"{$tournament->id}\")'>Clear</div>
                        <div class='result-content'></div>
                      </div>";
		echo "</div>";
	}
    ?>
</main>