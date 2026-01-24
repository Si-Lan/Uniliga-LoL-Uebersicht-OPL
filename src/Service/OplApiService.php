<?php

namespace App\Service;

class OplApiService extends ApiService {
	/**
	 * @param string $endpoint
	 * @return ApiResponse
	 */
	public function fetchFromEndpoint(string $endpoint): ApiResponse {
        if (empty($_ENV['OPL_BEARER_TOKEN']) || empty($_ENV['USER_AGENT'])) {
            return ApiResponse::error('Missing API credentials');
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
            return ApiResponse::error('No response from OPL API');
        }

		$statusCode = $this->parseStatusCode($http_response_header);
		$headers = $this->parseHeaders($http_response_header);

        if ($response === false || $statusCode !== 200) {
            return ApiResponse::error("Error from OPL API: Code $statusCode", $statusCode, $headers);
        }

        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return ApiResponse::error('Invalid JSON received from OPL API', $statusCode, $headers);
        }
        if (isset($data['error'])) {
            return ApiResponse::error('API error: ' . $data['error'], $statusCode, $headers);
        }
        return ApiResponse::success($data['data'], $statusCode, $headers);
    }

	/**
	 * @param int $eventId
	 * @return string|null
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