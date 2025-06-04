<?php

namespace App\API\Admin\ImportOpl;

use App\Core\Utilities\DataParsingHelpers;
use App\Domain\Repositories\TournamentRepository;

class TournamentsHandler {
	use DataParsingHelpers;
	public function getData(array $dataGet): void {
		$tournamentId = $this->intOrNull($dataGet['tournamentId'] ?? null);

		if (!$tournamentId) {
			http_response_code(400);
			echo json_encode(['error' => 'Missing tournament ID']);
			exit;
		}

		$apiUrl = "https://www.opleague.pro/api/v4/tournament/$tournamentId";
		$options = ["http" => [
			"header" => [
				"Authorization: Bearer {$_ENV['OPL_BEARER_TOKEN']}",
				"User-Agent: {$_ENV['USER_AGENT']}",
			]
		]];
		$context = stream_context_create($options);
		$response = @file_get_contents($apiUrl, context: $context);

		if (!isset($http_response_header)) {
			http_response_code(500);
			echo json_encode(['error' => 'No response from OPL API']);
			exit;
		}

		$httpStatus = $http_response_header[0] ?? '';
		if ($response === false || !str_contains($httpStatus, '200')) {
			http_response_code(500);
			echo json_encode(['error' => "Failed to get data from OPL API: $httpStatus"]);
			exit;
		}

		$data = json_decode($response, true);
		if (json_last_error() !== JSON_ERROR_NONE) {
			http_response_code(500);
			echo json_encode(['error' => 'Invalid JSON received from OPL API']);
			exit;
		}
		if (isset($data['error'])) {
			http_response_code(500);
			echo json_encode(['error' => 'API error: ' . $data['error']]);
			exit;
		}

		$tournamentRepo = new TournamentRepository();

		$tournament = $tournamentRepo->createFromOplData($data['data']);
		sort($data['data']['leafes']);
		sort($data['data']['ancestors']);

		$relatedEvents  = ["children"=>$data['data']['leafes'], "parents"=>$data['data']['ancestors']];

		echo json_encode(["entityData" => $tournamentRepo->mapEntityToData($tournament), "relatedTournaments" => $relatedEvents]);
	}

	public function saveFromForm(array $dataGet): void {
		if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
			http_response_code(400);
			echo json_encode(['error' => 'Invalid request method']);
			exit;
		}
		$tournamentData = json_decode(file_get_contents('php://input'), true);

		if (json_last_error() !== JSON_ERROR_NONE || !$tournamentData) {
			http_response_code(400);
			echo json_encode(['error' => 'Missing Data or invalid JSON received']);
			exit;
		}

		$tournamentRepo = new TournamentRepository();
		$tournament = $tournamentRepo->mapToEntity($tournamentData, newEntity: true);

		$saveResult = $tournamentRepo->save($tournament);
		$saveResult["result"] = $saveResult["result"]->name;
		echo json_encode($saveResult);
	}
}