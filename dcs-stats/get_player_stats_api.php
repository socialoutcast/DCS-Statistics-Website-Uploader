<?php
header('Content-Type: application/json');
ini_set('display_errors', 0);
error_reporting(0);

// Include security functions and API client
require_once __DIR__ . '/security_functions.php';
require_once __DIR__ . '/api_client_enhanced.php';

// Rate limiting: 30 requests per minute (lower for search endpoint)
if (!checkRateLimit(30, 60)) {
    exit;
}

$playerName = validateInput($_GET['name'] ?? '', [
    'type' => 'player_name',
    'max_length' => 50,
    'min_length' => 1
]);

if ($playerName === false) {
    logSecurityEvent('INVALID_INPUT', 'Invalid player name format: ' . substr($_GET['name'] ?? '', 0, 20));
    echo json_encode(["error" => "Invalid player name format"]);
    exit;
}

// Validate name
if (!$playerName) {
    logSecurityEvent('INVALID_INPUT', 'Empty player name provided');
    echo json_encode(["error" => "Invalid request"]);
    exit;
}

try {
    // Create API client
    $apiClient = createEnhancedAPIClient();
    
    // Get player stats from API
    // API /stats returns: deaths, aakills, aakdr, lastSessionKills, lastSessionDeaths, killsbymodule, kdrByModule
    $apiStats = $apiClient->getPlayerStats($playerName);
    
    if ($apiStats && is_array($apiStats)) {
        // Transform API response to our format
        $stats = [
            'ucid' => $apiStats['ucid'] ?? 'unknown',
            'name' => htmlspecialchars($playerName, ENT_QUOTES, 'UTF-8'),
            'kills' => $apiStats['aakills'] ?? 0,
            'deaths' => $apiStats['deaths'] ?? 0,
            'kd_ratio' => $apiStats['aakdr'] ?? 0,
            'teamkills' => $apiStats['teamkills'] ?? 0,
            'lastSessionKills' => $apiStats['lastSessionKills'] ?? 0,
            'lastSessionDeaths' => $apiStats['lastSessionDeaths'] ?? 0,
            'killsbymodule' => $apiStats['killsbymodule'] ?? [],
            'kdrByModule' => $apiStats['kdrByModule'] ?? []
        ];
        
        // Add calculated fields for compatibility
        $stats['takeoffs'] = $apiStats['takeoffs'] ?? 0;
        $stats['landings'] = $apiStats['landings'] ?? 0;
        $stats['crashes'] = $apiStats['crashes'] ?? 0;
        $stats['ejections'] = $apiStats['ejections'] ?? 0;
        $stats['flight_hours'] = $apiStats['flight_hours'] ?? 0;
        $stats['sorties'] = $apiStats['sorties'] ?? 0;
        
        // Find most used aircraft from killsbymodule
        if (!empty($stats['killsbymodule'])) {
            $mostUsed = array_keys($stats['killsbymodule'], max($stats['killsbymodule']));
            $stats['most_used_aircraft'] = $mostUsed[0] ?? "Unknown";
        } else {
            $stats['most_used_aircraft'] = "Unknown";
        }
        
        // Add metadata to response
        $response = [
            'source' => 'api',
            'timestamp' => date('c'),
            'data' => $stats
        ];
        
        echo json_encode($response);
    } else {
        // Player not found
        echo json_encode([
            'error' => 'Player not found',
            'source' => 'api',
            'timestamp' => date('c')
        ]);
    }
    
} catch (Exception $e) {
    // Generic error response
    logSecurityEvent('API_ERROR', 'Player stats API error: ' . $e->getMessage());
    echo json_encode([
        'error' => 'Service temporarily unavailable',
        'message' => 'Unable to retrieve player statistics',
        'source' => 'api',
        'timestamp' => date('c')
    ]);
}