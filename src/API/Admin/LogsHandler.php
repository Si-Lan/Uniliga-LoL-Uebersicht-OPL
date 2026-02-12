<?php

namespace App\API\Admin;

use App\API\AbstractHandler;
use App\Service\LogViewer;

class LogsHandler extends AbstractHandler {
    private LogViewer $logViewer;
    
    public function __construct() {
        $this->logViewer = new LogViewer();
    }
    
    public function getLogsAll(): void {
        $logs = $this->logViewer->getAllLogs();
        echo json_encode($logs);
    }
    
    public function getLogsContentAll(): void {
        $path = $_GET['path'] ?? null;
        
        if (!$path) {
            $this->sendErrorResponse(400, 'Missing path parameter');
        }
        
        $tail = isset($_GET['tail']) ? (int)$_GET['tail'] : null;
        
        if ($tail !== null) {
            $content = $this->logViewer->getLogTail($path, $tail);
        } else {
            $content = $this->logViewer->getLogContent($path);
        }
        
        if ($content === null) {
            $this->sendErrorResponse(404, 'Log file not found');
        }
        
        echo json_encode(['content' => $content]);
    }
    
    public function getLogs($jobId): void {
        if (!$jobId) {
            $this->sendErrorResponse(400, 'Missing jobId parameter');
        }
        
        $jobDetails = $this->logViewer->getJobDetails((int)$jobId);
        
        if ($jobDetails === null) {
            $this->sendErrorResponse(404, 'Job not found');
        }
        
        echo json_encode($jobDetails);
    }}