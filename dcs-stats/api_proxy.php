<?php
/**
 * API Proxy Endpoint
 * Handles all API requests from JavaScript, bypassing CORS issues
 */

// Set CORS headers to allow JavaScript access
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept');
header('Content-Type: application/json');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Load API configuration
$configFile = __DIR__ . '/api_config.json';
if (!file_exists($configFile)) {
    http_response_code(500);
    echo json_encode(['error' => 'API configuration not found']);
    exit;
}

$apiConfig = json_decode(file_get_contents($configFile), true);
if (!$apiConfig['use_api'] || empty($apiConfig['api_base_url'])) {
    http_response_code(503);
    echo json_encode(['error' => 'API not enabled']);
    exit;
}

// Get request parameters
$endpoint = $_GET['endpoint'] ?? '';
$method = $_GET['method'] ?? 'GET';
$data = [];

// Handle POST data
if ($method === 'POST') {
    // Get POST data from the request body
    $input = file_get_contents('php://input');
    $data = json_decode($input, true) ?? [];
}

// Validate endpoint
if (empty($endpoint)) {
    http_response_code(400);
    echo json_encode(['error' => 'Endpoint parameter required']);
    exit;
}

// Build full URL
$url = rtrim($apiConfig['api_base_url'], '/') . '/' . ltrim($endpoint, '/');

// Initialize cURL
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, $apiConfig['timeout'] ?? 30);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

// Handle HTTPS properly - allow self-signed certificates for local/dev environments
if (strpos($url, 'https://') === 0) {
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
}

// Set method and data
if ($method === 'POST') {
    curl_setopt($ch, CURLOPT_POST, true);
    
    // Use form-urlencoded for POST data
    if (!empty($data)) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
    } else {
        curl_setopt($ch, CURLOPT_POSTFIELDS, '');
    }
}

// Execute request
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

// Handle errors
if ($error) {
    http_response_code(502);
    echo json_encode([
        'error' => 'API request failed: ' . $error,
        'url' => $url,
        'method' => $method
    ]);
    exit;
}

// Handle empty responses
if (empty($response)) {
    http_response_code(502);
    echo json_encode([
        'error' => 'Empty response from API',
        'url' => $url,
        'http_code' => $httpCode
    ]);
    exit;
}

// Forward the HTTP status code
http_response_code($httpCode);

// Return the response
echo $response;