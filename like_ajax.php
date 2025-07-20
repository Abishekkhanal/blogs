<?php
header('Content-Type: application/json');
include 'db.php';

// Enable CORS if needed
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Only handle POST requests for AJAX
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method: ' . $_SERVER['REQUEST_METHOD']]);
    exit();
}

// Get input data - try both JSON and form data
$input = null;
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';

if (strpos($contentType, 'application/json') !== false) {
    // JSON request
    $jsonInput = file_get_contents('php://input');
    $input = json_decode($jsonInput, true);
} else {
    // Form data request
    $input = $_POST;
}

// Debug: log the input
error_log("Like AJAX Input: " . print_r($input, true));

// Get parameters
$blogId = isset($input['blog_id']) ? (int)$input['blog_id'] : 0;
$action = isset($input['action']) ? $input['action'] : ''; // 'like' or 'unlike'

// Validate required data
if (!$blogId || !in_array($action, ['like', 'unlike'])) {
    echo json_encode([
        'success' => false, 
        'message' => 'Invalid parameters', 
        'debug' => [
            'blog_id' => $blogId,
            'action' => $action,
            'input' => $input
        ]
    ]);
    exit();
}

// Get user's IP address
$ip = $_SERVER['REMOTE_ADDR'];

try {
    if ($action === 'like') {
        // Check if already liked
        $checkStmt = $conn->prepare("SELECT id FROM likes WHERE blog_id = ? AND user_ip = ?");
        $checkStmt->bind_param("is", $blogId, $ip);
        $checkStmt->execute();
        $result = $checkStmt->get_result();

        if ($result->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => 'Already liked']);
            exit();
        }

        // Add like
        $insertStmt = $conn->prepare("INSERT INTO likes (blog_id, user_ip) VALUES (?, ?)");
        $insertStmt->bind_param("is", $blogId, $ip);
        
        if ($insertStmt->execute()) {
            // Get updated count
            $countStmt = $conn->prepare("SELECT COUNT(*) as count FROM likes WHERE blog_id = ?");
            $countStmt->bind_param("i", $blogId);
            $countStmt->execute();
            $newCount = $countStmt->get_result()->fetch_assoc()['count'];
            
            echo json_encode([
                'success' => true, 
                'action' => 'liked',
                'message' => 'Post liked successfully',
                'likes_count' => $newCount
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to like post: ' . $conn->error]);
        }
        
    } else if ($action === 'unlike') {
        // Remove like
        $deleteStmt = $conn->prepare("DELETE FROM likes WHERE blog_id = ? AND user_ip = ?");
        $deleteStmt->bind_param("is", $blogId, $ip);
        
        if ($deleteStmt->execute() && $deleteStmt->affected_rows > 0) {
            // Get updated count
            $countStmt = $conn->prepare("SELECT COUNT(*) as count FROM likes WHERE blog_id = ?");
            $countStmt->bind_param("i", $blogId);
            $countStmt->execute();
            $newCount = $countStmt->get_result()->fetch_assoc()['count'];
            
            echo json_encode([
                'success' => true, 
                'action' => 'unliked',
                'message' => 'Post unliked successfully',
                'likes_count' => $newCount
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to unlike post or already unliked']);
        }
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>