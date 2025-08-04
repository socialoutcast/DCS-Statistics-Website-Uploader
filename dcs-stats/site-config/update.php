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
            <form id="update-form" class="form-section">
                <div class="form-group">
                    <label for="branch">Select Branch</label>
                    <select name="branch" id="branch">
                        <option value="main">Main</option>
                        <option value="Dev">Dev</option>
                    </select>
                </div>
                <div id="dev-warning" class="warning-box" style="display:none;">
                    Warning: Dev is not a stable release and may break your Dashboard.
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="backup" value="1"> Create backup before updating
                    </label>
                </div>
                <button type="submit" class="btn btn-primary">Start Update</button>
            </form>
            <pre id="log" class="update-log"></pre>
        </div>
    </main>
</div>
<script>
const branchSelect = document.getElementById('branch');
branchSelect.addEventListener('change', function() {
    document.getElementById('dev-warning').style.display = this.value === 'Dev' ? 'block' : 'none';
});

document.getElementById('update-form').addEventListener('submit', function(e) {
    e.preventDefault();
    const log = document.getElementById('log');
    log.textContent = '';
    const formData = new FormData(this);
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
    };
    xhr.send(formData);
});
</script>
</body>
</html>
