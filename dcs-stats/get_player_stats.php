<?php
header('Content-Type: application/json');
ini_set('display_errors', 0);
error_reporting(0);

// Include security functions
require_once __DIR__ . '/security_functions.php';

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

$playerName = strtolower($playerName);

// Validate file paths to prevent path traversal
$dataDir = __DIR__ . '/data';
$playersFile = validatePath($dataDir . '/players.json', $dataDir);
$missionsFile = validatePath($dataDir . '/missionstats.json', $dataDir);

if (!$playersFile || !$missionsFile) {
    logSecurityEvent('PATH_TRAVERSAL_ATTEMPT', 'Invalid file path access attempt');
    echo json_encode(["error" => "Service temporarily unavailable"]);
    exit;
}

// Validate name
if (!$playerName) {
    logSecurityEvent('INVALID_INPUT', 'Empty player name provided');
    echo json_encode(["error" => "Invalid request"]);
    exit;
}

// Validate files (generic error message to prevent information disclosure)
if (!file_exists($playersFile) || !file_exists($missionsFile)) {
    logSecurityEvent('DATA_UNAVAILABLE', 'Required data files not found');
    echo json_encode(["error" => "Service temporarily unavailable"]);
    exit;
}

// Search for UCID in players.json (line-by-line NDJSON)
$ucid = null;
$exactName = null;
$handle = fopen($playersFile, 'r');
if ($handle) {
    while (($line = fgets($handle)) !== false) {
        $entry = validateJsonLine($line, ['name', 'ucid']);
        if (!$entry) continue;
        if (trim(strtolower($entry['name'])) === $playerName) {
            $ucid = $entry['ucid'];
            $exactName = $entry['name'];
            break;
        }
    }
    fclose($handle);
}

if (!$ucid) {
    logSecurityEvent('PLAYER_NOT_FOUND', 'Player lookup failed: ' . substr($playerName, 0, 20));
    echo json_encode(["error" => "Player not found"]);
    exit;
}

// Initialize stats
$kills = 0;
$sorties = 0;
$takeoffs = 0;
$landings = 0;
$crashes = 0;
$ejections = 0;
$traps = 0;
$aircraftUsage = [];

// Parse missionstats.json
$handle = fopen($missionsFile, 'r');
if ($handle) {
    while (($line = fgets($handle)) !== false) {
        $entry = validateJsonLine($line, ['event']);
        if (!$entry) continue;

        if ($entry['init_id'] === $ucid) {
            switch ($entry['event']) {
                case "S_EVENT_HIT": $kills++; break;
                case "S_EVENT_TAKEOFF": $takeoffs++; break;
                case "S_EVENT_LAND": $landings++; break;
                case "S_EVENT_CRASH": $crashes++; break;
                case "S_EVENT_EJECTION": $ejections++; break;
            }

            if (!empty($entry['init_type'])) {
                $ac = $entry['init_type'];
                $aircraftUsage[$ac] = ($aircraftUsage[$ac] ?? 0) + 1;
            }
        }

        if ($entry['event'] === "S_EVENT_MISSION_START" && $entry['init_id'] === $ucid) {
            $sorties++;
        }
    }
    fclose($handle);
}

arsort($aircraftUsage);
$mostUsedAircraft = key($aircraftUsage);

// Get traps from traps.json
$trapsFile = validatePath($dataDir . '/traps.json', $dataDir);
$trapScores = [];
$debugInfo = [];

if ($trapsFile && file_exists($trapsFile)) {
    $handle = fopen($trapsFile, 'r');
    if ($handle) {
        $lineCount = 0;
        while (($line = fgets($handle)) !== false) {
            $lineCount++;
            $entry = json_decode(trim($line), true);
            if (!$entry) continue;
            
            // Debug: Store first few entries to understand structure
            if ($lineCount <= 3) {
                $debugInfo[] = array_keys($entry);
            }
            
            // Check if this trap belongs to the current player
            // Try multiple possible field names for UCID
            $trapUcid = $entry['ucid'] ?? $entry['player_ucid'] ?? $entry['pilot_ucid'] ?? null;
            
            if ($trapUcid && $trapUcid === $ucid) {
                $traps++;
                
                // Collect trap scores if available
                if (isset($entry['score'])) {
                    $trapScores[] = $entry['score'];
                } elseif (isset($entry['grade'])) {
                    // Convert letter grades to numeric scores if needed
                    $gradeMap = ['OK' => 3, 'Fair' => 2, 'No Grade' => 1, 'Cut' => 0, 'Bolter' => 0];
                    $trapScores[] = $gradeMap[$entry['grade']] ?? 1;
                } elseif (isset($entry['wire'])) {
                    // Score based on wire (3-wire is perfect)
                    $wireScores = [1 => 2, 2 => 3, 3 => 4, 4 => 3];
                    $trapScores[] = $wireScores[$entry['wire']] ?? 2;
                }
            }
        }
        fclose($handle);
    }
}

// Calculate average trap score
$avgTrapScore = count($trapScores) > 0 ? round(array_sum($trapScores) / count($trapScores), 2) : 0;

// Get top 5 aircraft for chart
$topAircraft = array_slice($aircraftUsage, 0, 5, true);
$aircraftData = [];
foreach ($topAircraft as $aircraft => $count) {
    $aircraftData[] = [
        'name' => htmlspecialchars($aircraft, ENT_QUOTES, 'UTF-8'),
        'count' => $count
    ];
}

// Sanitize data before sending to prevent XSS
echo json_encode([
    "name" => htmlspecialchars($exactName ?? $playerName, ENT_QUOTES, 'UTF-8'),
    "ucid" => htmlspecialchars($ucid, ENT_QUOTES, 'UTF-8'),
    "kills" => $kills,
    "sorties" => $sorties,
    "takeoffs" => $takeoffs,
    "landings" => $landings,
    "crashes" => $crashes,
    "ejections" => $ejections,
    "traps" => $traps,
    "avgTrapScore" => $avgTrapScore,
    "trapScores" => $trapScores,
    "mostUsedAircraft" => htmlspecialchars($mostUsedAircraft ?? "Unknown", ENT_QUOTES, 'UTF-8'),
    "aircraftUsage" => $aircraftData
]);
