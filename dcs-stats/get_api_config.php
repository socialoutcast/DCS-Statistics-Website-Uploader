<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Load configuration
$configFile = __DIR__ . '/api_config.json';
$config = file_exists($configFile) ? json_decode(file_get_contents($configFile), true) : [];

// Return only what client needs
echo json_encode([
    'api_base_url' => $config['api_base_url'] ?? 'http://localhost:8080',
    'use_api' => $config['use_api'] ?? false,
    'fallback_to_json' => $config['fallback_to_json'] ?? true,
    'timeout' => $config['timeout'] ?? 30
]);