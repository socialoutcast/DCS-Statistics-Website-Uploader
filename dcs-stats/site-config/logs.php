<?php
/**
 * Activity Logs Viewer
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/admin_functions.php';

// Require admin login and permission
requireAdmin();
requirePermission('view_logs');

// Get current admin
$currentAdmin = getCurrentAdmin();

// Get filter parameters
$filterAction = $_GET['action'] ?? '';
$filterAdmin = $_GET['admin'] ?? '';
$filterDateFrom = $_GET['date_from'] ?? date('Y-m-d', strtotime('-7 days'));
$filterDateTo = $_GET['date_to'] ?? date('Y-m-d');
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = RECORDS_PER_PAGE;

// Get all logs
$allLogs = json_decode(file_get_contents(ADMIN_LOGS_FILE), true) ?: [];

// Get admin users for filter and display
$users = getAdminUsers();
$userMap = [];
foreach ($users as $user) {
    $userMap[$user['id']] = $user['username'];
}

// Apply filters
$filteredLogs = [];
foreach ($allLogs as $log) {
    // Date filter
    $logDate = substr($log['created_at'], 0, 10);
    if ($logDate < $filterDateFrom || $logDate > $filterDateTo) {
        continue;
    }
    
    // Action filter
    if ($filterAction && $log['action'] !== $filterAction) {
        continue;
    }
    
    // Admin filter
    if ($filterAdmin && $log['admin_id'] != $filterAdmin) {
        continue;
    }
    
    // Add admin username
    $log['admin_username'] = $userMap[$log['admin_id']] ?? 'Unknown';
    
    $filteredLogs[] = $log;
}

// Sort by date descending
usort($filteredLogs, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});

// Pagination
$totalLogs = count($filteredLogs);
$totalPages = ceil($totalLogs / $perPage);
$offset = ($page - 1) * $perPage;
$logs = array_slice($filteredLogs, $offset, $perPage);

// Get unique actions for filter
$uniqueActions = array_unique(array_column($allLogs, 'action'));
sort($uniqueActions);

// Page title
$pageTitle = 'Activity Logs';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - Carrier Air Wing Command</title>
    <link rel="stylesheet" href="css/admin.css">
    <style>
        .filter-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .log-entry {
            padding: 15px;
            border-bottom: 1px solid var(--border-color);
        }
        
        .log-entry:last-child {
            border-bottom: none;
        }
        
        .log-entry:hover {
            background-color: var(--bg-tertiary);
        }
        
        .log-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }
        
        .log-action {
            font-weight: bold;
            color: var(--accent-primary);
        }
        
        .log-time {
            color: var(--text-muted);
            font-size: 12px;
        }
        
        .log-details {
            font-size: 14px;
            color: var(--text-secondary);
        }
        
        .log-meta {
            margin-top: 8px;
            font-size: 12px;
            color: var(--text-muted);
        }
        
        .action-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .action-login { background-color: rgba(76, 175, 80, 0.2); color: #4CAF50; }
        .action-logout { background-color: rgba(158, 158, 158, 0.2); color: #9E9E9E; }
        .action-ban { background-color: rgba(244, 67, 54, 0.2); color: #f44336; }
        .action-unban { background-color: rgba(255, 152, 0, 0.2); color: #ff9800; }
        .action-export { background-color: rgba(33, 150, 243, 0.2); color: #2196F3; }
        .action-edit { background-color: rgba(156, 39, 176, 0.2); color: #9c27b0; }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <?php include 'nav.php'; ?>
        
        <!-- Main Content -->
        <main class="admin-main">
            <!-- Header -->
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
            
            <!-- Content -->
            <div class="admin-content">
                <!-- Filters -->
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Filter Logs</h2>
                    </div>
                    
                    <form method="GET" action="" class="filter-form">
                        <div class="form-group">
                            <label for="filter_action">Action</label>
                            <select name="action" id="filter_action" class="form-control">
                                <option value="">All Actions</option>
                                <?php foreach ($uniqueActions as $action): ?>
                                    <option value="<?= e($action) ?>" <?= $filterAction === $action ? 'selected' : '' ?>>
                                        <?= e(LOG_ACTIONS[$action] ?? $action) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="filter_admin">Admin User</label>
                            <select name="admin" id="filter_admin" class="form-control">
                                <option value="">All Admins</option>
                                <?php foreach ($users as $user): ?>
                                    <option value="<?= $user['id'] ?>" <?= $filterAdmin == $user['id'] ? 'selected' : '' ?>>
                                        <?= e($user['username']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="filter_date_from">From Date</label>
                            <input type="date" name="date_from" id="filter_date_from" 
                                   class="form-control" value="<?= e($filterDateFrom) ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="filter_date_to">To Date</label>
                            <input type="date" name="date_to" id="filter_date_to" 
                                   class="form-control" value="<?= e($filterDateTo) ?>">
                        </div>
                        
                        <div class="form-group" style="display: flex; align-items: flex-end; gap: 10px;">
                            <button type="submit" class="btn btn-primary">Apply Filters</button>
                            <a href="logs.php" class="btn btn-secondary">Clear</a>
                        </div>
                    </form>
                </div>
                
                <!-- Logs List -->
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">
                            Activity Logs 
                            <span class="text-muted">(<?= number_format($totalLogs) ?> results)</span>
                        </h2>
                    </div>
                    
                    <?php if (empty($logs)): ?>
                        <p class="text-muted">No logs found for the selected criteria.</p>
                    <?php else: ?>
                        <div class="logs-list">
                            <?php foreach ($logs as $log): ?>
                                <?php
                                $actionClass = '';
                                if (strpos($log['action'], 'LOGIN') !== false) $actionClass = 'action-login';
                                elseif (strpos($log['action'], 'LOGOUT') !== false) $actionClass = 'action-logout';
                                elseif (strpos($log['action'], 'BAN') !== false && strpos($log['action'], 'UNBAN') === false) $actionClass = 'action-ban';
                                elseif (strpos($log['action'], 'UNBAN') !== false) $actionClass = 'action-unban';
                                elseif (strpos($log['action'], 'EXPORT') !== false) $actionClass = 'action-export';
                                elseif (strpos($log['action'], 'EDIT') !== false) $actionClass = 'action-edit';
                                ?>
                                <div class="log-entry">
                                    <div class="log-header">
                                        <div>
                                            <span class="action-badge <?= $actionClass ?>">
                                                <?= e($log['action']) ?>
                                            </span>
                                            <span class="log-action">
                                                <?= e(LOG_ACTIONS[$log['action']] ?? $log['action']) ?>
                                            </span>
                                        </div>
                                        <div class="log-time">
                                            <?= formatDate($log['created_at']) ?>
                                        </div>
                                    </div>
                                    
                                    <div class="log-details">
                                        <strong><?= e($log['admin_username']) ?></strong>
                                        <?php if ($log['target_type'] && $log['target_id']): ?>
                                            - <?= e($log['target_type']) ?>: <code><?= e($log['target_id']) ?></code>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <?php if ($log['details']): ?>
                                        <div class="log-meta">
                                            Details: <?= e(is_array($log['details']) ? json_encode($log['details']) : $log['details']) ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="log-meta">
                                        IP: <?= e($log['ip_address']) ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Pagination -->
                        <?= getPagination($totalLogs, $perPage, $page, 'logs.php?' . http_build_query([
                            'action' => $filterAction,
                            'admin' => $filterAdmin,
                            'date_from' => $filterDateFrom,
                            'date_to' => $filterDateTo
                        ])) ?>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</body>
</html>