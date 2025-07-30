<?php
header('Content-Type: application/json');
ini_set('display_errors', 0);
error_reporting(0);

// Include security functions
require_once __DIR__ . '/security_functions.php';

// Rate limiting
if (!checkRateLimit(60, 60)) {
    exit;
}

try {
    // Try to read instances.json file
    $instancesFile = __DIR__ . '/data/instances.json';
    
    if (!file_exists($instancesFile)) {
        echo json_encode([
            'servers' => [],
            'source' => 'json',
            'message' => 'No server data available'
        ]);
        exit;
    }
    
    // Read and parse instances data
    $fileContent = file_get_contents($instancesFile);
    $lines = explode("\n", trim($fileContent));
    $servers = [];
    
    foreach ($lines as $line) {
        if (empty($line)) continue;
        
        $instance = json_decode($line, true);
        if ($instance) {
            $servers[] = [
                'name' => $instance['server_name'] ?? $instance['name'] ?? 'Unknown Server',
                'status' => $instance['status'] ?? 'offline',
                'players' => $instance['players'] ?? 0,
                'max_players' => $instance['max_players'] ?? 0,
                'map' => $instance['map'] ?? $instance['theater'] ?? 'N/A',
                'mission' => $instance['mission_name'] ?? $instance['mission'] ?? 'N/A',
                'uptime' => isset($instance['uptime']) ? formatUptime($instance['uptime']) : 'N/A',
                'version' => $instance['version'] ?? 'N/A',
                'port' => $instance['port'] ?? 'N/A'
            ];
        }
    }
    
    echo json_encode([
        'servers' => $servers,
        'source' => 'json',
        'count' => count($servers),
        'generated' => date('c')
    ]);
    
} catch (Exception $e) {
    logSecurityEvent('SERVER_DATA_ERROR', 'Error reading server data: ' . $e->getMessage());
    echo json_encode([
        'error' => 'Failed to load server data',
        'servers' => [],
        'source' => 'json'
    ]);
}

// Helper function to format uptime
function formatUptime($seconds) {
    if (!is_numeric($seconds)) return 'N/A';
    
    $days = floor($seconds / 86400);
    $hours = floor(($seconds % 86400) / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    
    $parts = [];
    if ($days > 0) $parts[] = $days . 'd';
    if ($hours > 0) $parts[] = $hours . 'h';
    if ($minutes > 0) $parts[] = $minutes . 'm';
    
    return empty($parts) ? '< 1m' : implode(' ', $parts);
}