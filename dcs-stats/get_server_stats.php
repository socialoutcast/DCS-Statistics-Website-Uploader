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

// Validate file paths
$dataDir = __DIR__ . '/data';
$playersFile = validatePath($dataDir . '/players.json', $dataDir);
$missionsFile = validatePath($dataDir . '/missionstats.json', $dataDir);

if (!$playersFile || !$missionsFile) {
    logSecurityEvent('PATH_TRAVERSAL_ATTEMPT', 'Invalid file path access attempt');
    echo json_encode(["error" => "Service temporarily unavailable"]);
    exit;
}

// Initialize statistics
$totalPlayers = 0;
$playerVisits = [];
$totalKills = 0;
$totalDeaths = 0;
$pilotStats = [];

// Count unique players
if (file_exists($playersFile)) {
    $handle = fopen($playersFile, 'r');
    if ($handle) {
        while (($line = fgets($handle)) !== false) {
            $entry = validateJsonLine($line, ['name', 'ucid']);
            if (!$entry) continue;
            
            $totalPlayers++;
            $pilotStats[$entry['ucid']] = [
                'name' => $entry['name'],
                'visits' => 0,
                'kills' => 0,
                'deaths' => 0
            ];
        }
        fclose($handle);
    }
}

// Process mission stats
if (file_exists($missionsFile)) {
    $handle = fopen($missionsFile, 'r');
    if ($handle) {
        while (($line = fgets($handle)) !== false) {
            $entry = validateJsonLine($line, ['event']);
            if (!$entry) continue;
            
            // Count visits (mission starts)
            if ($entry['event'] === "S_EVENT_MISSION_START" && isset($entry['init_id']) && isset($pilotStats[$entry['init_id']])) {
                $pilotStats[$entry['init_id']]['visits']++;
            }
            
            // Count kills
            if ($entry['event'] === "S_EVENT_HIT" && isset($entry['init_id']) && isset($pilotStats[$entry['init_id']])) {
                $pilotStats[$entry['init_id']]['kills']++;
                $totalKills++;
            }
            
            // Count deaths (crashes and ejections count as deaths)
            if (in_array($entry['event'], ["S_EVENT_CRASH", "S_EVENT_EJECTION"]) && 
                isset($entry['init_id']) && isset($pilotStats[$entry['init_id']])) {
                $pilotStats[$entry['init_id']]['deaths']++;
                $totalDeaths++;
            }
        }
        fclose($handle);
    }
}

// Get top 5 pilots by visits
$pilotsByVisits = $pilotStats;
usort($pilotsByVisits, function($a, $b) {
    return $b['visits'] - $a['visits'];
});
$top5Pilots = array_slice($pilotsByVisits, 0, 5);

// Format top 5 for chart
$top5Data = [];
foreach ($top5Pilots as $pilot) {
    if ($pilot['visits'] > 0) {
        $top5Data[] = [
            'name' => htmlspecialchars($pilot['name'], ENT_QUOTES, 'UTF-8'),
            'visits' => $pilot['visits']
        ];
    }
}

// Return stats
echo json_encode([
    'totalPlayers' => $totalPlayers,
    'totalKills' => $totalKills,
    'totalDeaths' => $totalDeaths,
    'top5Pilots' => $top5Data
]);