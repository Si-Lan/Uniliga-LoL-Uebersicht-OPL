<?php

namespace App\UI\Components\EliminationBrackets;

use App\Domain\Entities\Matchup;
use App\Domain\Entities\Team;
use App\Domain\Entities\Tournament;
use App\Domain\Enums\EventFormat;
use App\Domain\Repositories\MatchupRepository;
use App\UI\Page\AssetManager;

class EliminationBracket {

	/** @var array<Matchup> $matches */
	private array $matchups = [];
	/** @var array<array<Matchup>> $matchesByColumn */
	private array $matchesByColumn = [];
	private int $hiddenColumnsStart;
	private int $hiddenColumnsEnd;

	public function __construct(
		private Tournament $tournamentStage,
		private ?Team $selectedTeam = null
	) {
		AssetManager::addCssAsset("components/brackets.css");
		AssetManager::addJsAsset("components/brackets.js");
		$matchupRepo = new MatchupRepository();
		$this->matchups = $matchupRepo->findAllByTournamentStage($this->tournamentStage);
		if ($this->tournamentStage->format === EventFormat::SINGLE_ELIMINATION) {
			$this->prepareMatchesSingleElimination($this->matchups);
		} elseif ($this->tournamentStage->format === EventFormat::DOUBLE_ELIMINATION) {
			$this->prepareMatchesDoubleElimination($this->matchups);
		}
	}


	private function prepareMatchesSingleElimination(array $matches): void {
		if (count($matches) === 0) return;
		$numMatches = $this->next_pow(count($matches));
		if ($numMatches !== count($matches)) {
			// array füllen
			$invalidMatchIdCounter = -1;
			for ($i = count($matches); $i < $numMatches; $i++) {
				$matches[] = Matchup::createEmptyWithId($invalidMatchIdCounter, $matches[0]->tournamentStage);
				$invalidMatchIdCounter--;
			}
		}
		$numColumns = (int) log($numMatches, 2);
		$matches = array_values($matches);
		$matchesNoThird = array_slice($matches, 0, $numMatches - 1);
		$matchesReversed = array_reverse($matchesNoThird);

		// Spalten für Spiele zuweisen
		$runningIndex = 0;
		for ($i = 0; $i < $numColumns; $i++) {
			// 1. Spalte hat Hälfte aller Spiele, 2. Spalte Viertel aller Spiele, usw.
			$numMatchesInColumn = $numMatches / (2**($i+1));
			for ($j = 0; $j < $numMatchesInColumn; $j++) {
				$matches[$runningIndex]->bracketColumn = $i;
				$runningIndex++;
				if ($runningIndex >= count($matches) - 1) break;
			}
		}
		$thirdPlaceMatch = $matches[$runningIndex];
		$thirdPlaceMatch->bracketColumn = $numColumns-1;

		// Verbindungen festlegen
		foreach ($matchesReversed as $i => $match) {
			if ($match->bracketColumn > 0) {
				$match->bracketPrevMatchups[] = $matchesReversed[2*$i+1];
				$match->bracketPrevMatchups[] = $matchesReversed[2*$i+2];
			}
			if ($i !== 0) {
				$match->bracketNextMatchups[] = $matchesReversed[floor(($i-1)/2)];
			}
		}

		// Third Place Spiel festlegen
		foreach ($matchesReversed[0]->bracketPrevMatchups as $match) {
			$match->bracketNextMatchups[] = $thirdPlaceMatch;
		}
		$thirdPlaceMatch->bracketPrevMatchups = $matchesReversed[0]->bracketPrevMatchups;
		$thirdPlaceMatch->bracketNextMatchups = $matchesReversed[0]->bracketNextMatchups;

		// MatchesByColumn für Ausgabe erstellen
		foreach ($matches as $match) {
			$this->matchesByColumn[$match->bracketColumn][] = $match;
		}
	}

	private function prepareMatchesDoubleElimination(array $matches): void {
		if (count($matches) === 0) return;
		$nearestPow = $this->next_pow(count($matches));
		$numMatches = $nearestPow - 2;
		if (count($matches) < $numMatches) {
			$invalidMatchIdCounter = -1;
			for ($i = count($matches); $i < $numMatches; $i++) {
				$matches[] = Matchup::createEmptyWithId($invalidMatchIdCounter, $matches[0]->tournamentStage);
				$invalidMatchIdCounter--;
			}
		} elseif (count($matches) > $numMatches) {
			$matches = array_slice($matches, 0, $numMatches);
		}
		$matches = array_values($matches);


		$upperMatches = array_slice($matches, 0, $nearestPow/2);
		$upperMatchesWithoutGrandFinal = array_slice($upperMatches, 0, count($upperMatches) - 1);
		$lowerMatches = array_slice($matches, $nearestPow/2);


		// Spalten für UB-Spiele zuweisen
		$matchesByRoundUB = [];
		$numRoundsUB = (int) log(count($upperMatches), 2);
		$runningIndex = 0;
		for ($i = 0; $i < $numRoundsUB; $i++) {
			$numMatchesInRound = count($upperMatches) / (2**($i+1));
			for ($j = 0; $j < $numMatchesInRound; $j++) {
				$matchesByRoundUB[$i][] = $upperMatches[$runningIndex];
				if ($i <= 1) {
					$upperMatchesWithoutGrandFinal[$runningIndex]->bracketColumn = $i;
				} else {
					$upperMatchesWithoutGrandFinal[$runningIndex]->bracketColumn = $i * 2 -1;
				}
				$runningIndex++;
				if ($runningIndex >= count($upperMatchesWithoutGrandFinal) - 1) break;
			}
		}

		// Spalten für LB-Spiele zuweisen
		$matchesByRoundLB = [];
		$numRoundsLB = ($numRoundsUB - 1) * 2;
		$runningIndex = 0;
		for ($i = 0; $i < $numRoundsLB; $i++) {
			$numMatchesInRound = (count($lowerMatches) + 2) / (2**(ceil(($i+1)/2)+1));
			for ($j = 0; $j < $numMatchesInRound; $j++) {
				$matchesByRoundLB[$i][] = $lowerMatches[$runningIndex];
				$lowerMatches[$runningIndex]->bracketColumn = $i + 1;
				$runningIndex++;
				if ($runningIndex >= count($lowerMatches) - 1) break;
			}
		}

		// Verbindungen für UB-Spiele festlegen
		foreach ($matchesByRoundUB as $roundIndex=>$roundMatches) {
			foreach ($roundMatches as $matchIndex=>$match) {
				// set next matchups
				if ($roundIndex !== count($matchesByRoundUB) - 1) {
					// winner
					$match->bracketNextMatchups[] = $matchesByRoundUB[$roundIndex+1][floor($matchIndex/2)];
				}
				// loser
				if ($roundIndex === 0) {
					$match->bracketNextMatchups[] = $matchesByRoundLB[$roundIndex][floor(($matchIndex)/2)];
				} else {
					$numMatchesInFollowinLowerRound = count($matchesByRoundLB[2*$roundIndex - 1]);
					$match->bracketNextMatchups[] = $matchesByRoundLB[2*$roundIndex - 1][(($numMatchesInFollowinLowerRound - 1 - $matchIndex) + (2*($roundIndex-1))) % $numMatchesInFollowinLowerRound];
				}

				// set prev matchups
				if ($roundIndex !== 0) {
					$match->bracketPrevMatchups[] = $matchesByRoundUB[$roundIndex-1][2*$matchIndex];
					$match->bracketPrevMatchups[] = $matchesByRoundUB[$roundIndex-1][2*$matchIndex + 1];
				}
			}
		}

		// Verbindungen für LB-Spiele festlegen
		foreach ($matchesByRoundLB as $roundIndex=>$roundMatches) {
			if ($roundIndex + 1 === count($matchesByRoundLB)) break;
			foreach ($roundMatches as $matchIndex=>$match) {
				if ($roundIndex%2 === 0) {
					$match->bracketNextMatchups[] = $matchesByRoundLB[$roundIndex+1][$matchIndex];
					$matchesByRoundLB[$roundIndex+1][$matchIndex]->bracketPrevMatchups[] = $match;
				} else {
					$match->bracketNextMatchups[] = $matchesByRoundLB[$roundIndex+1][floor($matchIndex/2)];
					$matchesByRoundLB[$roundIndex+1][floor($matchIndex/2)]->bracketPrevMatchups[] = $match;
				}
			}
		}


		// Grand Final festlegen
		$grandFinalMatch = $upperMatches[count($upperMatches) - 1];
		$grandFinalMatch->bracketColumn = $numRoundsUB * 2 - 1;
		$grandFinalMatch->bracketPrevMatchups[] = $upperMatchesWithoutGrandFinal[count($upperMatchesWithoutGrandFinal) - 1];
		$grandFinalMatch->bracketPrevMatchups[] = $lowerMatches[count($lowerMatches) - 1];
		$upperMatchesWithoutGrandFinal[count($upperMatchesWithoutGrandFinal) - 1]->bracketNextMatchups[] = $grandFinalMatch;
		$lowerMatches[count($lowerMatches) - 1]->bracketNextMatchups[] = $grandFinalMatch;

		// MatchesByColumn für Ausgabe erstellen
		$numColumns = $numRoundsUB * 2;
		/** @var array<array{'upper':array<Matchup>, 'lower': array<Matchup>}> $matchesByColumn */
		$this->matchesByColumn = array_fill(0, $numColumns, ["upper"=>[], "lower"=>[]]);
		foreach ($upperMatches as $match) {
			$this->matchesByColumn[$match->bracketColumn]["upper"][] = $match;
		}
		foreach ($lowerMatches as $match) {
			$this->matchesByColumn[$match->bracketColumn]["lower"][] = $match;
		}

        // Standardmäßig eingeklappte Spalten festlegen
		$this->hiddenColumnsStart = 0;
		foreach ($this->matchesByColumn as $column) {
			$columnVisible = false;
			foreach ([...$column["upper"], ...$column["lower"]] as $match) {
				if (!$match->defWin) {
					$columnVisible = true;
				}
			}
			if (!$columnVisible) {
				$this->hiddenColumnsStart++;
			} else {
				break;
			}
		}
		$this->hiddenColumnsEnd = count($this->matchesByColumn);
		for ($i = $numColumns - 1; $i > $this->hiddenColumnsStart; $i--) {
			$columnVisible = false;
			foreach ([...$this->matchesByColumn[$i]["upper"], ...$this->matchesByColumn[$i]["lower"]] as $match) {
				if ($match->team1 !== null || $match->team2 !== null) {
					$columnVisible = true;
				}
			}
			if (!$columnVisible) {
				$this->hiddenColumnsEnd--;
			} else {
				break;
			}
		}

        // Unnötige Spalten vom Ende entfernen
        $lastRealColumnIndex = count($this->matchesByColumn) - 1;
        $firstQualifiedFound = false;
        foreach ($this->matchesByColumn as $columnIndex => $column) {
            $matchesInColumn = [...$column["upper"], ...$column["lower"]];
            foreach ($matchesInColumn as $match) {
                if ($match->team1 !== null || $match->team2 !== null
                        || ($match->isQualified() && !$firstQualifiedFound)) {
                    $lastRealColumnIndex = $columnIndex;
                    if ($match->isQualified()) $firstQualifiedFound = true;
                }
            }
        }
        if ($firstQualifiedFound) {
            $this->matchesByColumn = array_slice($this->matchesByColumn, 0, $lastRealColumnIndex + 1);
        }
	}

	public function render(): string {
		$tournamentStage = $this->tournamentStage;
		$matchesByColumn = $this->matchesByColumn;
		$selectedTeam = $this->selectedTeam;
		ob_start();
		if ($tournamentStage->format === EventFormat::SINGLE_ELIMINATION) {
			include __DIR__.'/single-elimination-bracket.template.php';
		} elseif ($tournamentStage->format === EventFormat::DOUBLE_ELIMINATION) {
			$hiddenColumnsStart = $this->hiddenColumnsStart??0;
			$hiddenColumnsEnd = $this->hiddenColumnsEnd??count($matchesByColumn);
			include __DIR__.'/double-elimination-bracket.template.php';
		} else {
			?>
			<div class="elimination-bracket"><span>Turnierbaum kann nicht erstellt werden, da Turnier kein Eliminationsformat ist!</span></div>
			<?php
		}
		return ob_get_clean();
	}
	public function __toString(): string {
		return $this->render();
	}


	private function next_pow($number): int {
		if($number < 2) return 1;
		if(($number & ($number - 1)) == 0) return $number;
		for($i = 0 ; $number > 1 ; $i++) {
			$number = $number >> 1;
		}
		return 1<<($i+1);
	}
}