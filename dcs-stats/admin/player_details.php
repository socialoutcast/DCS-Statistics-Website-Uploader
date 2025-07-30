<?php
/**
 * Player Details Page
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/admin_functions.php';

// Require admin login and permission
requireAdmin();
requirePermission('manage_players');

// Get player UCID
$ucid = $_GET['ucid'] ?? '';
if (!$ucid) {
    header('Location: players.php');
    exit;
}

// Get current admin
$currentAdmin = getCurrentAdmin();

// Find player
$player = null;
$players = getPlayers();
foreach ($players as $p) {
    if ($p['ucid'] === $ucid) {
        $player = $p;
        break;
    }
}

if (!$player) {
    header('Location: players.php?error=player_not_found');
    exit;
}

// Get player statistics
$stats = getPlayerStats($ucid);

// Check if banned
$isBanned = isPlayerBanned($ucid);
$banInfo = null;
if ($isBanned) {
    $bans = getPlayerBans(true);
    foreach ($bans as $ban) {
        if ($ban['player_ucid'] === $ucid) {
            $banInfo = $ban;
            break;
        }
    }
}

// Log view action
logAdminActivity('PLAYER_VIEW', $_SESSION['admin_id'], 'player', $ucid);

// Get player's recent activity from mission stats
$recentActivity = [];
$dataDir = dirname(__DIR__) . '/data';
$missionStatsFile = $dataDir . '/missionstats.json';

if (file_exists($missionStatsFile)) {
    $handle = fopen($missionStatsFile, "r");
    if ($handle) {
        $events = [];
        while (($line = fgets($handle)) !== false) {
            $entry = json_decode($line, true);
            if (!$entry || $entry['init_id'] !== $ucid) continue;
            $events[] = $entry;
        }
        fclose($handle);
        
        // Sort by time descending and take last 20
        usort($events, function($a, $b) {
            return strtotime($b['time']) - strtotime($a['time']);
        });
        $recentActivity = array_slice($events, 0, 20);
    }
}

// Page title
$pageTitle = 'Player Details: ' . $player['name'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> - Carrier Air Wing Command</title>
    <link rel="stylesheet" href="css/admin.css">
    <style>
        .player-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .player-info h1 {
            margin-bottom: 10px;
        }
        
        .player-ucid {
            font-family: monospace;
            color: var(--text-muted);
            font-size: 14px;
        }
        
        .stat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .event-icon {
            display: inline-block;
            width: 20px;
            text-align: center;
            margin-right: 5px;
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
                <h1>Player Details</h1>
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
                <!-- Back Button -->
                <a href="players.php" class="btn btn-secondary mb-3">← Back to Players</a>
                
                <!-- Player Header -->
                <div class="player-header">
                    <div class="player-info">
                        <h1><?= e($player['name']) ?></h1>
                        <div class="player-ucid">UCID: <?= e($ucid) ?></div>
                    </div>
                    <div>
                        <?php if ($isBanned): ?>
                            <span class="text-danger" style="font-size: 24px;">🚫 BANNED</span>
                        <?php else: ?>
                            <span class="text-success" style="font-size: 24px;">✓ ACTIVE</span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Ban Information -->
                <?php if ($isBanned && $banInfo): ?>
                <div class="alert alert-error">
                    <strong>Player is banned</strong><br>
                    Reason: <?= e($banInfo['reason'] ?? 'No reason provided') ?><br>
                    Banned on: <?= formatDate($banInfo['banned_at']) ?><br>
                    <?php if ($banInfo['expires_at']): ?>
                        Expires: <?= formatDate($banInfo['expires_at']) ?>
                    <?php else: ?>
                        Duration: Permanent
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <!-- Statistics -->
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Statistics</h2>
                    </div>
                    <div class="stat-grid">
                        <div class="stat-card">
                            <div class="stat-label">Kills</div>
                            <div class="stat-value"><?= number_format($stats['kills']) ?></div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-label">Deaths</div>
                            <div class="stat-value"><?= number_format($stats['deaths']) ?></div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-label">K/D Ratio</div>
                            <div class="stat-value"><?= $stats['kd_ratio'] ?></div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-label">Sorties</div>
                            <div class="stat-value"><?= number_format($stats['sorties']) ?></div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-label">Last Seen</div>
                            <div class="stat-value" style="font-size: 16px;"><?= formatDate($stats['last_seen']) ?></div>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Activity -->
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Recent Activity</h2>
                    </div>
                    
                    <?php if (empty($recentActivity)): ?>
                        <p class="text-muted">No recent activity found.</p>
                    <?php else: ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Time</th>
                                    <th>Event</th>
                                    <th>Aircraft</th>
                                    <th>Target</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentActivity as $event): ?>
                                    <tr>
                                        <td><?= formatDate($event['time']) ?></td>
                                        <td>
                                            <?php
                                            $eventIcons = [
                                                'S_EVENT_HIT' => '💥',
                                                'S_EVENT_TAKEOFF' => '🛫',
                                                'S_EVENT_LAND' => '🛬',
                                                'S_EVENT_CRASH' => '💥',
                                                'S_EVENT_EJECTION' => '🪂',
                                                'S_EVENT_DEAD' => '☠️'
                                            ];
                                            $eventNames = [
                                                'S_EVENT_HIT' => 'Kill',
                                                'S_EVENT_TAKEOFF' => 'Takeoff',
                                                'S_EVENT_LAND' => 'Landing',
                                                'S_EVENT_CRASH' => 'Crash',
                                                'S_EVENT_EJECTION' => 'Ejection',
                                                'S_EVENT_DEAD' => 'Death'
                                            ];
                                            $eventType = $event['event'] ?? 'Unknown';
                                            ?>
                                            <span class="event-icon"><?= $eventIcons[$eventType] ?? '❓' ?></span>
                                            <?= e($eventNames[$eventType] ?? $eventType) ?>
                                        </td>
                                        <td><?= e($event['init_type'] ?? 'Unknown') ?></td>
                                        <td>
                                            <?php if (!empty($event['target_type'])): ?>
                                                <?= e($event['target_type']) ?>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
                
                <!-- Actions -->
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Actions</h2>
                    </div>
                    <div class="btn-group">
                        <?php if (hasPermission('ban_players')): ?>
                            <?php if ($isBanned): ?>
                                <form method="POST" action="players.php" style="display: inline;">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action" value="unban">
                                    <input type="hidden" name="ucid" value="<?= e($ucid) ?>">
                                    <button type="submit" 
                                            class="btn btn-success"
                                            onclick="return confirm('Unban this player?')">
                                        Unban Player
                                    </button>
                                </form>
                            <?php else: ?>
                                <button type="button" 
                                        class="btn btn-danger"
                                        onclick="showBanModal()">
                                    Ban Player
                                </button>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <?php if (hasPermission('export_data')): ?>
                            <a href="api/export_player.php?ucid=<?= urlencode($ucid) ?>" 
                               class="btn btn-secondary">
                                Export Data
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Ban Modal -->
    <div id="banModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Ban Player</h2>
                <button type="button" class="modal-close" onclick="closeBanModal()">&times;</button>
            </div>
            <form method="POST" action="players.php">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="ban">
                <input type="hidden" name="ucid" value="<?= e($ucid) ?>">
                <input type="hidden" name="player_name" value="<?= e($player['name']) ?>">
                
                <div class="form-group">
                    <label>Player</label>
                    <p><?= e($player['name']) ?> (<?= e($ucid) ?>)</p>
                </div>
                
                <div class="form-group">
                    <label for="banReason">Reason</label>
                    <textarea name="reason" 
                              id="banReason" 
                              class="form-control" 
                              placeholder="Enter ban reason..."></textarea>
                </div>
                
                <div class="form-group">
                    <label for="banExpires">Expires (leave empty for permanent)</label>
                    <input type="datetime-local" 
                           name="expires_at" 
                           id="banExpires" 
                           class="form-control">
                </div>
                
                <div class="btn-group">
                    <button type="submit" class="btn btn-danger">Ban Player</button>
                    <button type="button" class="btn btn-secondary" onclick="closeBanModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function showBanModal() {
            document.getElementById('banModal').classList.add('active');
        }
        
        function closeBanModal() {
            document.getElementById('banModal').classList.remove('active');
        }
        
        // Close modal on outside click
        document.getElementById('banModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeBanModal();
            }
        });
    </script>
</body>
</html>