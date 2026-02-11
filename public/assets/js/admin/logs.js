let currentLogPath = null;
let currentContentType = null; // 'general', 'job-message', 'job-logfile'
let isFullLogLoaded = false;
let autoRefresh = false;
let refreshInterval = null;
let cachedJobDetails = null; // Cache für aktuell geladene Job-Details
let currentJobId = null; // ID des aktuell geladenen Jobs

$(function() {
    // Restore last active job tab from localStorage
    const savedJobTab = localStorage.getItem('logsPage_activeJobTab');
    if (savedJobTab) {
        const $targetTab = $(`.job-tab[data-type="${savedJobTab}"]`);
        if ($targetTab.length > 0) {
            // Aktiviere den gespeicherten Tab
            $('.job-tab').removeClass('active');
            $targetTab.addClass('active');
            $('.job-logs').removeClass('active');
            $(`.job-logs[data-type="${savedJobTab}"]`).addClass('active');
        }
    }
    
    // Log-Datei auswählen (General Logs)
    $(document).on('click', '.general-logs .log-item', async function() {
        const isAlreadyActive = $(this).hasClass('active');
        
        $('.log-item').removeClass('active');
        $('.job-item').removeClass('active');
        $('.job-action-btn').removeClass('active');
        
        // Wenn bereits aktiv, abwählen
        if (isAlreadyActive) {
            currentLogPath = null;
            currentContentType = null;
            $('#current-log-name').text('Select a log file');
            $('#log-content').text('No log file selected');
            $('#refresh-log, #load-full-log').prop('disabled', true);
            return;
        }
        
        $(this).addClass('active');
        
        currentLogPath = $(this).data('path');
        currentContentType = 'general';
        isFullLogLoaded = false;
        const logName = $(this).find('.log-name').text();
        
        await loadLogContent(currentLogPath, true, true); // tail=true für letzte 100 Zeilen
        updateHeader(logName);
        updateLoadFullButton();
        
        // Schließe Job-Details und leere Cache wenn General Log ausgewählt wird
        $('#job-details-panel').slideUp(180);
        cachedJobDetails = null;
        currentJobId = null;
    });
    
    // Job-Item anklicken (zeigt Details oder schließt sie bei erneutem Klick)
    $(document).on('click', '.job-item', async function(e) {
        // Ignoriere wenn Button geklickt wurde
        if ($(e.target).closest('.job-action-btn').length) {
            return;
        }
        
        const jobId = $(this).data('job-id');
        const isAlreadyActive = $(this).hasClass('active');
        
        $('.log-item').removeClass('active');
        $('.job-item').removeClass('active');
        
        // Wenn bereits aktiv, schließe Panel
        if (isAlreadyActive) {
            $('#job-details-panel').slideUp(180);
            $('.job-action-btn').removeClass('active');
            currentLogPath = null;
            currentContentType = null;
            cachedJobDetails = null;
            currentJobId = null;
            $('#current-log-name').text('Select a log file');
            $('#log-content').text('No log file selected');
            $('#refresh-log, #load-full-log').prop('disabled', true);
            return;
        }
        
        // Ansonsten zeige Details
        $(this).addClass('active');
        const jobDetails = await showJobDetails(jobId);
        
        // Automatisch Message oder Log-Datei laden
        if (jobDetails) {
            await autoLoadJobContent(jobDetails, $(this));
        }
    });
    
    // Job DB-Message anzeigen
    $(document).on('click', '.view-job-message-btn', async function(e) {
        e.stopPropagation();
        const jobId = $(this).closest('.job-item').data('job-id');
        
        // Stelle sicher dass Job aktiv ist
        $('.log-item').removeClass('active');
        $('.job-item').removeClass('active');
        $(this).closest('.job-item').addClass('active');
        
        // Markiere diesen Button als aktiv
        $('.job-action-btn').removeClass('active');
        $(this).addClass('active');
        
        // Lade Job-Details nur wenn es ein anderer Job ist
        const jobDetails = await showJobDetails(jobId);
        if (jobDetails) {
            displayJobMessage(jobDetails, 'message');
        }
    });
    
    // Job Result-Message anzeigen
    $(document).on('click', '.view-result-message-btn', async function(e) {
        e.stopPropagation();
        const jobId = $(this).closest('.job-item').data('job-id');
        
        // Stelle sicher dass Job aktiv ist
        $('.log-item').removeClass('active');
        $('.job-item').removeClass('active');
        $(this).closest('.job-item').addClass('active');
        
        // Markiere diesen Button als aktiv
        $('.job-action-btn').removeClass('active');
        $(this).addClass('active');
        
        // Lade Job-Details nur wenn es ein anderer Job ist
        const jobDetails = await showJobDetails(jobId);
        if (jobDetails) {
            displayJobMessage(jobDetails, 'result');
        }
    });
    
    // Tab-Wechsel für Job-Kategorien
    $(document).on('click', '.job-tab', function() {
        const targetType = $(this).data('type');
        
        // Aktualisiere Tab-Status
        $('.job-tab').removeClass('active');
        $(this).addClass('active');
        
        // Zeige entsprechende Job-Liste
        $('.job-logs').removeClass('active');
        $(`.job-logs[data-type="${targetType}"]`).addClass('active');
        
        // Speichere aktiven Tab in localStorage
        localStorage.setItem('logsPage_activeJobTab', targetType);
    });
    
    // Job Log-File anzeigen
    $(document).on('click', '.view-log-file-btn', async function(e) {
        e.stopPropagation();
        $('.log-item').removeClass('active');
        $('.job-item').removeClass('active');
        $(this).closest('.job-item').addClass('active');
        
        // Markiere diesen Button als aktiv
        $('.job-action-btn').removeClass('active');
        $(this).addClass('active');
        
        const jobId = $(this).closest('.job-item').data('job-id');
        
        // Lade Job-Details nur wenn es ein anderer Job ist
        const jobDetails = await showJobDetails(jobId);
        if (jobDetails) {
            currentLogPath = jobDetails.logFilePath;
            currentContentType = 'job-logfile';
            const logName = `Job #${jobId} - Log File`;
            
            await loadLogContent(currentLogPath, false, false);
            updateHeader(logName);
            updateLoadFullButton();
        }
    });
    
    // Job-Details schließen
    $('#close-job-details').on('click', () => {
        $('#job-details-panel').slideUp(180);
        $('.job-item').removeClass('active');
        $('.job-action-btn').removeClass('active');
        currentLogPath = null;
        currentContentType = null;
        cachedJobDetails = null;
        currentJobId = null;
        $('#current-log-name').text('Select a log file');
        $('#log-content').text('No log file selected');
        $('#refresh-log, #load-full-log').prop('disabled', true);
    });
    
    // Refresh Button
    $('#refresh-log').on('click', () => {
        if (currentLogPath && currentContentType === 'general') {
            // Behalte aktuellen Zustand (tail oder full)
            loadLogContent(currentLogPath, !isFullLogLoaded, !isFullLogLoaded);
        } else if (currentLogPath) {
            loadLogContent(currentLogPath, false, false);
        }
    });
    
    // Load Full File / Last 100 Lines Button (Toggle)
    $('#load-full-log').on('click', () => {
        if (currentLogPath && currentContentType === 'general') {
            if (isFullLogLoaded) {
                // Wechsel zurück zu letzten 100 Zeilen
                isFullLogLoaded = false;
                loadLogContent(currentLogPath, true, true); // tail=true
            } else {
                // Lade ganze Datei
                isFullLogLoaded = true;
                loadLogContent(currentLogPath, false, false);
            }
            updateLoadFullButton();
        }
    });
});

async function loadLogContent(path, tail = false, scrollToBottom = false) {
    const url = tail 
        ? `/admin/api/logs/content?path=${encodeURIComponent(path)}&tail=100`
        : `/admin/api/logs/content?path=${encodeURIComponent(path)}`;
    
    const logsViewer = $('.logs-viewer');
    let loadingIndicator = logsViewer.find('.content-loading-indicator');
    if (loadingIndicator.length === 0) logsViewer.append('<div class="content-loading-indicator"></div>');
    
    try {
        const response = await fetch(url);
        if (!response.ok) {
            throw new Error('Failed to load log');
        }
        
        const data = await response.json();
        displayLogContent(data.content);
        
        // Scroll zum Ende wenn angefordert
        if (scrollToBottom || tail) {
            const logContent = document.getElementById('log-content');
            logContent.scrollTop = logContent.scrollHeight;
        }
        
        logsViewer.find('.content-loading-indicator').remove();
    } catch (error) {
        console.error('Error loading log:', error);
        $('#log-content').text('Error loading log file');
        logsViewer.find('.content-loading-indicator').remove();
    }
}

function displayLogContent(content) {
    const logContent = $('#log-content');
    
    // Prüfe ob Content leer ist
    if (!content || content.trim() === '') {
        logContent.html('Log file is empty');
        return;
    }
    
    // Syntax-Highlighting für Log-Level
    const highlighted = content
        .replace(/ERROR/g, '<span style="color: #f44336; font-weight: bold;">[ERROR]</span>')
        .replace(/WARNING/g, '<span style="color: #ff9800; font-weight: bold;">[WARNING]</span>')
        .replace(/INFO/g, '<span style="color: #4caf50;">[INFO]</span>')
        .replace(/DEBUG/g, '<span style="color: #2196f3;">[DEBUG]</span>')
        .replace(/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/g, '<span style="color: #9e9e9e;">$&</span>');
    
    logContent.html(highlighted);
}

function updateHeader(logName) {
    $('#current-log-name').text(logName);
    $('#refresh-log').prop('disabled', false);
}

function updateLoadFullButton() {
    const $btn = $('#load-full-log');
    
    if (currentContentType === 'general') {
        $btn.show();
        $btn.prop('disabled', false);
        if (isFullLogLoaded) {
            $btn.find('.material-symbol').addClass('symbol_hidden');
            $btn.find('.material-symbol.vertical_align_bottom').removeClass('symbol_hidden');
            $btn.contents().filter(function() { return this.nodeType === 3; }).last().replaceWith(' Last 100 Lines');
        } else {
            $btn.find('.material-symbol').addClass('symbol_hidden');
            $btn.find('.material-symbol.unfold_more').removeClass('symbol_hidden');
            $btn.contents().filter(function() { return this.nodeType === 3; }).last().replaceWith(' Load Full File');
        }
    } else {
        // Verstecke Button für Job Content
        $btn.hide();
    }
}

async function showJobDetails(jobId) {
    // Wenn bereits geladen und gleicher Job, nutze Cache
    if (currentJobId === jobId && cachedJobDetails) {
        return cachedJobDetails;
    }
    
    const logsViewer = $('.logs-viewer');
    let loadingIndicator = logsViewer.find('.content-loading-indicator');
    if (loadingIndicator.length === 0) logsViewer.append('<div class="content-loading-indicator"></div>');
    
    try {
        const response = await fetch(`/admin/api/logs/${jobId}`);
        if (!response.ok) throw new Error('Failed to load job details');
        
        const job = await response.json();
        
        // Cache Job-Details
        cachedJobDetails = job;
        currentJobId = jobId;
        
        // Fülle Job-Details Panel
        $('#detail-job-id').text(job.jobId);
        $('#detail-type').text(job.type);
        $('#detail-action').text(job.action);
        $('#detail-status').html(getStatusBadge(job.status));
        
        // Progress (nur bei running)
        if (job.status === 'running') {
            $('#detail-progress-row').show();
            $('#detail-progress').text(Math.round(job.progress) + '%');
        } else {
            $('#detail-progress-row').hide();
        }
        
        // Context
        if (job.contextName) {
            $('#detail-context-row').show();
            $('#detail-context').text(job.contextName);
        } else {
            $('#detail-context-row').hide();
        }
        
        // Timestamps
        $('#detail-created').text(formatTimestamp(job.createdAt || job.updatedAt));
        
        if (job.startedAt) {
            $('#detail-started-row').show();
            $('#detail-started').text(formatTimestamp(job.startedAt));
        } else {
            $('#detail-started-row').hide();
        }
        
        $('#detail-updated').text(formatTimestamp(job.updatedAt));
        
        if (job.finishedAt) {
            $('#detail-finished-row').show();
            $('#detail-finished').text(formatTimestamp(job.finishedAt));
        } else {
            $('#detail-finished-row').hide();
        }
        
        // Zeige Panel
        $('#job-details-panel').slideDown(180);
        
        logsViewer.find('.content-loading-indicator').remove();
        return cachedJobDetails;
        
    } catch (error) {
        console.error('Error loading job details:', error);
        logsViewer.find('.content-loading-indicator').remove();
        cachedJobDetails = null;
        currentJobId = null;
        return null;
    }
}

function getStatusBadge(status) {
    const badges = {
        'success': '<span class="status-badge status-success">Success</span>',
        'error': '<span class="status-badge status-error">Error</span>',
        'running': '<span class="status-badge status-running">Running</span>',
        'queued': '<span class="status-badge status-queued">Queued</span>',
        'cancelled': '<span class="status-badge status-cancelled">Cancelled</span>',
        'abandoned': '<span class="status-badge status-abandoned">Abandoned</span>'
    };
    return badges[status] || status;
}

function formatTimestamp(timestamp) {
    if (!timestamp) return '-';
    // Format: "10.02.2026 14:30:45"
    const date = new Date(timestamp);
    return date.toLocaleString('de-DE', { 
        day: '2-digit', 
        month: '2-digit', 
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
    });
}

function displayJobMessage(jobDetails, type = 'both') {
    currentLogPath = null;
    currentContentType = 'job-message';
    
    let content = '';
    let headerText = '';
    
    if (type === 'message' && jobDetails.message) {
        headerText = `Job #${jobDetails.jobId} - Message`;
        content = jobDetails.message;
    } else if (type === 'result' && jobDetails.resultMessage) {
        headerText = `Job #${jobDetails.jobId} - Result Message`;
        content = jobDetails.resultMessage;
    } else if (type === 'both') {
        headerText = `Job #${jobDetails.jobId} - DB Messages`;
        if (jobDetails.message) {
            content += '=== JOB MESSAGE ===\n\n' + jobDetails.message + '\n\n';
        }
        if (jobDetails.resultMessage) {
            content += '=== RESULT MESSAGE ===\n\n' + jobDetails.resultMessage;
        }
    }
    
    if (!content) {
        content = 'No messages available for this job';
        headerText = headerText || `Job #${jobDetails.jobId} - No Messages`;
    }
    
    updateHeader(headerText);
    updateLoadFullButton();
    displayLogContent(content);
}

async function autoLoadJobContent(jobDetails, $jobItem) {
    // Entferne alle aktiven Button-Zustände
    $('.job-action-btn').removeClass('active');
    
    // Prüfe zuerst ob Messages existieren (priorisiere resultMessage)
    if (jobDetails.hasResultMessage) {
        const $resultBtn = $jobItem.find('.view-result-message-btn');
        $resultBtn.addClass('active');
        displayJobMessage(jobDetails, 'result');
        return;
    }
    
    if (jobDetails.hasDbMessage) {
        const $messageBtn = $jobItem.find('.view-job-message-btn');
        $messageBtn.addClass('active');
        displayJobMessage(jobDetails, 'message');
        return;
    }
    
    // Keine Message, prüfe Log-Datei
    const $logFileBtn = $jobItem.find('.view-log-file-btn');
    if ($logFileBtn.length > 0) {
        $logFileBtn.addClass('active');
        currentLogPath = $logFileBtn.data('path');
        currentContentType = 'job-logfile';
        const logName = `Job #${jobDetails.jobId} - Log File`;
        await loadLogContent(currentLogPath, false, false);
        updateHeader(logName);
        updateLoadFullButton();
        return;
    }
    
    // Keine Message und kein Log, leere den Viewer
    currentLogPath = null;
    currentContentType = null;
    $('#current-log-name').text(`Job #${jobDetails.jobId} - No logs available`);
    $('#log-content').text('No logs available for this job');
    $('#refresh-log, #load-full-log').prop('disabled', true);
}