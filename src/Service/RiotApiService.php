<?php

namespace App\Service;

use App\Service\RiotApiResponse;
class RiotApiService {
	private function fetchFromEndpoint(string $endpoint): RiotApiResponse {
        if (empty($_ENV['RIOT_API_KEY'])) {
            return RiotApiResponse::error('Missing API credentials');
        }

        $apiUrl = "https://europe.api.riotgames.com/$endpoint";
        $options = ["http" => [
            "header" => ["X-Riot-Token: {$_ENV['RIOT_API_KEY']}"]
        ]];
        $context = stream_context_create($options);
        $response = @file_get_contents($apiUrl, context: $context);

        if (!isset($http_response_header)) {
            return RiotApiResponse::error('No response from RIOT API');
        }

        $statusLine = $http_response_header[0] ?? '';
        if (preg_match('#HTTP/\S+\s+(\d{3})#', $statusLine, $m)) {
            $statusCode = (int)$m[1];
        } else {
            $statusCode = null;
        }

        $headers = [];
        foreach ($http_response_header as $hdr) {
            if (str_contains($hdr, ':')) {
                [$k, $v] = explode(':', $hdr, 2);
                $headers[trim($k)] = trim($v);
            }
        }

        if ($response === false) {
            return RiotApiResponse::error("Error from RIOT API: $statusLine", $statusCode, $headers);
        }

        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return RiotApiResponse::error('Invalid JSON received from RIOT API', $statusCode, $headers);
        }
        if ($statusCode !== null && $statusCode >= 200 && $statusCode < 300) {
            return RiotApiResponse::success($data, $statusCode, $headers);
        }

        $message = $data['status']['message'] ?? 'Unknown error';
        return RiotApiResponse::error($message, $statusCode, $headers);
    }

    public function getRiotAccountByRiotId(string $name, string $tag): RiotApiResponse {
        $name = rawurlencode($name);
        $tag = rawurlencode($tag);
        $endpoint = "riot/account/v1/accounts/by-riot-id/$name/$tag";
        return $this->fetchFromEndpoint($endpoint);
    }

    public function getRiotAccountByPuuid(string $puuid): RiotApiResponse {
        $endpoint = "riot/account/v1/accounts/by-puuid/$puuid";
        return $this->fetchFromEndpoint($endpoint);
    }

    public function getRankByPuuid(string $puuid): RiotApiResponse {
        $endpoint = "lol/league/v4/entries/by-puuid/$puuid";
        return $this->fetchFromEndpoint($endpoint);
    }

    public function getMatchByMatchId(string $matchId): RiotApiResponse {
        $endpoint = "lol/match/v5/matches/$matchId";
        return $this->fetchFromEndpoint($endpoint);
    }
}