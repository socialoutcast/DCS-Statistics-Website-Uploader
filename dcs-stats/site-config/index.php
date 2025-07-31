<?php
/**
 * Admin Dashboard
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/admin_functions.php';

// Require admin login
requireAdmin();
requirePermission('view_dashboard');

// Get current admin
$currentAdmin = getCurrentAdmin();

// Get dashboard statistics
$stats = getDashboardStats();

// Page title
$pageTitle = 'Flight Deck Operations';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - Carrier Air Wing Command</title>
    <link rel="stylesheet" href="css/admin.css">
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
                <!-- Welcome Message -->
                <div class="alert alert-info">
                    Welcome aboard, <?= e($currentAdmin['username']) ?>! 
                    Last watch: <?= formatDate($currentAdmin['last_login']) ?>
                </div>
                
                <!-- Statistics Grid -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-label">Bridge Officers</div>
                        <div class="stat-value"><?= number_format($stats['total_admins']) ?></div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">Active Pilots</div>
                        <div class="stat-value"><?= number_format($stats['total_players']) ?></div>
                    </div>
                </div>
                
                <!-- Recent Activity -->
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Bridge Log</h2>
                        <a href="logs.php" class="btn btn-primary btn-small">View All</a>
                    </div>
                    
                    <?php if (empty($stats['recent_activity'])): ?>
                        <p class="text-muted">No recent activity to display.</p>
                    <?php else: ?>
                        <div class="activity-list">
                            <?php foreach ($stats['recent_activity'] as $activity): ?>
                                <div class="activity-item">
                                    <div class="activity-time"><?= formatDate($activity['created_at']) ?></div>
                                    <div class="activity-action">
                                        <strong><?= e($activity['admin_username']) ?></strong>
                                        <?= e(LOG_ACTIONS[$activity['action']] ?? $activity['action']) ?>
                                        <?php if ($activity['target_type']): ?>
                                            <span class="text-muted">(<?= e($activity['target_type']) ?>: <?= e($activity['target_id']) ?>)</span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($activity['details']): ?>
                                        <div class="activity-details">
                                            <?= e(is_array($activity['details']) ? json_encode($activity['details']) : $activity['details']) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Quick Actions -->
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Quick Actions</h2>
                    </div>
                    <div class="btn-group">
                        <?php if (hasPermission('manage_admins')): ?>
                            <a href="admins.php" class="btn btn-primary">Manage Admins</a>
                        <?php endif; ?>
                        <?php if (hasPermission('view_logs')): ?>
                            <a href="logs.php" class="btn btn-secondary">View Logs</a>
                        <?php endif; ?>
                        <?php if (hasPermission('export_data')): ?>
                            <a href="export.php" class="btn btn-secondary">Export Data</a>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- System Information -->
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">System Information</h2>
                    </div>
                    <table class="data-table">
                        <tr>
                            <td>Admin Panel Version</td>
                            <td><?= ADMIN_PANEL_VERSION ?></td>
                        </tr>
                        <tr>
                            <td>PHP Version</td>
                            <td><?= PHP_VERSION ?></td>
                        </tr>
                        <tr>
                            <td>Storage Mode</td>
                            <td><?= USE_DATABASE ? 'Database' : 'JSON Files' ?></td>
                        </tr>
                        <tr>
                            <td>Data Directory</td>
                            <td><?= ADMIN_DATA_DIR ?></td>
                        </tr>
                        <tr>
                            <td>Log Retention</td>
                            <td><?= LOG_RETENTION_DAYS ?> days</td>
                        </tr>
                    </table>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        // Auto-refresh activity every 30 seconds
        setInterval(() => {
            // In a real implementation, this would fetch new activity via AJAX
        }, 30000);
    </script>
</body>
</html>