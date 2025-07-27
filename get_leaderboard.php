<?php
header('Content-Type: application/json');
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Load player names
$playerMap = [];
$playersFile = __DIR__ . '/data/players.json';
if (file_exists($playersFile)) {
    $handle = fopen($playersFile, "r");
    if ($handle) {
        while (($line = fgets($handle)) !== false) {
            $data = json_decode(trim($line), true);
            if (isset($data['ucid'], $data['name'])) {
                $playerMap[$data['ucid']] = $data['name'];
            }
        }
        fclose($handle);
    }
}

// Prepare statistics
$stats = [];
$takeoffTimestamps = [];
$missionStatsFile = __DIR__ . '/data/missionstats.json';
if (file_exists($missionStatsFile)) {
    $handle = fopen($missionStatsFile, "r");
    if ($handle) {
        while (($line = fgets($handle)) !== false) {
            $entry = json_decode(trim($line), true);
            if (!$entry || !isset($entry['init_id']) || $entry['init_id'] === "-1") continue;

            $id = $entry['init_id'];
            $event = $entry['event'] ?? '';
            $time = strtotime($entry['time'] ?? '');

            if (!isset($stats[$id])) {
                $stats[$id] = [
                    'name' => $playerMap[$id] ?? $id,
                    'kills' => 0,
                    'sorties' => 0,
                    'flight_seconds' => 0,
                    'deaths' => 0,
                    'takeoffs' => 0,
                    'landings' => 0,
                    'crashes' => 0,
                    'ejections' => 0,
                    'airframes' => []
                ];
            }

            $airframe = $entry['init_type'] ?? '';
            if ($airframe) {
                if (!isset($stats[$id]['airframes'][$airframe])) {
                    $stats[$id]['airframes'][$airframe] = 0;
                }
                $stats[$id]['airframes'][$airframe]++;
            }

            switch ($event) {
                case 'S_EVENT_HIT':
                    $stats[$id]['kills']++;
                    break;
                case 'S_EVENT_TAKEOFF':
                    $stats[$id]['takeoffs']++;
                    $stats[$id]['sorties']++;
                    $takeoffTimestamps[$id] = $time;
                    break;
                case 'S_EVENT_LAND':
                case 'S_EVENT_CRASH':
                case 'S_EVENT_EJECTION':
                    if (isset($takeoffTimestamps[$id]) && $time > $takeoffTimestamps[$id]) {
                        $flightTime = $time - $takeoffTimestamps[$id];
                        $stats[$id]['flight_seconds'] += $flightTime;
                        unset($takeoffTimestamps[$id]);
                    }
                    if ($event === 'S_EVENT_LAND') $stats[$id]['landings']++;
                    if ($event === 'S_EVENT_CRASH') $stats[$id]['crashes']++;
                    if ($event === 'S_EVENT_EJECTION') $stats[$id]['ejections']++;
                    break;
                case 'S_EVENT_DEAD':
                    $stats[$id]['deaths']++;
                    break;
            }
        }
        fclose($handle);
    }
}

// Finalize stats
foreach ($stats as $id => &$s) {
    $s[''] = round($s['flight_seconds'] / 3600, 2);
    unset($s['flight_seconds']);
    if (!empty($s['airframes'])) {
        arsort($s['airframes']);
        $s['most_used_aircraft'] = array_key_first($s['airframes']);
    } else {
        $s['most_used_aircraft'] = "Unknown";
    }
    unset($s['airframes']);
}

// Sort and rank
usort($stats, fn($a, $b) => $b['kills'] <=> $a['kills']);
foreach ($stats as $i => &$s) {
    $s['rank'] = $i + 1;
}

echo json_encode($stats);
?>