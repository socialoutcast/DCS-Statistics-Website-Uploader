<?php
/**
 * Get Credits API Implementation
 * Fetches credits data from DCSServerBot API
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/api_client_enhanced.php';
require_once __DIR__ . '/security_functions.php';

// Rate limiting: 60 requests per minute
if (!checkRateLimit(60, 60)) {
    exit;
}

// Initialize response
$response = ['data' => [], 'error' => null];

try {
    // Create API client
    $client = createEnhancedAPIClient();
    
    // Get credits data - the API expects POST with empty body or specific filters
    $credits = $client->request('/credits', [], 'POST');
    
    if ($credits && is_array($credits)) {
        $response['data'] = $credits;
        $response['source'] = 'api';
    } else {
        $response['error'] = 'No credits data available';
    }
    
} catch (Exception $e) {
    $response['error'] = 'Failed to fetch credits: ' . $e->getMessage();
}

echo json_encode($response);