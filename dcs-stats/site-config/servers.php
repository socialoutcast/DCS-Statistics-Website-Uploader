<?php
/**
 * Server Statistics Overview
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/admin_functions.php';

// Require admin login and permission
requireAdmin();
requirePermission('manage_servers');

// Get current admin
$currentAdmin = getCurrentAdmin();

// Get server data from instances.json
$servers = [];
$dataDir = dirname(__DIR__) . '/data';
$instancesFile = $dataDir . '/instances.json';

if (file_exists($instancesFile)) {
    $content = file_get_contents($instancesFile);
    $data = json_decode($content, true);
    if ($data && is_array($data)) {
        $servers = $data;
    }
}

// Get mission statistics for each server
$serverStats = [];
$missionStatsFile = $dataDir . '/missionstats.json';

if (file_exists($missionStatsFile)) {
    $handle = fopen($missionStatsFile, "r");
    if ($handle) {
        $now = time();
        $day_ago = $now - 86400;
        $week_ago = $now - 604800;
        
        while (($line = fgets($handle)) !== false) {
            $entry = json_decode($line, true);
            if (!$entry) continue;
            
            $server = $entry['server'] ?? 'Unknown';
            $time = strtotime($entry['time'] ?? '');
            
            if (!isset($serverStats[$server])) {
                $serverStats[$server] = [
                    'total_events' => 0,
                    'events_24h' => 0,
                    'events_7d' => 0,
                    'unique_players' => [],
                    'unique_players_24h' => [],
                    'kills' => 0,
                    'deaths' => 0,
                    'takeoffs' => 0,
                    'last_activity' => null
                ];
            }
            
            $serverStats[$server]['total_events']++;
            
            if ($time > $day_ago) {
                $serverStats[$server]['events_24h']++;
                $serverStats[$server]['unique_players_24h'][$entry['init_id']] = true;
            }
            
            if ($time > $week_ago) {
                $serverStats[$server]['events_7d']++;
            }
            
            $serverStats[$server]['unique_players'][$entry['init_id']] = true;
            
            switch ($entry['event'] ?? '') {
                case 'S_EVENT_HIT':
                    $serverStats[$server]['kills']++;
                    break;
                case 'S_EVENT_DEAD':
                    $serverStats[$server]['deaths']++;
                    break;
                case 'S_EVENT_TAKEOFF':
                    $serverStats[$server]['takeoffs']++;
                    break;
            }
            
            if (!$serverStats[$server]['last_activity'] || $time > strtotime($serverStats[$server]['last_activity'])) {
                $serverStats[$server]['last_activity'] = $entry['time'];
            }
        }
        fclose($handle);
    }
}

// Convert unique player arrays to counts
foreach ($serverStats as &$stats) {
    $stats['unique_players'] = count($stats['unique_players']);
    $stats['unique_players_24h'] = count($stats['unique_players_24h']);
}

// Page title
$pageTitle = 'Server Statistics';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - Carrier Air Wing Command</title>
    <link rel="stylesheet" href="css/admin.css">
    <style>
        .server-card {
            background-color: var(--bg-tertiary);
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .server-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .server-name {
            font-size: 20px;
            font-weight: bold;
        }
        
        .server-status {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .status-online {
            background-color: rgba(76, 175, 80, 0.2);
            color: var(--accent-primary);
        }
        
        .status-offline {
            background-color: rgba(244, 67, 54, 0.2);
            color: var(--accent-danger);
        }
        
        .server-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        
        .server-stat {
            text-align: center;
        }
        
        .server-stat-value {
            font-size: 24px;
            font-weight: bold;
            color: var(--accent-primary);
        }
        
        .server-stat-label {
            font-size: 12px;
            color: var(--text-muted);
            margin-top: 5px;
        }
        
        .overview-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
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
                <!-- Overview Stats -->
                <div class="overview-grid">
                    <div class="stat-card">
                        <div class="stat-label">Total Servers</div>
                        <div class="stat-value"><?= count($servers) ?></div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">Online Servers</div>
                        <div class="stat-value">
                            <?php
                            $onlineCount = 0;
                            foreach ($servers as $server) {
                                if ($server['status'] === 'online') $onlineCount++;
                            }
                            echo $onlineCount;
                            ?>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">Total Events (24h)</div>
                        <div class="stat-value">
                            <?php
                            $totalEvents24h = 0;
                            foreach ($serverStats as $stats) {
                                $totalEvents24h += $stats['events_24h'];
                            }
                            echo number_format($totalEvents24h);
                            ?>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">Active Players (24h)</div>
                        <div class="stat-value">
                            <?php
                            $activePlayers = [];
                            foreach ($serverStats as $stats) {
                                foreach (array_keys($stats['unique_players_24h']) as $player) {
                                    $activePlayers[$player] = true;
                                }
                            }
                            echo count($activePlayers);
                            ?>
                        </div>
                    </div>
                </div>
                
                <!-- Server List -->
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">DCS Servers</h2>
                    </div>
                    
                    <?php if (empty($servers)): ?>
                        <p class="text-muted">No server data available. Make sure instances.json is being uploaded.</p>
                    <?php else: ?>
                        <?php foreach ($servers as $server): ?>
                            <?php
                            $serverName = $server['server_name'] ?? 'Unknown Server';
                            $serverKey = $server['instance'] ?? $serverName;
                            $stats = $serverStats[$serverKey] ?? [
                                'total_events' => 0,
                                'events_24h' => 0,
                                'unique_players' => 0,
                                'unique_players_24h' => 0,
                                'kills' => 0,
                                'deaths' => 0,
                                'takeoffs' => 0,
                                'last_activity' => null
                            ];
                            $isOnline = ($server['status'] ?? 'offline') === 'online';
                            ?>
                            <div class="server-card">
                                <div class="server-header">
                                    <div>
                                        <div class="server-name"><?= e($serverName) ?></div>
                                        <div class="text-muted" style="font-size: 14px;">
                                            <?= e($server['mission_name'] ?? 'No mission') ?>
                                        </div>
                                    </div>
                                    <div>
                                        <span class="server-status <?= $isOnline ? 'status-online' : 'status-offline' ?>">
                                            <?= $isOnline ? 'ONLINE' : 'OFFLINE' ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="server-stats">
                                    <div class="server-stat">
                                        <div class="server-stat-value"><?= $server['players'] ?? 0 ?>/<?= $server['max_players'] ?? 0 ?></div>
                                        <div class="server-stat-label">Players Online</div>
                                    </div>
                                    <div class="server-stat">
                                        <div class="server-stat-value"><?= number_format($stats['events_24h']) ?></div>
                                        <div class="server-stat-label">Events (24h)</div>
                                    </div>
                                    <div class="server-stat">
                                        <div class="server-stat-value"><?= number_format($stats['unique_players_24h']) ?></div>
                                        <div class="server-stat-label">Unique Players (24h)</div>
                                    </div>
                                    <div class="server-stat">
                                        <div class="server-stat-value"><?= number_format($stats['kills']) ?></div>
                                        <div class="server-stat-label">Total Kills</div>
                                    </div>
                                    <div class="server-stat">
                                        <div class="server-stat-value"><?= number_format($stats['takeoffs']) ?></div>
                                        <div class="server-stat-label">Total Sorties</div>
                                    </div>
                                </div>
                                
                                <div style="margin-top: 15px; font-size: 12px; color: var(--text-muted);">
                                    <strong>Server Details:</strong><br>
                                    Map: <?= e($server['mission_theatre'] ?? 'Unknown') ?><br>
                                    Uptime: <?= e($server['uptime'] ?? 'Unknown') ?><br>
                                    Last Activity: <?= formatDate($stats['last_activity']) ?><br>
                                    <?php if (!empty($server['dcs_version'])): ?>
                                        DCS Version: <?= e($server['dcs_version']) ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <!-- Server Activity Chart (placeholder) -->
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Server Activity (Last 7 Days)</h2>
                    </div>
                    <p class="text-muted">
                        Chart visualization would go here. Consider integrating Chart.js or a similar library
                        to display server activity trends over time.
                    </p>
                </div>
            </div>
        </main>
    </div>
</body>
</html>