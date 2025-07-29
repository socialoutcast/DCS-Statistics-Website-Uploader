<?php
header('Content-Type: application/json');
ini_set('display_errors', 0);
error_reporting(0);

// Include security functions and API client
require_once __DIR__ . '/security_functions.php';
require_once __DIR__ . '/api_client.php';

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
    $useAPI = true;
    $stats = null;
    
    if ($useAPI) {
        try {
            // Get player stats from API
            // API /stats returns: deaths, aakills, aakdr, lastSessionKills, lastSessionDeaths, killsbymodule, kdrByModule
            $apiStats = $apiClient->getPlayerStats($playerName);
            
            // Get user info from API
            // API /getuser returns array of users with: name, last_seen
            $userInfo = $apiClient->getUser($playerName);
            
            // Check if we got valid responses
            if (!$apiStats && !$userInfo) {
                // Player not found
                echo json_encode(["error" => "Player not found"]);
                exit;
            }
            
            // Transform API response to match our existing format
            $stats = [
                'ucid' => null,  // API doesn't return UCID
                'name' => htmlspecialchars($userInfo['name'] ?? $playerName, ENT_QUOTES, 'UTF-8'),
                'last_seen' => $userInfo['last_seen'] ?? null,
                'kills' => $apiStats['aakills'] ?? 0,
                'deaths' => $apiStats['deaths'] ?? 0,
                'kd_ratio' => $apiStats['aakdr'] ?? 0,
                // Additional stats from API
                'kills_by_module' => $apiStats['killsbymodule'] ?? [],
                'kdr_by_module' => $apiStats['kdrByModule'] ?? [],
                'last_session_kills' => $apiStats['lastSessionKills'] ?? 0,
                'last_session_deaths' => $apiStats['lastSessionDeaths'] ?? 0,
                // These fields are not available in current API
                'takeoffs' => 0,
                'landings' => 0,
                'crashes' => 0,
                'ejections' => 0,
                'flight_hours' => 0,
                'sorties' => 0,
                'most_used_aircraft' => 'N/A',
                'teamkills' => 0,
                'carrier_traps' => 0,  // Not in API
                'trap_case1' => 0,
                'trap_case2' => 0,
                'trap_case3' => 0
            ];
            
            // Try to get missile PK data as well
            try {
                $missilePK = $apiClient->getMissilePK($playerName);
                // Format missile stats for display
                if ($missilePK && isset($missilePK['weapon'])) {
                    $weaponStats = [];
                    foreach ($missilePK['weapon'] as $weaponName => $pk) {
                        $weaponStats[] = [
                            'weapon' => $weaponName,
                            'pk' => $pk
                        ];
                    }
                    // Sort by PK descending
                    usort($weaponStats, function($a, $b) {
                        return $b['pk'] <=> $a['pk'];
                    });
                    $stats['weapon_effectiveness'] = $weaponStats;
                } else {
                    $stats['weapon_effectiveness'] = [];
                }
            } catch (Exception $e) {
                // Missile stats are optional
                $stats['weapon_effectiveness'] = [];
            }
            
        } catch (Exception $apiError) {
            // Log API error and fall back to JSON
            logSecurityEvent('API_ERROR', 'Failed to fetch player stats from API: ' . $apiError->getMessage());
            $useAPI = false;
        }
    }
    
    // Fallback to JSON file processing if API fails or is disabled
    if (!$useAPI) {
        $playerNameLower = strtolower($playerName);
        
        // Validate file paths
        $dataDir = __DIR__ . '/data';
        $playersFile = validatePath($dataDir . '/players.json', $dataDir);
        $missionsFile = validatePath($dataDir . '/missionstats.json', $dataDir);
        
        if (!$playersFile || !$missionsFile) {
            logSecurityEvent('PATH_TRAVERSAL_ATTEMPT', 'Invalid file path access attempt');
            echo json_encode(["error" => "Service temporarily unavailable"]);
            exit;
        }
        
        // Validate files
        if (!file_exists($playersFile) || !file_exists($missionsFile)) {
            logSecurityEvent('DATA_UNAVAILABLE', 'Required data files not found');
            echo json_encode(["error" => "Service temporarily unavailable"]);
            exit;
        }
        
        // Search for UCID in players.json
        $handle = fopen($playersFile, "r");
        $ucid = null;
        while (($line = fgets($handle)) !== false) {
            $data = validateJsonLine($line, ['ucid', 'name']);
            if ($data && strtolower($data['name']) === $playerNameLower) {
                $ucid = $data['ucid'];
                break;
            }
        }
        fclose($handle);
        
        if (!$ucid) {
            echo json_encode(["error" => "Player not found"]);
            exit;
        }
        
        // Initialize stats
        $stats = [
            'ucid' => $ucid,
            'name' => htmlspecialchars($playerName, ENT_QUOTES, 'UTF-8'),
            'kills' => 0,
            'deaths' => 0,
            'takeoffs' => 0,
            'landings' => 0,
            'crashes' => 0,
            'ejections' => 0,
            'flight_seconds' => 0,
            'sorties' => 0,
            'teamkills' => 0,
            'airframes' => []
        ];
        
        $takeoffTimestamps = [];
        
        // Parse missionstats.json
        $handle = fopen($missionsFile, "r");
        while (($line = fgets($handle)) !== false) {
            $entry = validateJsonLine($line, ['init_id']);
            if (!$entry) continue;
            
            $id = $entry['init_id'] ?? null;
            $event = $entry['event'] ?? '';
            $time = strtotime($entry['time'] ?? '');
            
            if ($id === $ucid) {
                $airframe = $entry['init_type'] ?? '';
                if ($airframe && !isset($stats['airframes'][$airframe])) {
                    $stats['airframes'][$airframe] = 0;
                }
                if ($airframe) $stats['airframes'][$airframe]++;
                
                switch ($event) {
                    case 'S_EVENT_HIT':
                        if (($entry['target_coalition'] ?? '') === ($entry['init_coalition'] ?? '')) {
                            $stats['teamkills']++;
                        } else {
                            $stats['kills']++;
                        }
                        break;
                    case 'S_EVENT_TAKEOFF':
                        $stats['takeoffs']++;
                        $stats['sorties']++;
                        $takeoffTimestamps[$id] = $time;
                        break;
                    case 'S_EVENT_LAND':
                    case 'S_EVENT_CRASH':
                    case 'S_EVENT_EJECTION':
                        if (isset($takeoffTimestamps[$id]) && $time > $takeoffTimestamps[$id]) {
                            $flightTime = $time - $takeoffTimestamps[$id];
                            $stats['flight_seconds'] += $flightTime;
                            unset($takeoffTimestamps[$id]);
                        }
                        if ($event === 'S_EVENT_LAND') $stats['landings']++;
                        if ($event === 'S_EVENT_CRASH') $stats['crashes']++;
                        if ($event === 'S_EVENT_EJECTION') $stats['ejections']++;
                        break;
                    case 'S_EVENT_DEAD':
                        $stats['deaths']++;
                        break;
                }
            }
        }
        fclose($handle);
        
        // Get traps from traps.json
        $trapsFile = validatePath($dataDir . '/traps.json', $dataDir);
        if ($trapsFile && file_exists($trapsFile)) {
            $handle = fopen($trapsFile, "r");
            $stats['carrier_traps'] = 0;
            $stats['trap_case1'] = 0;
            $stats['trap_case2'] = 0;
            $stats['trap_case3'] = 0;
            
            while (($line = fgets($handle)) !== false) {
                $entry = json_decode(trim($line), true);
                if (!$entry || ($entry['player_ucid'] ?? '') !== $ucid) continue;
                
                $stats['carrier_traps']++;
                
                $wire = $entry['wire'] ?? 0;
                $carrierCase = $entry['carrier_case'] ?? 0;
                if ($carrierCase == 1) $stats['trap_case1']++;
                elseif ($carrierCase == 2) $stats['trap_case2']++;
                elseif ($carrierCase == 3) $stats['trap_case3']++;
            }
            fclose($handle);
        }
        
        // Finalize stats
        $stats['flight_hours'] = round($stats['flight_seconds'] / 3600, 2);
        $stats['kd_ratio'] = $stats['deaths'] > 0 ? round($stats['kills'] / $stats['deaths'], 2) : $stats['kills'];
        
        if (!empty($stats['airframes'])) {
            arsort($stats['airframes']);
            $stats['most_used_aircraft'] = array_key_first($stats['airframes']);
        } else {
            $stats['most_used_aircraft'] = "Unknown";
        }
        
        unset($stats['flight_seconds']);
        unset($stats['airframes']);
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
    logSecurityEvent('GENERAL_ERROR', 'Player stats error: ' . $e->getMessage());
    echo json_encode([
        'error' => 'Service temporarily unavailable',
        'timestamp' => date('c')
    ]);
}