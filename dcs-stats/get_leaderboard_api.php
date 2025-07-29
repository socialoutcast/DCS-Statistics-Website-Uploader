<?php
header('Content-Type: application/json');
ini_set('display_errors', 0);
error_reporting(0);

// Include security functions and API client
require_once __DIR__ . '/security_functions.php';
require_once __DIR__ . '/api_client.php';

// Rate limiting: 120 requests per minute (doubled for high-traffic endpoint)
if (!checkRateLimit(120, 60)) {
    exit;
}

try {
    // Attempt to use API first
    $useAPI = true;
    $stats = [];
    
    if ($useAPI) {
        try {
            // Check which sorting is requested
            $sortBy = $_GET['sort'] ?? 'kills';
            
            // Get data from appropriate endpoint
            if ($sortBy === 'kdr') {
                $topPlayers = $apiClient->getTopKDR();
            } else {
                $topPlayers = $apiClient->getTopKills();
            }
            
            // Transform API response to match our existing format
            // API returns: fullNickname, AAkills, deaths, AAKDR
            foreach ($topPlayers as $index => $player) {
                $stats[] = [
                    'rank' => $index + 1,
                    'name' => htmlspecialchars($player['fullNickname'] ?? 'Unknown', ENT_QUOTES, 'UTF-8'),
                    'kills' => $player['AAkills'] ?? 0,
                    'deaths' => $player['deaths'] ?? 0,
                    'kd_ratio' => $player['AAKDR'] ?? 0,
                    // API doesn't provide these fields, so we'll need to handle differently
                    'sorties' => 0,  // Not available in current API
                    'flight_hours' => 0,  // Not available in current API
                    'takeoffs' => 0,  // Not available in current API
                    'landings' => 0,  // Not available in current API
                    'crashes' => 0,  // Not available in current API
                    'ejections' => 0,  // Not available in current API
                    'most_used_aircraft' => 'N/A'  // Not available in current API
                ];
            }
            
            // If we need more detailed stats, we could make individual /stats calls
            // but that would be inefficient for a leaderboard
            
        } catch (Exception $apiError) {
            // Log API error and fall back to JSON
            logSecurityEvent('API_ERROR', 'Failed to fetch from API: ' . $apiError->getMessage());
            $useAPI = false;
        }
    }
    
    // Fallback to JSON file processing if API fails or is disabled
    if (!$useAPI) {
        // Original JSON-based code
        $playerMap = [];
        $dataDir = __DIR__ . '/data';
        $playersFile = validatePath($dataDir . '/players.json', $dataDir);
        
        if (file_exists($playersFile)) {
            $handle = fopen($playersFile, "r");
            if ($handle) {
                while (($line = fgets($handle)) !== false) {
                    $data = validateJsonLine($line, ['ucid', 'name']);
                    if ($data) {
                        $playerMap[$data['ucid']] = $data['name'];
                    }
                }
                fclose($handle);
            }
        }
        
        // Prepare statistics
        $stats = [];
        $takeoffTimestamps = [];
        $missionStatsFile = validatePath($dataDir . '/missionstats.json', $dataDir);
        
        if (file_exists($missionStatsFile)) {
            $handle = fopen($missionStatsFile, "r");
            if ($handle) {
                while (($line = fgets($handle)) !== false) {
                    $entry = validateJsonLine($line, ['init_id']);
                    if (!$entry || $entry['init_id'] === "-1") continue;
                    
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
            $s['flight_hours'] = round($s['flight_seconds'] / 3600, 2);
            unset($s['flight_seconds']);
            
            // Calculate K/D ratio
            $s['kd_ratio'] = $s['deaths'] > 0 ? round($s['kills'] / $s['deaths'], 2) : $s['kills'];
            
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
        
        // Sanitize data
        foreach ($stats as &$stat) {
            $stat['name'] = htmlspecialchars($stat['name'], ENT_QUOTES, 'UTF-8');
            $stat['most_used_aircraft'] = htmlspecialchars($stat['most_used_aircraft'], ENT_QUOTES, 'UTF-8');
        }
    }
    
    // Add metadata to response
    $response = [
        'source' => $useAPI ? 'api' : 'json',
        'timestamp' => date('c'),
        'data' => $stats
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    // Generic error response
    logSecurityEvent('GENERAL_ERROR', 'Leaderboard error: ' . $e->getMessage());
    echo json_encode([
        'error' => 'Service temporarily unavailable',
        'timestamp' => date('c')
    ]);
}