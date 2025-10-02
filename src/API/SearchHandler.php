<?php

namespace App\API;

use App\Core\Utilities\DataParsingHelpers;
use App\Domain\Entities\Player;
use App\Domain\Entities\Team;
use App\Domain\Repositories\PlayerRepository;
use App\Domain\Repositories\TeamRepository;
use App\Domain\Services\EntitySorter;

class SearchHandler {
	use DataParsingHelpers;
	public function getSearchGlobalAll(): void {
		$searchString = $this->stringOrNull($_GET['search'] ?? null);

		if (is_null($searchString)) {
			http_response_code(400);
			echo json_encode(['error'=>'missing Search String']);
			return;
		}

		$playerRepo = new PlayerRepository();
		$players = $playerRepo->findAllByNameContainsLetters($searchString);

		$teamRepo = new TeamRepository();
		$teams = $teamRepo->findAllByNameContainsLetters($searchString);

		$resultEntities = array_merge($players,$teams);
		$resultEntities = EntitySorter::sortByNameMatchingString($resultEntities,$searchString);

		$searchResults = [];
		foreach ($resultEntities as $entity) {
			if ($entity instanceof Player) {
				$searchResults[] = ["type"=>"player","id"=>$entity->id,"name"=>$entity->name,"riotIdName"=>$entity->riotIdName,"riotIdTag"=>$entity->riotIdTag];
			}
			if ($entity instanceof Team) {
				$searchResults[] = ["type"=>"team","id"=>$entity->id,"name"=>$entity->name,"shortName"=>$entity->shortName];
			}
		}

		echo json_encode($searchResults);
	}
}