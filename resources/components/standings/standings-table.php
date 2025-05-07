<div class="standings">
	<div class="title">
		<h3>
			Standings
			<?php
            if ($this->selectedTeam !== null) {
                $groupLinkUrl = "/turnier/".$this->tournamentStage->rootTournament->id."/".$this->tournamentStage->getUrlKey()."/".$this->tournamentStage->id;
                echo new \App\Components\UI\PageLink($groupLinkUrl, $this->tournamentStage->getFullName());
            }
            ?>
		</h3>
	</div>

    <?php $classes = implode(" ", ["standings-table", "content", ($this->tournamentStage->mostCommonBestOf == 3 || $this->tournamentStage->mostCommonBestOf == 5) ? "with-single-games" : ""]); ?>
	<div class="<?=$classes?>">
        <div class="standing-row standing-header">
            <div class="standing-pre-header rank">#</div>
            <div class="standing-item-wrapper-header">
                <div class="standing-item team">Team</div>
                <div class="standing-item played" title="gespielte Spiele">Pl</div>
                <?php
                switch ($this->tournamentStage->mostCommonBestOf) {
                    case 1:
                        ?>
                <div class="standing-item score" title="Wins/Losses in Serien">W-L</div>
                <?php
                        break;
                    case 3:
                    case 5:
                        ?>
                <div class="standing-item score" title="Wins/Losses in Serien">W-L</div>
                <div class="standing-item score-games" title="Wins/Losses in Spielen">Scr</div>
                <?php
                        break;
                    default:
                        ?>
                <div class="standing-item score" title="Wins/Draws/Losses">W-D-L</div>
                <?php
                }
                ?>
                <div class="standing-item points" title="Punkte">Pt</div>
            </div>
        </div>
        <?php
        $prevStanding = 0;
        foreach ($this->teamsInTournamentStage as $teamInTournamentStage) {
            $teamSelected = $teamInTournamentStage->team->id === $this->selectedTeam->id;
            echo new \App\Components\Standings\StandingsRow($teamInTournamentStage,$prevStanding,$teamSelected);
            $prevStanding = $teamInTournamentStage->standing;
        }
        ?>
	</div>
</div>