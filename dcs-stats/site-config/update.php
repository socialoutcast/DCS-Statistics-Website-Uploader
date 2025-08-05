<?php
/**
 * Update Dashboard from GitHub branches
 */
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/admin_functions.php';

requireAdmin();
requirePermission('manage_updates');

$currentAdmin = getCurrentAdmin();

$pageTitle = 'Update Dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - Carrier Air Wing Command</title>
    <link rel="stylesheet" href="css/admin.css">
    <style>
        .update-log {
            background: #111;
            color: #0f0;
            padding: 10px;
            height: 300px;
            overflow-y: auto;
            white-space: pre-wrap;
            font-family: monospace;
        }
        .warning-box {
            background-color: rgba(255, 152, 0, 0.1);
            border: 1px solid var(--accent-warning);
            padding: 15px;
            border-radius: 4px;
            margin-top: 10px;
        }
        .row {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }
        .col-md-6 {
            flex: 1;
        }
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-primary {
            background-color: var(--accent-primary);
            color: white;
        }
        .badge-warning {
            background-color: var(--accent-warning);
            color: #000;
        }
        .btn-block {
            width: 100%;
            display: block;
        }
        .btn-info {
            background-color: var(--accent-info);
            color: white;
        }
        .btn-info:hover {
            background-color: #1976D2;
        }
        .mb-2 {
            margin-bottom: 10px;
        }
        .badge-info {
            background-color: #2196F3;
            color: white;
        }
        .badge-success {
            background-color: #4CAF50;
            color: white;
        }
        #git-status small {
            color: #666;
            font-weight: normal;
        }
        .mt-2 {
            margin-top: 10px;
        }
    </style>
</head>
<body>
<div class="admin-wrapper">
    <?php include 'nav.php'; ?>
    <main class="admin-main">
        <header class="admin-header">
            <h1><?= $pageTitle ?></h1>
            <div class="admin-user-menu">
                <div class="admin-user-info">
                    <div class="admin-username"><?= e($currentAdmin['username']) ?></div>
                    <div class="admin-role"><?= getRoleBadge($currentAdmin['role']) ?></div>
                </div>
                <a href="logout.php" class="btn btn-secondary btn-small">Logout</a>
            </div>
        </header>
        <div class="admin-content">
            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">System Information</h3>
                        </div>
                        <div class="card-content">
                            <?php
                            require_once __DIR__ . '/version_tracker.php';
                            require_once dirname(__DIR__) . '/dev_mode.php';
                            $versionInfo = initializeVersionTracking();
                            $currentBranch = $versionInfo['branch'];
                            $isDev = isDevMode();
                            ?>
                            <p><strong>Current Version:</strong> <?= $versionInfo['version'] ?></p>
                            <p><strong>Current Branch:</strong> <span class="badge badge-<?= $currentBranch === 'Dev' ? 'warning' : 'primary' ?>"><?= $currentBranch ?></span></p>
                            <?php if ($isDev): ?>
                                <p><strong>Git Status:</strong> <span id="git-status" class="text-muted">Loading...</span></p>
                            <?php endif; ?>
                            <p><strong>PHP Version:</strong> <?= PHP_VERSION ?></p>
                            <p><strong>Last Updated:</strong> <?= $versionInfo['updated_at'] ?? 'Unknown' ?></p>
                            
                            <div id="update-status" style="margin-top: 15px;">
                                <p class="text-muted">Checking for updates...</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Quick Actions</h3>
                        </div>
                        <div class="card-content">
                            <button class="btn btn-secondary btn-block mb-2" onclick="createBackup()">
                                <span class="nav-icon">💾</span> Create Manual Backup
                            </button>
                            <button class="btn btn-warning btn-block mb-2" onclick="showDowngradeModal()">
                                <span class="nav-icon">⬇️</span> Downgrade Version
                            </button>
                            <button class="btn btn-info btn-block mb-2" onclick="checkForUpdates()">
                                <span class="nav-icon">🔍</span> Check for Updates
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card mt-3">
                <div class="card-header">
                    <h3 class="card-title">Update Log</h3>
                </div>
                <pre id="log" class="update-log"></pre>
            </div>
            
            <div class="card mt-3">
                <div class="card-header">
                    <h3 class="card-title">Backup Management</h3>
                </div>
                <div id="backup-list" class="card-content">
                    <p class="text-muted">Loading backups...</p>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- Restore Modal -->
<div id="restoreModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Restore from Backup</h3>
            <button class="modal-close" onclick="closeModal('restoreModal')">&times;</button>
        </div>
        <div class="modal-body">
            <p>Select a backup to restore:</p>
            <div id="restore-backup-list">Loading backups...</div>
        </div>
    </div>
</div>

<!-- Downgrade Modal -->
<div id="downgradeModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Downgrade Version</h3>
            <button class="modal-close" onclick="closeModal('downgradeModal')">&times;</button>
        </div>
        <div class="modal-body">
            <form id="downgrade-form">
                <div class="form-group">
                    <label for="downgrade-version">Select Version</label>
                    <select id="downgrade-version" class="form-control">
                        <option value="">Loading versions...</option>
                    </select>
                </div>
                <div class="form-group">
                    <p class="text-muted">✓ Automatic backup will be created before downgrading</p>
                </div>
                <button type="submit" class="btn btn-warning">Downgrade</button>
            </form>
        </div>
    </div>
</div>

<script>
// Check for updates on page load
let updateAvailable = false;
let latestVersion = null;

function checkUpdateStatus() {
    fetch('api/check_updates.php')
        .then(response => response.text())
        .then(data => {
            const statusDiv = document.getElementById('update-status');
            
            // Parse the response to check if update is available
            if (data.includes('✅ Update Available!')) {
                updateAvailable = true;
                // Extract version from response
                const versionMatch = data.match(/Latest Release: (v?[\d.]+)/);
                if (versionMatch) {
                    latestVersion = versionMatch[1];
                }
                statusDiv.innerHTML = `
                    <div class="alert alert-info">
                        <strong>Update Available!</strong> Version ${latestVersion}
                        <button class="btn btn-primary btn-small" onclick="performUpdate()" style="margin-left: 10px;">
                            Update Now
                        </button>
                    </div>
                `;
            } else if (data.includes('✓ You are running the latest')) {
                statusDiv.innerHTML = '<p class="text-success">✓ System is up to date</p>';
            } else {
                statusDiv.innerHTML = '<p class="text-muted">Update status unknown</p>';
            }
            
            // Also populate versions for downgrade
            const versions = [];
            const versionMatches = data.matchAll(/- (v?[\d.]+)/g);
            for (const match of versionMatches) {
                versions.push(match[1]);
            }
            populateVersionSelect(versions);
        })
        .catch(error => {
            document.getElementById('update-status').innerHTML = '<p class="text-danger">Failed to check updates</p>';
        });
}

function performUpdate() {
    const formData = new FormData();
    formData.append('branch', 'main');
    
    const log = document.getElementById('log');
    log.textContent = 'Starting update...\n';
    
    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'api/update.php');
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    xhr.onprogress = function() {
        log.textContent = xhr.responseText;
        log.scrollTop = log.scrollHeight;
    };
    xhr.onload = function() {
        log.textContent = xhr.responseText;
        log.scrollTop = log.scrollHeight;
        setTimeout(() => {
            window.location.reload();
        }, 3000);
    };
    xhr.send(formData);
}

// Load backup list
function loadBackups() {
    fetch('api/list_backups.php')
        .then(response => response.json())
        .then(data => {
            const backupList = document.getElementById('backup-list');
            if (data.backups && data.backups.length > 0) {
                let html = '<div class="data-table-wrapper"><table class="data-table">';
                html += '<thead><tr><th>Backup Date</th><th>Version</th><th>Branch</th><th>Size</th><th>Status</th><th>Actions</th></tr></thead><tbody>';
                data.backups.forEach((backup, index) => {
                    const branchClass = backup.branch === 'Dev' ? 'badge-warning' : 'badge-primary';
                    const isProtected = index < 5;
                    const statusHtml = isProtected 
                        ? '<span class="badge badge-success">Protected</span>' 
                        : '<span class="badge badge-warning">Will be auto-deleted</span>';
                    html += `<tr>
                        <td>${backup.date}</td>
                        <td>${backup.version}</td>
                        <td><span class="badge ${branchClass}">${backup.branch}</span></td>
                        <td>${backup.size}</td>
                        <td>${statusHtml}</td>
                        <td>
                            <button class="btn btn-small btn-secondary" onclick="restoreBackup('${backup.name}')">Restore</button>
                            <button class="btn btn-small btn-danger" onclick="deleteBackup('${backup.name}')">Delete</button>
                        </td>
                    </tr>`;
                });
                html += '</tbody></table></div>';
                html += '<p class="text-muted mt-2">ℹ️ System keeps the 5 most recent backups. Older backups are automatically deleted.</p>';
                backupList.innerHTML = html;
            } else {
                backupList.innerHTML = '<p class="text-muted">No backups found.</p>';
            }
        })
        .catch(error => {
            document.getElementById('backup-list').innerHTML = '<p class="text-danger">Failed to load backups.</p>';
        });
}

function restoreBackup(filename) {
    if (!confirm('Are you sure you want to restore this backup? This will overwrite current files.')) {
        return;
    }
    
    const log = document.getElementById('log');
    log.textContent = 'Starting restore...\n';
    
    fetch('api/restore_backup.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ backup: filename })
    })
    .then(response => response.text())
    .then(data => {
        log.textContent += data;
        loadBackups();
    })
    .catch(error => {
        log.textContent += 'Restore failed: ' + error.message;
    });
}

function deleteBackup(filename) {
    if (!confirm('Are you sure you want to delete this backup?')) {
        return;
    }
    
    fetch('api/delete_backup.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ backup: filename })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadBackups();
        } else {
            alert('Failed to delete backup: ' + data.error);
        }
    })
    .catch(error => {
        alert('Failed to delete backup: ' + error.message);
    });
}

// Create manual backup
function createBackup() {
    const log = document.getElementById('log');
    log.textContent = 'Creating backup...\n';
    
    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'api/create_backup.php');
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    xhr.onprogress = function() {
        log.textContent = xhr.responseText;
        log.scrollTop = log.scrollHeight;
    };
    xhr.onload = function() {
        log.textContent = xhr.responseText;
        log.scrollTop = log.scrollHeight;
        loadBackups();
    };
    xhr.send();
}

// Check for updates
function checkForUpdates() {
    const log = document.getElementById('log');
    log.textContent = 'Checking for updates...\n';
    
    fetch('api/check_updates.php')
        .then(response => response.text())
        .then(data => {
            log.textContent = data;
        })
        .catch(error => {
            log.textContent = 'Failed to check for updates: ' + error.message;
        });
}


// Modal functions
function showRestoreModal() {
    document.getElementById('restoreModal').classList.add('active');
    loadBackupsForRestore();
}

function showDowngradeModal() {
    document.getElementById('downgradeModal').classList.add('active');
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.remove('active');
}

// Load backups for restore modal
function loadBackupsForRestore() {
    fetch('api/list_backups.php')
        .then(response => response.json())
        .then(data => {
            const restoreList = document.getElementById('restore-backup-list');
            if (data.backups && data.backups.length > 0) {
                let html = '<div class="backup-list">';
                data.backups.forEach(backup => {
                    const branchClass = backup.branch === 'Dev' ? 'badge-warning' : 'badge-primary';
                    html += `
                        <div class="backup-item" style="padding: 10px; border: 1px solid var(--border-color); margin-bottom: 10px; border-radius: 4px;">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <div>
                                    <strong>${backup.date}</strong><br>
                                    Version: ${backup.version} | <span class="badge ${branchClass}">${backup.branch}</span> | Size: ${backup.size}
                                </div>
                                <button class="btn btn-secondary btn-small" onclick="restoreBackup('${backup.name}'); closeModal('restoreModal');">
                                    Restore
                                </button>
                            </div>
                        </div>
                    `;
                });
                html += '</div>';
                restoreList.innerHTML = html;
            } else {
                restoreList.innerHTML = '<p class="text-muted">No backups available.</p>';
            }
        });
}

// Populate version select
function populateVersionSelect(versions) {
    const select = document.getElementById('downgrade-version');
    let html = '<option value="">Select a version</option>';
    versions.forEach(version => {
        if (version !== '<?= ADMIN_PANEL_VERSION ?>') {
            html += `<option value="${version}">${version}</option>`;
        }
    });
    select.innerHTML = html;
}

// Handle downgrade form
document.getElementById('downgrade-form').addEventListener('submit', function(e) {
    e.preventDefault();
    const version = document.getElementById('downgrade-version').value;
    if (!version) {
        alert('Please select a version');
        return;
    }
    
    closeModal('downgradeModal');
    
    const formData = new FormData();
    formData.append('version', version);
    
    const log = document.getElementById('log');
    log.textContent = `Downgrading to version ${version}...\n`;
    
    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'api/update.php');
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    xhr.onprogress = function() {
        log.textContent = xhr.responseText;
        log.scrollTop = log.scrollHeight;
    };
    xhr.onload = function() {
        log.textContent = xhr.responseText;
        log.scrollTop = log.scrollHeight;
        setTimeout(() => {
            window.location.reload();
        }, 3000);
    };
    xhr.send(formData);
});

// Load backups on page load
loadBackups();
checkUpdateStatus();

<?php if ($isDev): ?>
// In dev mode, check git status
function checkGitStatus() {
    fetch('api/git_status.php')
        .then(response => response.json())
        .then(data => {
            const statusEl = document.getElementById('git-status');
            if (data.success) {
                let html = `<span class="badge badge-info">${data.branch}</span>`;
                if (data.ahead > 0 || data.behind > 0) {
                    html += ' <small>(';
                    if (data.ahead > 0) html += `↑${data.ahead}`;
                    if (data.ahead > 0 && data.behind > 0) html += ' ';
                    if (data.behind > 0) html += `↓${data.behind}`;
                    html += ')</small>';
                }
                if (data.modified > 0) {
                    html += ` <span class="text-warning">• ${data.modified} modified</span>`;
                }
                if (data.untracked > 0) {
                    html += ` <span class="text-muted">• ${data.untracked} untracked</span>`;
                }
                statusEl.innerHTML = html;
            } else {
                statusEl.innerHTML = '<span class="text-danger">Not a git repository</span>';
            }
        })
        .catch(error => {
            document.getElementById('git-status').innerHTML = '<span class="text-danger">Failed to check</span>';
        });
}
checkGitStatus();
<?php endif; ?>
</script>
</body>
</html>
