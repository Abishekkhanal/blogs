<?php
include 'db.php';

// Handle GET requests (simple link approach)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $blogId = isset($_GET['blog_id']) ? (int)$_GET['blog_id'] : 0;
    $slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';
} else {
    // Fallback: also handle POST requests
    $blogId = isset($_POST['blog_id']) ? (int)$_POST['blog_id'] : 0;
    $slug = isset($_POST['slug']) ? trim($_POST['slug']) : '';
}

// Validate required data
if (!$blogId || !$slug) {
    header("Location: blogs.php");
    exit();
}

// Get user's IP address
$ip = $_SERVER['REMOTE_ADDR'];

// Check if user already liked this post
$checkStmt = $conn->prepare("SELECT id FROM likes WHERE blog_id = ? AND user_ip = ?");
$checkStmt->bind_param("is", $blogId, $ip);
$checkStmt->execute();
$result = $checkStmt->get_result();

if ($result->num_rows > 0) {
    // Already liked
    $message = 'already_liked';
} else {
    // Add new like
    $insertStmt = $conn->prepare("INSERT INTO likes (blog_id, user_ip) VALUES (?, ?)");
    $insertStmt->bind_param("is", $blogId, $ip);
    
    if ($insertStmt->execute()) {
        $message = 'like_success';
    } else {
        $message = 'like_failed';
    }
}

// Redirect back to blog post with message
header("Location: blog-view.php?slug=" . urlencode($slug) . "&like=" . $message);
exit();
?>