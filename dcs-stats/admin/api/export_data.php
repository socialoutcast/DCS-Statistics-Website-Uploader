<?php
/**
 * Data Export API Endpoint
 */

require_once dirname(__DIR__) . '/auth.php';
require_once dirname(__DIR__) . '/admin_functions.php';

// Require admin login and permission
requireAdmin();
requirePermission('export_data');

// Verify CSRF token
if (!isset($_GET['csrf_token']) || !verifyCSRFToken($_GET['csrf_token'])) {
    http_response_code(403);
    die('Invalid security token');
}

// Get export parameters
$exportType = $_GET['type'] ?? '';
$format = $_GET['format'] ?? 'csv';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';

// Validate format
if (!in_array($format, EXPORT_FORMATS)) {
    http_response_code(400);
    die('Invalid export format');
}

// Prepare data based on export type
$data = [];
$filename = '';

switch ($exportType) {
    case 'players':
        $filename = 'players_export_' . date('Y-m-d');
        $players = getPlayers();
        
        foreach ($players as $player) {
            $stats = getPlayerStats($player['ucid']);
            $data[] = [
                'name' => $player['name'],
                'ucid' => $player['ucid'],
                'kills' => $stats['kills'],
                'deaths' => $stats['deaths'],
                'kd_ratio' => $stats['kd_ratio'],
                'sorties' => $stats['sorties'],
                'last_seen' => $stats['last_seen'],
                'is_banned' => isPlayerBanned($player['ucid']) ? 'Yes' : 'No'
            ];
        }
        break;
        
    case 'missions':
        $filename = 'missions_export_' . date('Y-m-d');
        if (!$dateFrom || !$dateTo) {
            http_response_code(400);
            die('Date range required for mission export');
        }
        
        $dataDir = dirname(dirname(__DIR__)) . '/data';
        $missionStatsFile = $dataDir . '/missionstats.json';
        
        if (file_exists($missionStatsFile)) {
            $handle = fopen($missionStatsFile, "r");
            if ($handle) {
                $count = 0;
                while (($line = fgets($handle)) !== false && $count < EXPORT_MAX_RECORDS) {
                    $entry = json_decode($line, true);
                    if (!$entry) continue;
                    
                    $time = $entry['time'] ?? '';
                    if ($time >= $dateFrom . ' 00:00:00' && $time <= $dateTo . ' 23:59:59') {
                        $data[] = [
                            'time' => $time,
                            'event' => $entry['event'] ?? '',
                            'init_id' => $entry['init_id'] ?? '',
                            'init_type' => $entry['init_type'] ?? '',
                            'target_id' => $entry['target_id'] ?? '',
                            'target_type' => $entry['target_type'] ?? '',
                            'weapon' => $entry['weapon'] ?? '',
                            'server' => $entry['server'] ?? ''
                        ];
                        $count++;
                    }
                }
                fclose($handle);
            }
        }
        break;
        
    case 'admin_logs':
        $filename = 'admin_logs_export_' . date('Y-m-d');
        if (!$dateFrom || !$dateTo) {
            http_response_code(400);
            die('Date range required for logs export');
        }
        
        $logs = json_decode(file_get_contents(ADMIN_LOGS_FILE), true) ?: [];
        $users = getAdminUsers();
        $userMap = [];
        foreach ($users as $user) {
            $userMap[$user['id']] = $user['username'];
        }
        
        foreach ($logs as $log) {
            $logDate = substr($log['created_at'], 0, 10);
            if ($logDate >= $dateFrom && $logDate <= $dateTo) {
                $data[] = [
                    'date' => $log['created_at'],
                    'admin' => $userMap[$log['admin_id']] ?? 'Unknown',
                    'action' => LOG_ACTIONS[$log['action']] ?? $log['action'],
                    'target_type' => $log['target_type'] ?? '',
                    'target_id' => $log['target_id'] ?? '',
                    'ip_address' => $log['ip_address'] ?? '',
                    'details' => is_array($log['details']) ? json_encode($log['details']) : $log['details']
                ];
            }
        }
        break;
        
    case 'full':
        if (getCurrentAdmin()['role'] != ROLE_SUPER_ADMIN) {
            http_response_code(403);
            die('Permission denied');
        }
        
        $filename = 'full_export_' . date('Y-m-d_H-i-s');
        
        if ($format === 'json') {
            // Export everything as JSON
            $data = [
                'export_date' => date('c'),
                'export_by' => getCurrentAdmin()['username'],
                'players' => getPlayers(),
                'bans' => getPlayerBans(false),
                'admin_users' => array_map(function($user) {
                    unset($user['password_hash']); // Don't export password hashes
                    return $user;
                }, getAdminUsers()),
                'admin_logs' => json_decode(file_get_contents(ADMIN_LOGS_FILE), true) ?: []
            ];
        } else {
            // For CSV, we'll just export players as it's the most useful
            $players = getPlayers();
            foreach ($players as $player) {
                $stats = getPlayerStats($player['ucid']);
                $data[] = array_merge($player, $stats);
            }
        }
        break;
        
    default:
        http_response_code(400);
        die('Invalid export type');
}

// Check if we have data
if (empty($data) && $exportType !== 'full') {
    http_response_code(404);
    die('No data found for the specified criteria');
}

// Export based on format
if ($format === 'csv') {
    exportToCSV($data, $filename . '.csv');
} else {
    exportToJSON($data, $filename . '.json');
}