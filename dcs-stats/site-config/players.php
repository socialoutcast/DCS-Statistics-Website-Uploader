<?php
/**
 * Player Management Interface
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/admin_functions.php';

// Require admin login and permission
requireAdmin();
requirePermission('manage_players');

// Get current admin
$currentAdmin = getCurrentAdmin();

// Handle actions
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $message = ERROR_MESSAGES['csrf_invalid'];
        $messageType = 'error';
    } else {
        switch ($_POST['action']) {
            case 'ban':
                if (hasPermission('ban_players')) {
                    $result = banPlayer(
                        $_POST['ucid'],
                        $_POST['player_name'],
                        $_POST['reason'] ?? null,
                        $_POST['expires_at'] ?? null
                    );
                    if ($result['success']) {
                        $message = SUCCESS_MESSAGES['player_banned'];
                        $messageType = 'success';
                    } else {
                        $message = $result['error'];
                        $messageType = 'error';
                    }
                }
                break;
                
            case 'unban':
                if (hasPermission('ban_players')) {
                    $result = unbanPlayer($_POST['ucid']);
                    if ($result['success']) {
                        $message = SUCCESS_MESSAGES['player_unbanned'];
                        $messageType = 'success';
                    } else {
                        $message = $result['error'];
                        $messageType = 'error';
                    }
                }
                break;
        }
    }
}

// Get search parameters
$search = $_GET['search'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = RECORDS_PER_PAGE;
$offset = ($page - 1) * $perPage;

// Get players
$allPlayers = getPlayers($search);
$totalPlayers = count($allPlayers);
$players = array_slice($allPlayers, $offset, $perPage);

// Get banned players
$bannedPlayers = getPlayerBans(true);
$bannedUcids = array_column($bannedPlayers, 'player_ucid');

// Page title
$pageTitle = 'Player Management';
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
                <?php if ($message): ?>
                    <div class="alert alert-<?= $messageType ?>">
                        <?= e($message) ?>
                    </div>
                <?php endif; ?>
                
                <!-- Search Box -->
                <div class="card">
                    <form method="GET" action="" class="search-box">
                        <input type="text" 
                               name="search" 
                               class="form-control" 
                               placeholder="Search by name or UCID..." 
                               value="<?= e($search) ?>">
                        <button type="submit" class="btn btn-primary">Search</button>
                        <?php if ($search): ?>
                            <a href="players.php" class="btn btn-secondary">Clear</a>
                        <?php endif; ?>
                    </form>
                </div>
                
                <!-- Players Table -->
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">
                            Players 
                            <?php if ($search): ?>
                                <span class="text-muted">(<?= $totalPlayers ?> results)</span>
                            <?php else: ?>
                                <span class="text-muted">(<?= $totalPlayers ?> total)</span>
                            <?php endif; ?>
                        </h2>
                    </div>
                    
                    <?php if (empty($players)): ?>
                        <p class="text-muted">No players found.</p>
                    <?php else: ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>UCID</th>
                                    <th>Status</th>
                                    <th>Last Seen</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($players as $player): ?>
                                    <?php 
                                    $ucid = $player['ucid'] ?? '';
                                    $isBanned = in_array($ucid, $bannedUcids);
                                    $stats = getPlayerStats($ucid);
                                    ?>
                                    <tr>
                                        <td>
                                            <strong><?= e($player['name'] ?? 'Unknown') ?></strong>
                                            <br>
                                            <small class="text-muted">
                                                Kills: <?= $stats['kills'] ?> | 
                                                Deaths: <?= $stats['deaths'] ?> | 
                                                K/D: <?= $stats['kd_ratio'] ?>
                                            </small>
                                        </td>
                                        <td>
                                            <code><?= e($ucid) ?></code>
                                        </td>
                                        <td>
                                            <?php if ($isBanned): ?>
                                                <span class="text-danger">🚫 Banned</span>
                                            <?php else: ?>
                                                <span class="text-success">✓ Active</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= formatDate($stats['last_seen']) ?></td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="player_details.php?ucid=<?= urlencode($ucid) ?>" 
                                                   class="btn btn-secondary btn-small">View</a>
                                                
                                                <?php if (hasPermission('ban_players')): ?>
                                                    <?php if ($isBanned): ?>
                                                        <form method="POST" action="" style="display: inline;">
                                                            <?= csrfField() ?>
                                                            <input type="hidden" name="action" value="unban">
                                                            <input type="hidden" name="ucid" value="<?= e($ucid) ?>">
                                                            <button type="submit" 
                                                                    class="btn btn-success btn-small"
                                                                    onclick="return confirm('Unban this player?')">
                                                                Unban
                                                            </button>
                                                        </form>
                                                    <?php else: ?>
                                                        <button type="button" 
                                                                class="btn btn-danger btn-small"
                                                                onclick="showBanModal('<?= e($ucid) ?>', '<?= e($player['name'] ?? '') ?>')">
                                                            Ban
                                                        </button>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        
                        <!-- Pagination -->
                        <?= getPagination($totalPlayers, $perPage, $page, 'players.php' . ($search ? '?search=' . urlencode($search) : '')) ?>
                    <?php endif; ?>
                </div>
                
                <!-- Banned Players -->
                <?php if (hasPermission('ban_players') && !empty($bannedPlayers)): ?>
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Banned Players (<?= count($bannedPlayers) ?>)</h2>
                    </div>
                    
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Player</th>
                                <th>Reason</th>
                                <th>Banned By</th>
                                <th>Banned On</th>
                                <th>Expires</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bannedPlayers as $ban): ?>
                                <tr>
                                    <td>
                                        <strong><?= e($ban['player_name']) ?></strong>
                                        <br>
                                        <code><?= e($ban['player_ucid']) ?></code>
                                    </td>
                                    <td><?= e($ban['reason'] ?? 'No reason provided') ?></td>
                                    <td>
                                        <?php
                                        $banAdmin = null;
                                        foreach (getAdminUsers() as $admin) {
                                            if ($admin['id'] == $ban['admin_id']) {
                                                $banAdmin = $admin;
                                                break;
                                            }
                                        }
                                        echo e($banAdmin['username'] ?? 'Unknown');
                                        ?>
                                    </td>
                                    <td><?= formatDate($ban['banned_at']) ?></td>
                                    <td>
                                        <?php if ($ban['expires_at']): ?>
                                            <?= formatDate($ban['expires_at']) ?>
                                        <?php else: ?>
                                            <span class="text-danger">Permanent</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <form method="POST" action="" style="display: inline;">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="action" value="unban">
                                            <input type="hidden" name="ucid" value="<?= e($ban['player_ucid']) ?>">
                                            <button type="submit" 
                                                    class="btn btn-success btn-small"
                                                    onclick="return confirm('Unban this player?')">
                                                Unban
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
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
            <form method="POST" action="">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="ban">
                <input type="hidden" name="ucid" id="banUcid">
                <input type="hidden" name="player_name" id="banPlayerName">
                
                <div class="form-group">
                    <label>Player</label>
                    <p id="banPlayerDisplay"></p>
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
        function showBanModal(ucid, playerName) {
            document.getElementById('banUcid').value = ucid;
            document.getElementById('banPlayerName').value = playerName;
            document.getElementById('banPlayerDisplay').textContent = playerName + ' (' + ucid + ')';
            document.getElementById('banModal').classList.add('active');
        }
        
        function closeBanModal() {
            document.getElementById('banModal').classList.remove('active');
            document.getElementById('banReason').value = '';
            document.getElementById('banExpires').value = '';
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