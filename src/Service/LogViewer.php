<?php

namespace App\Service;

use App\Domain\Entities\UpdateJob;
use App\Domain\Enums\Jobs\UpdateJobType;
use App\Domain\Repositories\UpdateJobRepository;
use DirectoryIterator;

class LogViewer {
    private const string LOGS_BASE_PATH = BASE_PATH . '/logs';
    private const string JOBS_LOG_PATH = self::LOGS_BASE_PATH . '/jobs';
    
    private UpdateJobRepository $jobRepo;
    public function __construct() {
        $this->jobRepo = new UpdateJobRepository();
    }
    
    /**
     * @return array{general: array, jobs: array}
     */
    public function getAllLogs(): array {
        return [
            'general' => $this->getGeneralLogs(),
            'jobs' => $this->getJobLogs()
        ];
    }
    
    /**
     * Holt allgemeine Log-Dateien (admin_update.log, user_update.log, etc.)
     * @return array<array{name: string, path: string, size: int, modified: int}>
     */
    public function getGeneralLogs(): array {
        $logs = [];
        $dir = new DirectoryIterator(self::LOGS_BASE_PATH);
        
        foreach ($dir as $file) {
            if ($file->isDot() || $file->isDir() || $file->getExtension() !== 'log') {
                continue;
            }
            
            $logs[] = [
                'name' => $file->getFilename(),
                'path' => 'logs/' . $file->getFilename(),
                'size' => $file->getSize(),
                'modified' => $file->getMTime()
            ];
        }
        
        // Sortiere nach Änderungsdatum (neueste zuerst)
        usort($logs, fn($a, $b) => $b['modified'] <=> $a['modified']);
        
        return $logs;
    }
    
    /**
     * Holt Jobs aus der Datenbank und prüft auf zugehörige Log-Dateien
     * @return array<string, array>
     */
    public function getJobLogs(): array {
        $jobTypeMapping = [
            'admin_updates' => UpdateJobType::ADMIN,
            'user_updates' => UpdateJobType::USER,
            'cron_updates' => UpdateJobType::CRON,
            'ddragon_updates' => UpdateJobType::ADMIN // DDragon läuft als Admin-Job
        ];
        
        $logs = [];
        
        foreach ($jobTypeMapping as $label => $jobType) {
            // Hole die neuesten 50 Jobs dieses Typs
            $jobs = $this->jobRepo->findAll(type: $jobType);
            
            // Trenne DDragon-Downloads von anderen Admin-Jobs
            $jobs = array_filter($jobs, function($job) use ($label) {
                if ($label === 'ddragon_updates') {
                    return $job->action->isDdragonDownload();
                } else if ($label === 'admin_updates') {
                    return !$job->action->isDdragonDownload();
                }
                return true;
            });
            
            $jobs = array_slice($jobs, 0, 50);
            
            $logs[$label] = array_map(function(UpdateJob $job) use ($label) {
                return $this->mapJobToLogEntry($job, $label);
            }, $jobs);
        }
        
        return $logs;
    }
    
    /**
     * Mappt einen Job zu einem Log-Entry mit Log-Datei-Info
     */
    private function mapJobToLogEntry(UpdateJob $job, string $category): array {
        $logFilePath = $this->findLogFileForJob($job, $category);
        $hasLogFile = $logFilePath !== null;
        $logFileHasJobLogs = false;
        $logFileSize = 0;
        
        if ($hasLogFile) {
            $fullPath = BASE_PATH . '/' . $logFilePath;
            if (file_exists($fullPath)) {
                $logFileSize = filesize($fullPath);
                $logFileHasJobLogs = $logFileSize > 0;
            }
        }
        
        return [
            'jobId' => $job->id,
            'type' => $job->type->value,
            'action' => $job->action->value,
            'status' => $job->status->value,
            'progress' => $job->progress,
            'message' => $job->message,
            'resultMessage' => $job->resultMessage,
            'createdAt' => $job->createdAt->format('Y-m-d H:i:s'),
            'startedAt' => $job->startedAt?->format('Y-m-d H:i:s'),
            'finishedAt' => $job->finishedAt?->format('Y-m-d H:i:s'),
            'updatedAt' => $job->updatedAt->format('Y-m-d H:i:s'),
            'contextName' => $this->getJobContextName($job),
            'hasLogFile' => $hasLogFile,
            'logFilePath' => $logFilePath,
            'logFileSize' => $logFileSize,
            'logFileHasJobLogs' => $logFileHasJobLogs,
            'hasDbMessage' => !empty($job->message),
            'hasResultMessage' => !empty($job->resultMessage)
        ];
    }
    
    /**
     * Findet die Log-Datei für einen Job
     */
    private function findLogFileForJob(UpdateJob $job, string $category): ?string {
        $basePath = self::JOBS_LOG_PATH . '/' . $category;
        
        if (!is_dir($basePath)) {
            return null;
        }
        
        // Suche nach Datei mit Job-ID im Namen
        $pattern = "*-{$job->id}_*.log";
        $files = glob($basePath . '/' . $pattern);

        // Vor der ID dürfen nur Buchstaben/Unterstriche, keine Ziffern stehen, sonst werden Teile von Timestamps gematcht
        $files = array_filter($files, function($file) use ($job) {
            return preg_match('/\D-' . $job->id . '_/', basename($file));
        });
        
        if (empty($files)) {
            return null;
        }
        
        // Nimm die neueste Datei wenn mehrere existieren
        usort($files, fn($a, $b) => filemtime($b) <=> filemtime($a));
        $relativePath = str_replace(BASE_PATH . '/', '', $files[0]);
        
        return $relativePath;
    }
    
    /**
     * Gibt den Context-Namen eines Jobs zurück
     */
    private function getJobContextName(UpdateJob $job): ?string {
        if ($job->context === null) {
            return null;
        }
        
        return match(get_class($job->context)) {
            'App\\Domain\\Entities\\Tournament' => $job->context->getShortName() ?? null,
            'App\\Domain\\Entities\\Team' => $job->context->name ?? null,
            'App\\Domain\\Entities\\Matchup' => "Matchup #{$job->context->id}",
            'App\\Domain\\Entities\\Patch' => $job->context->patchNumber ?? null,
            default => null
        };
    }
    
    /**
     * Holt die DB-Message eines Jobs
     */
    public function getJobMessage(int $jobId): ?string {
        $job = $this->jobRepo->findById($jobId);
        return $job?->message;
    }
    
    /**
     * Holt die Result-Message eines Jobs
     */
    public function getJobResultMessage(int $jobId): ?string {
        $job = $this->jobRepo->findById($jobId);
        return $job?->resultMessage;
    }
    
    /**
     * Holt vollständige Job-Details
     */
    public function getJobDetails(int $jobId): ?array {
        $job = $this->jobRepo->findById($jobId);
        
        if ($job === null) {
            return null;
        }
        
        // Bestimme Kategorie für Log-File-Suche
        $category = match($job->type) {
            UpdateJobType::USER => 'user_updates',
            UpdateJobType::CRON => 'cron_updates',
            UpdateJobType::ADMIN => $job->action->isDdragonDownload() ? 'ddragon_updates' : 'admin_updates',
        };
        
        return $this->mapJobToLogEntry($job, $category);
    }
    
    /**
     * Liest Log-Datei und gibt Inhalt zurück
     */
    public function getLogContent(string $relativePath): ?string {
        $fullPath = BASE_PATH . '/' . $relativePath;
        
        // Sicherheitscheck: Stelle sicher, dass Pfad im logs-Verzeichnis liegt
        if (!$this->isPathInLogsDirectory($fullPath)) {
            return null;
        }
        
        if (!file_exists($fullPath)) {
            return null;
        }
        
        return file_get_contents($fullPath);
    }
    
    /**
     * Liest die letzten N Zeilen einer Log-Datei
     */
    public function getLogTail(string $relativePath, int $lines = 100): ?string {
        $fullPath = BASE_PATH . '/' . $relativePath;
        
        if (!$this->isPathInLogsDirectory($fullPath) || !file_exists($fullPath)) {
            return null;
        }
        
        $file = new \SplFileObject($fullPath);
        $file->seek(PHP_INT_MAX);
        $lastLine = $file->key();
        $startLine = max(0, $lastLine - $lines);
        
        $file->seek($startLine);
        $content = '';
        while (!$file->eof()) {
            $content .= $file->fgets();
        }
        
        return $content;
    }
    
    private function isPathInLogsDirectory(string $path): bool {
        $realPath = realpath($path);
        $logsPath = realpath(self::LOGS_BASE_PATH);
        return $realPath !== false && str_starts_with($realPath, $logsPath);
    }
    
    /**
     * Formatiert Dateigröße für Anzeige
     */
    public static function formatFileSize(int $bytes): string {
        $units = ['B', 'KB', 'MB', 'GB'];
        $power = $bytes > 0 ? floor(log($bytes, 1024)) : 0;
        return round($bytes / pow(1024, $power), 2) . ' ' . $units[$power];
    }
}