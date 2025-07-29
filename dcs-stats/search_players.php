<?php
/**
 * Search Players Endpoint Router
 * Routes to API or JSON version based on configuration
 */

// Load configuration
$configFile = __DIR__ . '/api_config.json';
$config = file_exists($configFile) ? json_decode(file_get_contents($configFile), true) : [];

// Check if API is enabled for this endpoint
$useAPI = ($config['use_api'] ?? false) && 
          in_array('search_players.php', $config['enabled_endpoints'] ?? []);

// Include appropriate version
if ($useAPI) {
    include __DIR__ . '/search_players_api.php';
} else {
    include __DIR__ . '/search_players_json.php';
}