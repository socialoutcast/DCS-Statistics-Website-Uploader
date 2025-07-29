<?php
header('Content-Type: application/json');
ini_set('display_errors', 0);
error_reporting(0);

// Include security functions and API client
require_once __DIR__ . '/security_functions.php';
require_once __DIR__ . '/api_client.php';

// Rate limiting
if (!checkRateLimit(60, 60)) {
    exit;
}

// Validate search query - frontend uses 'search' parameter
$query = validateInput($_GET['search'] ?? $_GET['q'] ?? '', [
    'type' => 'search_query',
    'max_length' => 50,
    'min_length' => 2
]);

if ($query === false) {
    echo json_encode(["error" => "Invalid search query"]);
    exit;
}

try {
    $useAPI = true;
    $players = [];
    
    if ($useAPI) {
        try {
            // Use /getuser endpoint to search for players
            // This returns an array of players matching the nickname
            $apiResponse = $apiClient->makeRequest('POST', '/getuser', ['nick' => $query]);
            
            if (is_array($apiResponse)) {
                foreach ($apiResponse as $player) {
                    $players[] = [
                        'ucid' => null, // API doesn't provide UCID
                        'name' => htmlspecialchars($player['name'] ?? '', ENT_QUOTES, 'UTF-8'),
                        'last_seen' => $player['last_seen'] ?? null
                    ];
                }
            }
            
        } catch (Exception $apiError) {
            // Fall back to JSON
            logSecurityEvent('API_ERROR', 'Search API failed: ' . $apiError->getMessage());
            $useAPI = false;
        }
    }
    
    // Fallback to JSON search if API fails
    if (!$useAPI) {
        $dataDir = __DIR__ . '/data';
        $playersFile = validatePath($dataDir . '/players.json', $dataDir);
        
        if ($playersFile && file_exists($playersFile)) {
            $queryLower = strtolower($query);
            $handle = fopen($playersFile, "r");
            
            while (($line = fgets($handle)) !== false) {
                $entry = json_decode(trim($line), true);
                if (!$entry) continue;
                
                $nameLower = strtolower($entry['name'] ?? '');
                if (strpos($nameLower, $queryLower) !== false) {
                    $players[] = [
                        'ucid' => $entry['ucid'] ?? null,
                        'name' => htmlspecialchars($entry['name'] ?? '', ENT_QUOTES, 'UTF-8'),
                        'last_seen' => $entry['last_seen'] ?? null
                    ];
                    
                    // Limit results
                    if (count($players) >= 50) break;
                }
            }
            fclose($handle);
        }
    }
    
    // Sort by name
    usort($players, function($a, $b) {
        return strcasecmp($a['name'], $b['name']);
    });
    
    // Return response with metadata
    echo json_encode([
        'source' => $useAPI ? 'api' : 'json',
        'query' => $query,
        'count' => count($players),
        'players' => $players
    ]);
    
} catch (Exception $e) {
    logSecurityEvent('GENERAL_ERROR', 'Search error: ' . $e->getMessage());
    echo json_encode([
        'error' => 'Search temporarily unavailable',
        'query' => $query
    ]);
}