<?php

namespace App\Service;

use App\Domain\Repositories\TeamRepository;
use App\Domain\Repositories\TournamentRepository;

class OplLogoService {
	private string $imgFolderPath = BASE_PATH."/public/assets/img";
	private string $teamLogosFolderPath;
	private string $tournamentLogosFolderPath;

	public function __construct() {
		$this->teamLogosFolderPath = $this->imgFolderPath."/team_logos";
		$this->tournamentLogosFolderPath = $this->imgFolderPath."/tournament_logos";
		if (!file_exists($this->teamLogosFolderPath)) mkdir($this->teamLogosFolderPath, recursive: true);
		if (!file_exists($this->tournamentLogosFolderPath)) mkdir($this->tournamentLogosFolderPath, recursive: true);
	}

	public function downloadTeamLogo(int $teamId): array {
		$result = [
			"LogoReceived" => false,
			"LogoUpdated" => false,
		];

		$oplLogoUrlLightmode = "/styles/media/team/$teamId/Logo_100.webp";
		$oplLogoUrlDarkmode = "/styles/media/team/$teamId/Logo_on_black_100.webp";

		$teamRepo = new TeamRepository();
		$team = $teamRepo->findById($teamId);
		if ($team === null || $team->logoId === null) {
			return $result;
		}

		$localTeamLogoDir = $this->teamLogosFolderPath."/".$team->logoId;

		$localLogoExists = is_dir($localTeamLogoDir) && file_exists($localTeamLogoDir."/logo_square.webp") && file_exists($localTeamLogoDir."/logo_light_square.webp");

		if (!is_dir($localTeamLogoDir)) mkdir($localTeamLogoDir);

		$logoDark = $this->downloadLogo($oplLogoUrlDarkmode);
		$logoDarkSquared = $this->squareLogo($logoDark);
		$logoLight = $this->downloadLogo($oplLogoUrlLightmode);
		$logoLightSquared = $this->squareLogo($logoLight);

		if ($logoDark === null && $logoLight === null) {
			return $result;
		}

		$result["LogoReceived"] = $logoDark !== null || $logoLight !== null;

		if ($localLogoExists) {
			$isNewDarkmodeLogo = $this->isNewLogo($logoDarkSquared, $localTeamLogoDir."/logo_square.webp");
			$isNewLightmodeLogo = $this->isNewLogo($logoLightSquared, $localTeamLogoDir."/logo_light_square.webp");
			$isNewLogo = $isNewDarkmodeLogo["result"] || $isNewLightmodeLogo["result"];
			if ($isNewLogo) {
				$newDirKey = $teamRepo->createNewLogoDirForTeam($team->id);
				$this->copyOldTeamLogos($team->id, $newDirKey);
				$this->saveLogos($logoDark, $logoLight, $localTeamLogoDir);
				$teamRepo->setNewLogoForTeam($team->id, max($isNewDarkmodeLogo["rating"], $isNewLightmodeLogo["rating"]));
			} else {
				$teamRepo->setLogoDownloadTimeForTeam($team->id);
			}
		} else {
			$this->saveLogos($logoDark, $logoLight, $localTeamLogoDir);
			$teamRepo->setNewLogoForTeam($team->id, null);
			$isNewLogo = true;
		}

		$result["LogoUpdated"] = $isNewLogo;

		return $result;
	}
	public function downloadTournamentLogo(int $tournamentId): array {
		$result = [
			"LogoReceived" => false,
			"LogoUpdated" => false,
		];

		$oplLogoUrlLightmode = "/styles/media/event/$tournamentId/Logo_100.webp";
		$oplLogoUrlDarkmode = "/styles/media/event/$tournamentId/Logo_on_black_100.webp";

		$tournamentRepo = new TournamentRepository();
		$tournament = $tournamentRepo->findById($tournamentId);
		if ($tournament === null || $tournament->logoId === null) {
			return $result;
		}

		$localTournamentLogoDir = $this->tournamentLogosFolderPath."/".$tournament->logoId;

		$localLogoExists = is_dir($localTournamentLogoDir) && file_exists($localTournamentLogoDir."/logo_square.webp") && file_exists($localTournamentLogoDir."/logo_light_square.webp");

		if (!is_dir($localTournamentLogoDir)) mkdir($localTournamentLogoDir);

		$logoDark = $this->downloadLogo($oplLogoUrlDarkmode);
		$logoDarkSquared = $this->squareLogo($logoDark);
		$logoLight = $this->downloadLogo($oplLogoUrlLightmode);
		$logoLightSquared = $this->squareLogo($logoLight);

		$result["LogoReceived"] = $logoDark !== null || $logoLight !== null;

		if ($localLogoExists) {
			$isNewDarkmodeLogo = $this->isNewLogo($logoDarkSquared, $localTournamentLogoDir."/logo_square.webp");
			$isNewLightmodeLogo = $this->isNewLogo($logoLightSquared, $localTournamentLogoDir."/logo_light_square.webp");
			$isNewLogo = $isNewDarkmodeLogo["result"] || $isNewLightmodeLogo["result"];
			if ($isNewLogo) {
				$this->saveLogos($logoDark, $logoLight, $localTournamentLogoDir);
			}
		} else {
			$this->saveLogos($logoDark, $logoLight, $localTournamentLogoDir);
			$isNewLogo = true;
		}

		$result["LogoUpdated"] = $isNewLogo;

		return $result;
	}

	private function downloadLogo(string $oplLogoUrl):\GdImage|null {
		$options = ['http' => ['header' => ["User-Agent: {$_ENV['USER_AGENT']}"]]];
		$context = stream_context_create($options);
		$logoData = @file_get_contents("https://www.opleague.pro$oplLogoUrl", context: $context);
		$logoExists = str_contains($http_response_header[0] ?? '', '200');
		return $logoExists ? imagecreatefromstring($logoData) : null;
	}
	private function saveLogos(\GdImage|null $logoDark, \GdImage|null $logoLight, string $targetDir): void {
		if ($logoDark !== null) {
			$logoDarkSquare = $this->squareLogo($logoDark);

			$this->saveWebp($logoDark, $targetDir . "/logo.webp");
			$this->saveWebp($logoDarkSquare, $targetDir . "/logo_square.webp");
			if ($logoLight === null) {
				$this->saveWebp($logoDark, $targetDir . "/logo_light.webp");
				$this->saveWebp($logoDarkSquare, $targetDir . "/logo_light_square.webp");
			}
		}
		if ($logoLight !== null) {
			$logoLightSquare = $this->squareLogo($logoLight);

			$this->saveWebp($logoLight, $targetDir . "/logo_light.webp");
			$this->saveWebp($logoLightSquare, $targetDir . "/logo_light_square.webp");
			if ($logoDark === null) {
				$this->saveWebp($logoLight, $targetDir . "/logo.webp");
				$this->saveWebp($logoLightSquare, $targetDir . "/logo_square.webp");
			}
		}
	}

	private function saveWebp(\GdImage $image, string $targetFile, int $webpQuality = 100): void {
		imagepalettetotruecolor($image);
		imagealphablending($image, false);
		imagesavealpha($image, true);
		imagewebp($image, $targetFile, $webpQuality);
	}
	private function squareLogo(\GdImage|null $image): \GdImage|null {
		if ($image === null) return null;
		$imgSize = max(imagesx($image), imagesy($image));
		$imgSquare = imagecreate($imgSize, $imgSize);
		imagepalettetotruecolor($imgSquare);
		imagealphablending($imgSquare, false);
		$transparency = imagecolorallocatealpha($imgSquare, 0, 0, 0, 127);
		imagefill($imgSquare, 0, 0, $transparency);
		imagesavealpha($imgSquare, true);
		imagecopy($imgSquare, $image,intval(($imgSize-imagesx($image))/2),intval(($imgSize-imagesy($image))/2), 0, 0, imagesx($image), imagesy($image));
		return $imgSquare;
	}

	/**
	 * @param string|\GdImage $imageOrPathA
	 * @param string|\GdImage $imageOrPathB
	 * @return array{result: bool, rating: float}
	 */
	private function isNewLogo(string|\GdImage|null $imageOrPathA, string|\GdImage $imageOrPathB): array {
		if ($imageOrPathA === null) return ["result" => false, "rating" => 0];
		$comparision = @$this->compareImages($imageOrPathA, $imageOrPathB, 20);
		if ($comparision < 35) {
			return ["result" => true, "rating" => $comparision];
		} else {
			return ["result" => false, "rating" => $comparision];
		}
	}

	private function compareImages(string|\GdImage $imageOrPathA, string|\GdImage $imageOrPathB, int $accuracy): float|int {
		$imgA = (gettype($imageOrPathA) === 'string') ? imagecreatefrompng($imageOrPathA) : $imageOrPathA;
		$imgB = (gettype($imageOrPathB) === 'string') ? imagecreatefromwebp($imageOrPathB) : $imageOrPathB;

		$width = imagesx($imgA);
		$height = imagesy($imgA);
		$pointsX = $accuracy*5;
		$pointsY = $accuracy*5;
		$sizeX = round($width/$pointsX);
		$sizeY = round($height/$pointsY);

		$transparencyA = imagecolorallocatealpha($imgA, 0,0,0,127);
		$transparencyB = imagecolorallocatealpha($imgB, 0,0,0,127);

		//loop through each point and compare the color of that point
		$y = 0;
		$matchcount = 0;
		$num = 0;
		for ($i = 0; $i <= $pointsY; $i++) {
			$x = 0;
			for ($n = 0; $n <= $pointsX; $n++) {
				$rgba = imagecolorat($imgA, $x, $y);
				$colorsa = imagecolorsforindex($imgA, $rgba);

				$rgbb = imagecolorat($imgB, $x, $y);
				$colorsb = imagecolorsforindex($imgB, $rgbb);

				if ($rgba == $transparencyA && $rgbb == $transparencyB) {
					$x += $sizeX;
					continue;
				}

				if($this->compareColors($colorsa['red'], $colorsb['red']) && $this->compareColors($colorsa['green'], $colorsb['green']) && $this->compareColors($colorsa['blue'], $colorsb['blue'])){
					//point matches
					$matchcount ++;
				}
				$x += $sizeX;
				$num++;
			}
			$y += $sizeY;
		}
		//take a rating of the similarity between the points, if over 90 percent they match.
		$rating = $matchcount*(100/$num);
		return $rating;
	}

	private function compareColors(int $color1, int $color2): bool {
		if ($color1 >= $color2-10 && $color1 <= $color2+10) return true;
		return false;
	}

	private function copyOldTeamLogos(int $teamId, int $dirKey): bool {
		$oldTeamLogoDir = $this->teamLogosFolderPath."/".$teamId;
		$newTeamLogoDir = $this->teamLogosFolderPath."/".$teamId."/".$dirKey;;
		if (!is_dir($oldTeamLogoDir)) return false;
		if (!is_dir($newTeamLogoDir)) mkdir($newTeamLogoDir);
		$dirIterator = new \DirectoryIterator($oldTeamLogoDir);
		$success = true;
		foreach ($dirIterator as $file) {
			if ($file->isDot() || $file->isDir() || $file->getExtension() != "webp") continue;
			$imgName = $file->getFilename();
			$copySuccess = copy($oldTeamLogoDir."/".$imgName, $newTeamLogoDir."/".$imgName);
			if (!$copySuccess) $success = false;
		}
		return $success;
	}
}