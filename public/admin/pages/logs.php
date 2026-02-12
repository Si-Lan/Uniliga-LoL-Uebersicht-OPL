<?php

use App\Service\LogViewer;
use App\UI\Components\Helpers\IconRenderer;
use App\UI\Components\Navigation\Header;
use App\UI\Enums\HeaderType;
use App\UI\Page\AssetManager;
use App\UI\Page\PageMeta;

$pageMeta = new PageMeta('Logs', bodyClass: 'admin admin-logs');
AssetManager::addJsModule('admin/logs');
AssetManager::addCssAsset('admin/logs.css');

echo new Header(HeaderType::ADMIN_LOG);

$logViewer = new LogViewer();
$logs = $logViewer->getAllLogs();
?>

<main>
    <div class="logs-container">
        <div class="logs-sidebar">
            <h2>General Logs</h2>
            <div class="log-list general-logs">
                <?php foreach ($logs['general'] as $log): ?>
                    <div class="log-item" data-path="<?= htmlspecialchars($log['path']) ?>">
                        <span class="log-name"><?= htmlspecialchars($log['name']) ?></span>
                        <span class="log-size"><?= LogViewer::formatFileSize($log['size']) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <h2>Job Logs</h2>
            <div class="job-tabs">
                <?php 
                $tabLabels = [
                    'admin_updates' => 'Admin',
                    'user_updates' => 'User',
                    'cron_updates' => 'Cron',
                    'ddragon_updates' => 'DDragon'
                ];
                $isFirst = true;
                foreach ($logs['jobs'] as $type => $typeLogs): 
                ?>
                    <button class="job-tab <?= $isFirst ? 'active' : '' ?>" data-type="<?= $type ?>">
                        <?= $tabLabels[$type] ?? ucfirst(str_replace('_', ' ', $type)) ?>
                        <span class="job-count"><?= count($typeLogs) ?></span>
                    </button>
                <?php 
                    $isFirst = false;
                endforeach; 
                ?>
            </div>
            <?php 
            $isFirst = true;
            foreach ($logs['jobs'] as $type => $typeLogs): 
            ?>
                <div class="log-list job-logs <?= $isFirst ? 'active' : '' ?>" data-type="<?= $type ?>">
                    <?php foreach ($typeLogs as $job): ?>
                        <?php 
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
                    <?php endforeach; ?>
                </div>
            <?php 
                $isFirst = false;
            endforeach; 
            ?>
        </div>
        
        <div class="logs-viewer">
            <div class="job-details-panel" id="job-details-panel" style="display: none;">
                <div class="job-details-header">
                    <h3>Job Details</h3>
                    <button id="close-job-details" class="close-btn">
                        <?= IconRenderer::getMaterialIconSpan('close')?>
                    </button>
                </div>
                <div class="job-details-content">
                    <div class="job-detail-row">
                        <span class="job-detail-label">Job ID:</span>
                        <span class="job-detail-value" id="detail-job-id">-</span>
                    </div>
                    <div class="job-detail-row">
                        <span class="job-detail-label">Type:</span>
                        <span class="job-detail-value" id="detail-type">-</span>
                    </div>
                    <div class="job-detail-row">
                        <span class="job-detail-label">Action:</span>
                        <span class="job-detail-value" id="detail-action">-</span>
                    </div>
                    <div class="job-detail-row">
                        <span class="job-detail-label">Status:</span>
                        <span class="job-detail-value" id="detail-status">-</span>
                    </div>
                    <div class="job-detail-row" id="detail-progress-row" style="display: none;">
                        <span class="job-detail-label">Progress:</span>
                        <span class="job-detail-value" id="detail-progress">-</span>
                    </div>
                    <div class="job-detail-row" id="detail-context-row" style="display: none;">
                        <span class="job-detail-label">Context:</span>
                        <span class="job-detail-value" id="detail-context">-</span>
                    </div>
                    <div class="job-detail-row">
                        <span class="job-detail-label">Created:</span>
                        <span class="job-detail-value" id="detail-created">-</span>
                    </div>
                    <div class="job-detail-row" id="detail-started-row" style="display: none;">
                        <span class="job-detail-label">Started:</span>
                        <span class="job-detail-value" id="detail-started">-</span>
                    </div>
                    <div class="job-detail-row">
                        <span class="job-detail-label">Updated:</span>
                        <span class="job-detail-value" id="detail-updated">-</span>
                    </div>
                    <div class="job-detail-row" id="detail-finished-row" style="display: none;">
                        <span class="job-detail-label">Finished:</span>
                        <span class="job-detail-value" id="detail-finished">-</span>
                    </div>
                </div>
            </div>
            <div class="logs-viewer-header">
                <h2 id="current-log-name">Select a log file</h2>
                <div class="logs-viewer-controls">
                    <button id="refresh-log" disabled>
                        <?= IconRenderer::getMaterialIconSpan('refresh')?>
                        Refresh
                    </button>
                    <button id="load-full-log" disabled>
                        <?= IconRenderer::getMaterialIconSpan('unfold_more', ['unfold_more'])?>
                        <?= IconRenderer::getMaterialIconSpan('vertical_align_bottom', ['vertical_align_bottom','symbol_hidden'])?>
                        Load Full File
                    </button>
                </div>
            </div>
            <pre id="log-content" class="log-content">No log file selected</pre>
        </div>
    </div>
</main>