<?php
header('Content-Type: application/json');

$playersFile = 'data/players.json';
$statsFile = 'data/missionstats.json';

if (!file_exists($playersFile) || !file_exists($statsFile)) {
    echo json_encode([]);
    exit;
}

// Load player names
$players = [];
$handle = fopen($playersFile, "r");
while (($line = fgets($handle)) !== false) {
    $data = json_decode($line, true);
    if (isset($data['ucid'], $data['name'])) {
        $players[$data['ucid']] = $data['name'];
    }
}
fclose($handle);

// Aggregate stats by init_id using 'event'
$stats = [];
$handle = fopen($statsFile, "r");
while (($line = fgets($handle)) !== false) {
    $data = json_decode($line, true);
    if (!is_array($data) || !isset($data['init_id'])) {
        continue;
    }

    $id = $data['init_id'];
    if (!isset($stats[$id])) {
        $stats[$id] = [
            "name" => $players[$id] ?? $id,
            "kills" => 0,
            "deaths" => 0,
            "sorties" => 0,
            "missions" => 0,
            "points" => 0
        ];
    }

    $event = $data['event'] ?? null;
    if ($event) {
        switch ($event) {
            case 'S_EVENT_KILL':
                $stats[$id]['kills']++;
                break;
            case 'S_EVENT_DEAD':
                $stats[$id]['deaths']++;
                break;
            case 'S_EVENT_TAKEOFF':
                $stats[$id]['sorties']++;
                break;
            case 'S_EVENT_MISSION_END':
                $stats[$id]['missions']++;
                break;
        }
    }

    if (isset($data['points']) && is_numeric($data['points'])) {
        $stats[$id]['points'] += $data['points'];
    }
}
fclose($handle);

usort($stats, function($a, $b) {
    return $b['points'] <=> $a['points'];
});

echo json_encode(array_values($stats));
?>
