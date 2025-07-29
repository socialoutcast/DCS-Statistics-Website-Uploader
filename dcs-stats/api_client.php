<?php
/**
 * DCSServerBot REST API Client
 * 
 * This class handles all API interactions with DCSServerBot REST API
 * replacing the previous JSON file-based approach.
 */

class DCSServerBotAPIClient {
    private $apiBaseUrl;
    private $apiKey;
    private $timeout;
    
    public function __construct($config = []) {
        $this->apiBaseUrl = $config['api_base_url'] ?? 'http://localhost:8080/api';
        $this->apiKey = $config['api_key'] ?? null;
        $this->timeout = $config['timeout'] ?? 30;
    }
    
    /**
     * Make a request to the API
     */
    public function makeRequest($method, $endpoint, $data = null) {
        $url = $this->apiBaseUrl . $endpoint;
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        
        // Add headers
        $headers = [
            'Content-Type: application/json',
            'Accept: application/json'
        ];
        
        if ($this->apiKey) {
            $headers[] = 'Authorization: Bearer ' . $this->apiKey;
        }
        
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        // Set method and data
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        } elseif ($method === 'GET') {
            if ($data) {
                $url .= '?' . http_build_query($data);
                curl_setopt($ch, CURLOPT_URL, $url);
            }
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception('API request failed: ' . $error);
        }
        
        if ($httpCode >= 400) {
            throw new Exception('API returned error code: ' . $httpCode);
        }
        
        return json_decode($response, true);
    }
    
    /**
     * Get user information by nickname
     * Returns an array of matching users
     */
    public function getUser($nickname) {
        // API expects 'nick' not 'nickname'
        // Returns an array of users that match this nick
        $users = $this->makeRequest('POST', '/getuser', ['nick' => $nickname]);
        // Return the first match if found
        return !empty($users) && is_array($users) ? $users[0] : null;
    }
    
    /**
     * Get player statistics
     */
    public function getPlayerStats($nickname, $date = null) {
        // API expects 'nick' not 'nickname'
        $data = ['nick' => $nickname];
        if ($date) {
            $data['date'] = $date;
        }
        return $this->makeRequest('POST', '/stats', $data);
    }
    
    /**
     * Get top players by kills
     */
    public function getTopKills() {
        return $this->makeRequest('GET', '/topkills');
    }
    
    /**
     * Get top players by kill/death ratio
     */
    public function getTopKDR() {
        return $this->makeRequest('GET', '/topkdr');
    }
    
    /**
     * Get missile probability of kill for a player
     */
    public function getMissilePK($nickname, $date = null) {
        // API expects 'nick' not 'nickname'
        $data = ['nick' => $nickname];
        if ($date) {
            $data['date'] = $date;
        }
        return $this->makeRequest('POST', '/missilepk', $data);
    }
    
    /**
     * Search for players (custom implementation since not in API)
     * This will need to be adjusted based on available API endpoints
     */
    public function searchPlayers($query) {
        // For now, we'll need to implement this differently
        // Perhaps by getting all players and filtering client-side
        // or requesting this feature in the API
        throw new Exception('Player search not yet implemented in API');
    }
    
    /**
     * Get credits/points for players (custom implementation)
     * This endpoint doesn't exist in the current API
     */
    public function getCredits() {
        // This will need a custom endpoint or different approach
        throw new Exception('Credits endpoint not yet available in API');
    }
    
    /**
     * Get squadron information (custom implementation)
     * This endpoint doesn't exist in the current API
     */
    public function getSquadrons() {
        // This will need a custom endpoint or different approach
        throw new Exception('Squadron endpoint not yet available in API');
    }
    
    /**
     * Get server/instance information (custom implementation)
     * This endpoint doesn't exist in the current API
     */
    public function getServers() {
        // This will need a custom endpoint or different approach
        throw new Exception('Server info endpoint not yet available in API');
    }
}

// Configuration loader
function loadAPIConfig() {
    $configFile = __DIR__ . '/api_config.json';
    if (file_exists($configFile)) {
        return json_decode(file_get_contents($configFile), true);
    }
    
    // Default configuration
    return [
        'api_base_url' => getenv('DCSBOT_API_URL') ?: 'http://localhost:8080/api',
        'api_key' => getenv('DCSBOT_API_KEY') ?: null,
        'timeout' => 30
    ];
}

// Create global API client instance
$apiClient = new DCSServerBotAPIClient(loadAPIConfig());