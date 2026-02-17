<?php

namespace App\UI\Components\Admin;

use App\UI\Components\Helpers\IconRenderer;

class JobItem {
    public function __construct(
        private array $job
    ) {}

    public function render(): string {
        $job = $this->job;
        
        $statusClass = match($job['status']) {
            'success' => 'job-success',
            'error' => 'job-error',
            'running' => 'job-running',
            'queued' => 'job-queued',
            'cancelled' => 'job-cancelled',
            'abandoned' => 'job-abandoned',
            default => ''
        };
        
        $statusIcon = match($job['status']) {
            'success' => 'check_circle',
            'error' => 'error',
            'running' => 'autorenew',
            'queued' => 'schedule',
            'cancelled' => 'cancel',
            'abandoned' => 'warning',
            default => 'help'
        };
        
        ob_start();
        ?>
        <div class="job-item <?= $statusClass ?>" data-job-id="<?= $job['jobId'] ?>">
            <div class="job-header">
                <?= IconRenderer::getMaterialIconSpan($statusIcon, ['job-status-icon'])?>
                <div class="job-info">
                    <div class="job-title">
                        #<?= $job['jobId'] ?> - <?= htmlspecialchars($job['action']) ?>
                    </div>
                    <div class="job-meta">
                        <?php if ($job['status'] === 'running'): ?>
                            <span class="job-progress"><?= round($job['progress']) ?>%</span>
                            <div class="job-progress-bar">
                                <div class="job-progress-fill" style="width: <?= $job['progress'] ?>%"></div>
                            </div>
                        <?php else: ?>
                            <span class="job-time"><?= $job['finishedAt'] ?? $job['updatedAt'] ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="job-actions">
                <?php if ($job['hasDbMessage']): ?>
                    <button class="job-action-btn view-job-message-btn" 
                            data-job-id="<?= $job['jobId'] ?>" 
                            title="View Message">
                        <?= IconRenderer::getMaterialIconSpan('description')?>
                    </button>
                <?php endif; ?>
                <?php if ($job['hasResultMessage']): ?>
                    <button class="job-action-btn view-result-message-btn" 
                            data-job-id="<?= $job['jobId'] ?>" 
                            title="View Result Message">
                        <?= IconRenderer::getMaterialIconSpan('assignment_turned_in')?>
                    </button>
                <?php endif; ?>
                <?php if ($job['hasLogFile']): ?>
                    <button class="job-action-btn view-log-file-btn"
                            data-path="<?= htmlspecialchars($job['logFilePath']) ?>"
                            title="<?= $job['logFileHasJobLogs'] ? 'Log File (Has Logs!)' : 'Log File (Empty)' ?>">
                        <?= $job['logFileHasJobLogs'] ? IconRenderer::getMaterialIconSpan('inbox_text') : IconRenderer::getMaterialIconSpan('inbox')?>
                    </button>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    public function __toString(): string {
        return $this->render();
    }
}
