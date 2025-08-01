<?php
header('Content-Type: application/json');
ini_set('display_errors', 0);
error_reporting(0);

// Include required files
require_once __DIR__ . '/security_functions.php';
require_once __DIR__ . '/api_client.php';

// Rate limiting
if (!checkRateLimit(60, 60)) {
    exit;
}

// Load API configuration
$configFile = __DIR__ . '/api_config.json';
$config = file_exists($configFile) ? json_decode(file_get_contents($configFile), true) : [];

// Check if API is enabled
if (!$config || !$config['use_api']) {
    echo json_encode([
        'error' => 'API not configured',
        'totalPlayers' => 0,
        'totalKills' => 0,
        'totalDeaths' => 0,
        'top5Pilots' => [],
        'top3Squadrons' => []
    ]);
    exit;
}

try {
    // Initialize API client
    $apiClient = new DCSServerBotAPIClient($config);
    
    // Get top kills data (this gives us the top players with their stats)
    $topKills = $apiClient->getTopKills();
    
    // Calculate totals from the data we have
    $totalKills = 0;
    $totalDeaths = 0;
    $pilotStats = [];
    
    if (is_array($topKills)) {
        foreach ($topKills as $pilot) {
            $totalKills += $pilot['AAkills'] ?? 0;
            $totalDeaths += $pilot['deaths'] ?? 0;
            
            // Store pilot data for top 5
            $pilotStats[] = [
                'name' => $pilot['fullNickname'] ?? 'Unknown',
                'kills' => $pilot['AAkills'] ?? 0,
                'deaths' => $pilot['deaths'] ?? 0,
                'kdr' => $pilot['AAKDR'] ?? 0,
                'visits' => $pilot['AAkills'] ?? 0 // Using kills as a proxy for activity
            ];
        }
    }
    
    // Sort by kills (visits) and get top 5
    usort($pilotStats, function($a, $b) {
        return $b['visits'] - $a['visits'];
    });
    $top5Pilots = array_slice($pilotStats, 0, 5);
    
    // Since the API doesn't provide squadron data, we'll return empty for now
    $top3Squadrons = [];
    
    // Count unique players (from the data we have)
    $totalPlayers = count($pilotStats);
    
    // Return the data in the expected format
    echo json_encode([
        'totalPlayers' => $totalPlayers,
        'totalKills' => $totalKills,
        'totalDeaths' => $totalDeaths,
        'top5Pilots' => $top5Pilots,
        'top3Squadrons' => $top3Squadrons,
        'source' => 'api'
    ]);
    
} catch (Exception $e) {
    
    // Return error response
    echo json_encode([
        'error' => 'Service temporarily unavailable',
        'totalPlayers' => 0,
        'totalKills' => 0,
        'totalDeaths' => 0,
        'top5Pilots' => [],
        'top3Squadrons' => []
    ]);
}