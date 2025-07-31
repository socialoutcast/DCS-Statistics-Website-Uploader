<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Load configuration
$configFile = __DIR__ . '/api_config.json';
$config = file_exists($configFile) ? json_decode(file_get_contents($configFile), true) : [];

// Return only what client needs - API is now mandatory
echo json_encode([
    'api_base_url' => $config['api_base_url'] ?? 'http://localhost:8080',
    'use_api' => true,  // Always use API
    'fallback_to_json' => false,  // Never fallback to JSON
    'timeout' => $config['timeout'] ?? 30
]);