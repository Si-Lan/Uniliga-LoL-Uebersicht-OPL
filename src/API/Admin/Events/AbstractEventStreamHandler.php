<?php

namespace App\API\Admin\Events;

class AbstractEventStreamHandler {
	protected function sendSSEMessage(string $event, string $message): void {
		echo "event: $event\ndata: $message\n\n";
		flush();
	}
	protected function sendSSEMessageJson(string $event, array $data): void {
		$this->sendSSEMessage($event, json_encode($data));
	}

	protected function sendSSEHeartbeat(): void {
		$this->sendSSEMessageJson('heartbeat', ['time' => date('Y-m-d H:i:s')]);
	}

	protected function streamLoop(int $intervalSeconds, callable $callback): void {
		$now = microtime(true);
		$lastHeartbeat = $now;
		$lastCallback = $now;
		$heartbeatInterval = 15;

		$this->sendSSEHeartbeat();
		$callback();

		while (true) {
			if (connection_aborted()) break;

			sleep(1);
			$now = microtime(true);

			if ($now - $lastCallback >= $intervalSeconds) {
				$callback();
				$lastCallback = $now;
			}
			if ($now - $lastHeartbeat >= $heartbeatInterval) {
				$this->sendSSEHeartbeat();
				$lastHeartbeat = $now;
			}
		}
	}
}