<?php

namespace App\Service;

class OplApiService {
	/**
	 * @throws \Exception
	 */
	public function fetchFromEndpoint(string $endpoint): array {
        if (empty($_ENV['OPL_BEARER_TOKEN']) || empty($_ENV['USER_AGENT'])) {
            throw new \Exception('Missing API credentials');
        }

        $apiUrl = "https://www.opleague.pro/api/v4/$endpoint";
        $options = ["http" => [
            "header" => [
                "Authorization: Bearer {$_ENV['OPL_BEARER_TOKEN']}",
                "User-Agent: {$_ENV['USER_AGENT']}",
            ]
        ]];
        $context = stream_context_create($options);
        $response = @file_get_contents($apiUrl, context: $context);

        if (!isset($http_response_header)) {
            throw new \Exception('No response from OPL API');
        }

        $httpStatus = $http_response_header[0] ?? '';
        if ($response === false || !str_contains($httpStatus, '200')) {
            throw new \Exception("Error from OPL API: $httpStatus");
        }

        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Invalid JSON received from OPL API');
        }
        if (isset($data['error'])) {
            throw new \Exception('API error: ' . $data['error']);
        }
        return $data['data'];
    }
}