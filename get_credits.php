<?php
header('Content-Type: application/json');

$playersFile = 'data/players.json';
$creditsFile = 'data/credits.json';

$players = [];
$handle = fopen($playersFile, "r");
if ($handle) {
    while (($line = fgets($handle)) !== false) {
        $data = json_decode($line, true);
        if (isset($data['ucid'], $data['name'])) {
            $players[$data['ucid']] = $data['name'];
        }
    }
    fclose($handle);
}

$credits = [];
$handle = fopen($creditsFile, "r");
if ($handle) {
    while (($line = fgets($handle)) !== false) {
        $data = json_decode($line, true);
        $ucid = $data['player_ucid'];
        $points = $data['points'];
        if (!isset($credits[$ucid])) {
            $credits[$ucid] = 0;
        }
        $credits[$ucid] += $points;
    }
    fclose($handle);
}

$result = [];
foreach ($credits as $ucid => $totalPoints) {
    $result[] = [
        "name" => $players[$ucid] ?? $ucid,
        "credits" => $totalPoints
    ];
}

usort($result, function($a, $b) {
    return $b['credits'] - $a['credits'];
});

echo json_encode($result);
?>
