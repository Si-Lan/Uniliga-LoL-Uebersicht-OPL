<?php

use App\Core\Utilities\UserContext;
use App\Domain\Repositories\UpdateJobRepository;
use App\Domain\Repositories\PatchRepository;
use App\Domain\Repositories\TournamentRepository;
use App\Domain\Repositories\RankedSplitRepository;
use App\Domain\Repositories\MatchupChangeSuggestionRepository;
use App\Domain\Enums\Jobs\UpdateJobStatus;
use App\Domain\Enums\SuggestionStatus;
use App\UI\Components\Navigation\Header;
use App\UI\Enums\HeaderType;
use App\UI\Page\AssetManager;
use App\UI\Page\PageMeta;
use App\UI\Components\Helpers\IconRenderer;

$pageMeta = new PageMeta('Admin Dashboard', bodyClass: 'admin admin-dashboard');
AssetManager::addCssAsset('admin/dashboard.css');
AssetManager::addJsModule('admin/dashboard');

echo new Header(HeaderType::ADMIN);

$jobRepo = new UpdateJobRepository();
$patchRepo = new PatchRepository();
$tournamentRepo = new TournamentRepository();
$rankedSplitRepo = new RankedSplitRepository();
$suggestionRepo = new MatchupChangeSuggestionRepository();

$runningJobs = $jobRepo->findAll(status: UpdateJobStatus::RUNNING);
$recentFailedJobs = $jobRepo->findAll(status: UpdateJobStatus::ERROR, limit: 5);
$recentFailedJobs = array_filter($recentFailedJobs, fn($j) => $j->createdAt->getTimestamp() > time() - 172800);
$recentAbandonedJobs = $jobRepo->findAll(status: UpdateJobStatus::ABANDONED, limit: 5);
$recentAbandonedJobs = array_filter($recentAbandonedJobs, fn($j) => $j->createdAt->getTimestamp() > time() - 172800);
$recentFailedAndAbandonedJobs = array_merge($recentFailedJobs, $recentAbandonedJobs);
$allPatches = $patchRepo->findAll();
$completedPatches = array_filter($allPatches, fn($p) => $p->data && $p->championWebp && $p->itemWebp && $p->runesWebp && $p->spellWebp);
$allTournaments = $tournamentRepo->findAllRootTournaments();
$runningTournaments = array_filter($allTournaments, fn($t) => $t->isRunning());
$activeTournaments = array_filter($allTournaments, fn($t) => !$t->archived);
$allSplits = $rankedSplitRepo->findAll();
$openSuggestions = $suggestionRepo->findAllByStatus(SuggestionStatus::PENDING);
?>

<main class="admin-dashboard">
    <div class="dashboard-header">
        <h1>Admin Dashboard</h1>
    </div>

    <div class="dashboard-grid">
        <!-- OPL Import Card -->
        <a href="/admin/opl" class="dashboard-card opl-card">
            <div class="card-header">
                <div class="card-icon"><?= IconRenderer::getMaterialIconDiv('edit_square')?></div>
                <h2>OPL Import</h2>
            </div>
            <div class="card-stats">
                <div class="stat">
                    <span class="stat-value"><?= count($activeTournaments) ?></span>
                    <span class="stat-label">Aktive Turniere</span>
                </div>
                <div class="stat secondary">
                    <span class="stat-value"><?= count($runningTournaments) ?></span>
                    <span class="stat-label">Laufende</span>
                </div>
                <div class="stat secondary">
                    <span class="stat-value"><?= count($allTournaments) ?></span>
                    <span class="stat-label">Gesamt</span>
                </div>
            </div>
        </a>

        <!-- Riot API Card -->
        <a href="/admin/rgapi" class="dashboard-card rgapi-card">
            <div class="card-header">
                <div class="card-icon"><?= IconRenderer::getMaterialIconDiv('videogame_asset')?></div>
                <h2>Riot API</h2>
            </div>
            <div class="card-stats">
                <div class="stat">
                    <span class="stat-value"><?= count($activeTournaments) ?></span>
                    <span class="stat-label">Aktive Turniere</span>
                </div>
                <div class="stat secondary">
                    <span class="stat-value"><?= count($runningTournaments) ?></span>
                    <span class="stat-label">Laufende</span>
                </div>
                <div class="stat secondary">
                    <span class="stat-value"><?= count($allTournaments) ?></span>
                    <span class="stat-label">Gesamt</span>
                </div>
            </div>
        </a>

        <!-- DDragon Card -->
        <a href="/admin/ddragon" class="dashboard-card ddragon-card">
            <div class="card-header">
                <div class="card-icon"><?= IconRenderer::getMaterialIconDiv('photo_library')?></div>
                <h2>DDragon</h2>
            </div>
            <div class="card-stats">
                <div class="stat">
                    <span class="stat-value"><?= count($completedPatches) ?></span>
                    <span class="stat-label">Vollständige Patches</span>
                </div>
                <div class="stat secondary">
                    <span class="stat-value"><?= count($allPatches) ?></span>
                    <span class="stat-label">Gesamt</span>
                </div>
            </div>
        </a>

        <!-- Logs Card -->
        <a href="/admin/logs" class="dashboard-card logs-card <?= count($recentFailedAndAbandonedJobs) > 0 ? 'has-errors' : '' ?>">
            <div class="card-header">
                <div class="card-icon"><?= IconRenderer::getMaterialIconDiv('description')?></div>
                <h2>Update Logs</h2>
            </div>
            <div class="card-stats">
                <div class="stat">
                    <span class="stat-value"><?= count($runningJobs) ?></span>
                    <span class="stat-label">Laufend</span>
                </div>
                <?php if (count($recentFailedAndAbandonedJobs) > 0): ?>
                <div class="stat error">
                    <span class="stat-value"><?= count($recentFailedAndAbandonedJobs) ?></span>
                    <span class="stat-label">Fehler</span>
                </div>
                <?php endif; ?>
            </div>
        </a>

        <!-- Ranked Splits Card -->
        <div role="button" tabindex="0" class="dashboard-card splits-card" id="ranked-splits-card" data-dialog-id="ranked-split-popup">
            <div class="card-header">
                <div class="card-icon"><?= IconRenderer::getMaterialIconDiv('stars')?></div>
                <h2>Ranked Splits</h2>
            </div>
            <div class="card-stats">
                <div class="stat">
                    <span class="stat-value"><?= count($allSplits) ?></span>
                    <span class="stat-label">Eingetragene Splits</span>
                </div>
            </div>
        </div>

        <!-- Match Change Suggestions Card -->
        <div role="button" tabindex="0" class="dashboard-card suggestions-card <?= count($openSuggestions) > 0 ? 'has-suggestions' : '' ?>" id="suggestions-card">
            <div class="card-header">
                <div class="card-icon"><?= IconRenderer::getMaterialIconDiv('edit_note')?></div>
                <h2>Match-Änderungen</h2>
            </div>
            <div class="card-stats">
                <?php if (count($openSuggestions) > 0): ?>
                <div class="stat highlight">
                    <span class="stat-value"><?= count($openSuggestions) ?></span>
                    <span class="stat-label">Offene Vorschläge</span>
                </div>
                <?php else: ?>
                <div class="stat">
                    <span class="stat-value">0</span>
                    <span class="stat-label">Offene Vorschläge</span>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Maintenance Mode Card -->
        <div class="dashboard-card maintenance-card <?= UserContext::isMaintenanceMode() ? 'maintenance-active' : '' ?>">
            <div class="card-header">
                <div class="card-icon"><?= IconRenderer::getMaterialIconDiv(UserContext::isMaintenanceMode() ? 'build' : 'build_circle')?></div>
                <h2>Maintenance Mode</h2>
            </div>
            <div class="card-content">
                <div class="maintenance-toggle">
                    <label class="toggle-switch">
                        <input type="checkbox" id="maintenance-mode-toggle" <?= UserContext::isMaintenanceMode() ? 'checked' : '' ?>>
                        <span class="toggle-slider"></span>
                    </label>
                    <span class="toggle-label"><?= UserContext::isMaintenanceMode() ? 'Aktiv' : 'Inaktiv' ?></span>
                </div>
            </div>
        </div>
    </div>
</main>

<?php
// Include Popups that might be needed
use App\UI\Components\Admin\RankedSplit\RankedSplitList;
use App\UI\Components\Popups\Popup;

echo new Popup("ranked-split-popup", content: new RankedSplitList());
?>