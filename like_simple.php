<?php
// Simple test endpoint
header('Content-Type: application/json');

// Log all request data for debugging
error_log("=== LIKE SIMPLE DEBUG ===");
error_log("Method: " . $_SERVER['REQUEST_METHOD']);
error_log("GET: " . print_r($_GET, true));
error_log("POST: " . print_r($_POST, true));
error_log("Raw Input: " . file_get_contents('php://input'));
error_log("Content-Type: " . ($_SERVER['CONTENT_TYPE'] ?? 'not set'));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo json_encode([
        'success' => true,
        'message' => 'POST request received successfully',
        'data' => $_POST,
        'method' => $_SERVER['REQUEST_METHOD']
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Only POST requests allowed',
        'method' => $_SERVER['REQUEST_METHOD']
    ]);
}
?>