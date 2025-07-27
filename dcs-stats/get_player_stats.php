<?php
header('Content-Type: application/json');

$playerName = strtolower(trim($_GET['name'] ?? ''));
$playersFile = __DIR__ . "/data/players.json";
$missionsFile = __DIR__ . "/data/missionstats.json";

// Validate name
if (!$playerName) {
    echo json_encode(["error" => "Missing player name"]);
    exit;
}

// Validate files
if (!file_exists($playersFile)) {
    echo json_encode(["error" => "players.json not found"]);
    exit;
}
if (!file_exists($missionsFile)) {
    echo json_encode(["error" => "missionstats.json not found"]);
    exit;
}

// Search for UCID in players.json (line-by-line NDJSON)
$ucid = null;
$handle = fopen($playersFile, 'r');
if ($handle) {
    while (($line = fgets($handle)) !== false) {
        $entry = json_decode($line, true);
        if (!isset($entry['name'])) continue;
        if (trim(strtolower($entry['name'])) === $playerName) {
            $ucid = $entry['ucid'];
            break;
        }
    }
    fclose($handle);
}

if (!$ucid) {
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
$aircraftUsage = [];

// Parse missionstats.json
$handle = fopen($missionsFile, 'r');
if ($handle) {
    while (($line = fgets($handle)) !== false) {
        $entry = json_decode($line, true);
        if (!$entry || !isset($entry['event'])) continue;

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

echo json_encode([
    "name" => $playerName,
    "ucid" => $ucid,
    "kills" => $kills,
    "sorties" => $sorties,
    "takeoffs" => $takeoffs,
    "landings" => $landings,
    "crashes" => $crashes,
    "ejections" => $ejections,
    "mostUsedAircraft" => $mostUsedAircraft ?? "Unknown"
]);
