<?php

namespace App\Service;

class RiotApiService extends ApiService {
	private function fetchFromEndpoint(string $endpoint, string $region = 'europe'): ApiResponse {
        if (empty($_ENV['RIOT_API_KEY'])) {
            return ApiResponse::error('Missing API credentials');
        }

        $apiUrl = "https://$region.api.riotgames.com/$endpoint";
        $options = ["http" => [
            "header" => ["X-Riot-Token: {$_ENV['RIOT_API_KEY']}"]
        ]];
        $context = stream_context_create($options);
        $response = @file_get_contents($apiUrl, context: $context);

        if (!isset($http_response_header)) {
            return ApiResponse::error('No response from RIOT API');
        }

        $statusCode = $this->parseStatusCode($http_response_header);
        $headers = $this->parseHeaders($http_response_header);

        if ($response === false) {
            return ApiResponse::error("Error from RIOT API: Code $statusCode", $statusCode, $headers);
        }

        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return ApiResponse::error('Invalid JSON received from RIOT API', $statusCode, $headers);
        }
        if ($statusCode !== null && $statusCode >= 200 && $statusCode < 300) {
            return ApiResponse::success($data, $statusCode, $headers);
        }

        $message = $data['status']['message'] ?? 'Unknown error';
        return ApiResponse::error($message, $statusCode, $headers);
    }

    public function getRiotAccountByRiotId(string $name, string $tag): ApiResponse {
        $name = rawurlencode($name);
        $tag = rawurlencode($tag);
        $endpoint = "riot/account/v1/accounts/by-riot-id/$name/$tag";
        return $this->fetchFromEndpoint($endpoint);
    }

    public function getRiotAccountByPuuid(string $puuid): ApiResponse {
        $endpoint = "riot/account/v1/accounts/by-puuid/$puuid";
        return $this->fetchFromEndpoint($endpoint);
    }

    public function getRankByPuuid(string $puuid): ApiResponse {
        $endpoint = "lol/league/v4/entries/by-puuid/$puuid";
        return $this->fetchFromEndpoint($endpoint, 'euw1');
    }

    public function getMatchByMatchId(string $matchId): ApiResponse {
        $endpoint = "lol/match/v5/matches/$matchId";
        return $this->fetchFromEndpoint($endpoint);
    }

	public function getMatchIdsByPuuidAndDatetimeForTourneyGames(string $puuid, \DateTimeImmutable $dateTime): ApiResponse {
		$startTime = $dateTime->modify('-5 days')->getTimestamp();
		$endTime = $dateTime->modify('+5 days')->getTimestamp();
		
		$endpoint = "lol/match/v5/matches/by-puuid/$puuid/ids?startTime=$startTime&endTime=$endTime&type=tourney";
		return $this->fetchFromEndpoint($endpoint);
	}
	public function getMatchIdsByPuuidAndDatetimeForCustomGames(string $puuid, \DateTimeImmutable $dateTime): ApiResponse {
		$startTime = $dateTime->modify('-5 days')->getTimestamp();
		$endTime = $dateTime->modify('+5 days')->getTimestamp();

		$endpoint = "lol/match/v5/matches/by-puuid/$puuid/ids?startTime=$startTime&endTime=$endTime&queue=0";
		return $this->fetchFromEndpoint($endpoint);
	}
}