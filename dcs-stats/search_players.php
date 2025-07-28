<?php
header('Content-Type: application/json');
ini_set('display_errors', 0);
error_reporting(0);

// Include security functions
require_once __DIR__ . '/security_functions.php';

// Rate limiting: 30 requests per minute
if (!checkRateLimit(30, 60)) {
    exit;
}

$searchTerm = validateInput($_GET['search'] ?? '', [
    'type' => 'player_name',
    'max_length' => 50,
    'min_length' => 1
]);

if ($searchTerm === false) {
    logSecurityEvent('INVALID_INPUT', 'Invalid search term: ' . substr($_GET['search'] ?? '', 0, 20));
    echo json_encode(["error" => "Invalid search term"]);
    exit;
}

$searchTerm = strtolower($searchTerm);

// Validate file path
$dataDir = __DIR__ . '/data';
$playersFile = validatePath($dataDir . '/players.json', $dataDir);

if (!$playersFile || !file_exists($playersFile)) {
    logSecurityEvent('DATA_UNAVAILABLE', 'Players file not found');
    echo json_encode(["error" => "Service temporarily unavailable"]);
    exit;
}

$matches = [];
$handle = fopen($playersFile, 'r');
if ($handle) {
    while (($line = fgets($handle)) !== false) {
        $entry = validateJsonLine($line, ['name', 'ucid']);
        if (!$entry) continue;
        
        // Partial match search
        if (stripos($entry['name'], $searchTerm) !== false) {
            $matches[] = [
                'name' => htmlspecialchars($entry['name'], ENT_QUOTES, 'UTF-8'),
                'ucid' => htmlspecialchars($entry['ucid'], ENT_QUOTES, 'UTF-8')
            ];
            
            // Limit results to prevent abuse
            if (count($matches) >= 20) {
                break;
            }
        }
    }
    fclose($handle);
}

// Sort matches by how well they match (exact matches first, then starts with, then contains)
usort($matches, function($a, $b) use ($searchTerm) {
    $aLower = strtolower($a['name']);
    $bLower = strtolower($b['name']);
    
    // Exact match
    if ($aLower === $searchTerm) return -1;
    if ($bLower === $searchTerm) return 1;
    
    // Starts with
    $aStarts = strpos($aLower, $searchTerm) === 0;
    $bStarts = strpos($bLower, $searchTerm) === 0;
    if ($aStarts && !$bStarts) return -1;
    if (!$aStarts && $bStarts) return 1;
    
    // Alphabetical
    return strcasecmp($a['name'], $b['name']);
});

echo json_encode([
    'results' => $matches,
    'count' => count($matches)
]);