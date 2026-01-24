<?php

namespace App\Service;

abstract class ApiService {
    /**
     * Parst den Statuscode aus $http_response_header.
     *
     * @param array $httpResponseHeader
     * @return int|null
     */
    protected function parseStatusCode(array $httpResponseHeader): ?int {
        $statusLine = $httpResponseHeader[0] ?? '';
        if (preg_match('#HTTP/\S+\s+(\d{3})#', $statusLine, $m)) {
            return (int)$m[1];
        }
        return null;
    }

    /**
     * Parst die Headers aus $http_response_header.
     *
     * @param array $httpResponseHeader
     * @return array
     */
    protected function parseHeaders(array $httpResponseHeader): array {
        $headers = [];
        foreach ($httpResponseHeader as $hdr) {
            if (str_contains($hdr, ':')) {
                [$k, $v] = explode(':', $hdr, 2);
                $headers[trim($k)] = trim($v);
            }
        }
        return $headers;
    }
}