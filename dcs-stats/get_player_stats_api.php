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
            'nick' => htmlspecialchars($playerName, ENT_QUOTES, 'UTF-8'),
            'kills' => $apiStats['kills'] ?? 0,
            'deaths' => $apiStats['deaths'] ?? 0,
            'kdr' => $apiStats['kdr'] ?? 0,
            'kills_pvp' => $apiStats['kills'] ?? 0,
            'deaths_pvp' => $apiStats['deaths'] ?? 0,
            'kdr_pvp' => $apiStats['kdr'] ?? 0,
            'teamkills' => $apiStats['teamkills'] ?? 0,
            'takeoffs' => $apiStats['takeoffs'] ?? 0,
            'landings' => $apiStats['landings'] ?? 0,
            'crashes' => $apiStats['crashes'] ?? 0,
            'ejections' => $apiStats['ejections'] ?? 0,
            'playtime' => $apiStats['playtime'] ?? 0,
            'sorties' => $apiStats['sorties'] ?? 0,
            'lastSessionKills' => $apiStats['lastSessionKills'] ?? 0,
            'lastSessionDeaths' => $apiStats['lastSessionDeaths'] ?? 0,
            'killsByModule' => $apiStats['killsByModule'] ?? [],
            'kdrByModule' => $apiStats['kdrByModule'] ?? []
        ];
        
        // Find most used aircraft from killsbymodule
        if (!empty($stats['killsByModule'])) {
            $mostUsed = array_keys($stats['killsByModule'], max($stats['killsByModule']));
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