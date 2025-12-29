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

	/**
	 * @throws \Exception
	 */
	public function getFormatByEventId(int $eventId): string|null {
		if (empty($_ENV['OPL_BEARER_TOKEN']) || empty($_ENV['USER_AGENT'])) {
			throw new \Exception('Missing API credentials');
		}

		$eventUrl = "https://www.opleague.pro/event/$eventId/info";
		$options = ['http' => ['header' => ["User-Agent: {$_ENV['USER_AGENT']}"]]];
		$context = stream_context_create($options);
		$html = @file_get_contents($eventUrl, context: $context);

		$dom = new \DOMDocument();
		@$dom->loadHTML($html);

		$xpath = new \DOMXPath($dom);

		$infoColumns = $xpath->query("//body//main//section//div[contains(@class,'event-info__column')]");
		$jsContent = $infoColumns->item(1)->nodeValue;
		$buildBlocks = explode('Object.assign(data,', $jsContent);

		$infoCards = [];
		foreach ($buildBlocks as $buildBlock) {
			$buildBlock = trim($buildBlock);
			if ($buildBlock === '') continue;

			// Block bis zum schließenden ')'
			$pos = strpos($buildBlock, ')');
			if ($pos === false) continue;

			$jsonString = substr($buildBlock, 0, $pos);
			$jsonString = str_replace(["\r","\n"], '', $jsonString);

			$data = json_decode($jsonString, true);
			if (!$data) continue;

			// Alle Builds durchsuchen
			foreach ($data as $build) {
				if (isset($build['info_cards'])) {
					foreach ($build['info_cards'] as $card) {
						if (isset($card['title'], $card['value']['content'])) {
							$infoCards[$card['title']] = $card['value']['content'];
						}
					}
				}
			}
		}

		return $infoCards['format'] ?? null;
	}
}