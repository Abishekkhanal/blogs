<?php
// Start output buffering to capture any errors
ob_start();

include 'db.php';

// Debug: Show what we received
echo "<!DOCTYPE html><html><head><title>Like Debug</title></head><body>";
echo "<h3>Like.php Debug</h3>";
echo "<p><strong>Request Method:</strong> " . $_SERVER['REQUEST_METHOD'] . "</p>";
echo "<p><strong>POST Data:</strong></p><pre>" . print_r($_POST, true) . "</pre>";
echo "<p><strong>GET Data:</strong></p><pre>" . print_r($_GET, true) . "</pre>";

// Only handle POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo "<p style='color: red;'>❌ Not a POST request</p>";
    echo "<p><a href='blogs.php'>← Back to Blogs</a></p>";
    echo "</body></html>";
    exit();
}

// Get POST data
$blogId = isset($_POST['blog_id']) ? (int)$_POST['blog_id'] : 0;
$slug = isset($_POST['slug']) ? trim($_POST['slug']) : '';

echo "<p><strong>Processed Values:</strong></p>";
echo "<p>Blog ID: " . $blogId . "</p>";
echo "<p>Slug: '" . htmlspecialchars($slug) . "'</p>";

// Validate required data
if (!$blogId || !$slug) {
    echo "<p style='color: red;'>❌ Missing required data</p>";
    echo "<p><a href='blogs.php'>← Back to Blogs</a></p>";
    echo "</body></html>";
    exit();
}

// Get user's IP address
$ip = $_SERVER['REMOTE_ADDR'];
echo "<p>User IP: " . $ip . "</p>";

try {
    // Check if user already liked this post
    $checkStmt = $conn->prepare("SELECT id FROM likes WHERE blog_id = ? AND user_ip = ?");
    $checkStmt->bind_param("is", $blogId, $ip);
    $checkStmt->execute();
    $result = $checkStmt->get_result();

    if ($result->num_rows > 0) {
        // Already liked
        $message = 'already_liked';
        echo "<p style='color: orange;'>⚠️ Already liked this post</p>";
    } else {
        // Add new like
        $insertStmt = $conn->prepare("INSERT INTO likes (blog_id, user_ip) VALUES (?, ?)");
        $insertStmt->bind_param("is", $blogId, $ip);
        
        if ($insertStmt->execute()) {
            $message = 'like_success';
            echo "<p style='color: green;'>✅ Like added successfully!</p>";
        } else {
            $message = 'like_failed';
            echo "<p style='color: red;'>❌ Failed to add like: " . $conn->error . "</p>";
        }
    }

    // Show redirect URL
    $redirectUrl = "blog-view.php?slug=" . urlencode($slug) . "&like=" . $message;
    echo "<p><strong>Would redirect to:</strong> " . htmlspecialchars($redirectUrl) . "</p>";
    echo "<p><a href='" . htmlspecialchars($redirectUrl) . "'>← Click here to go back to the blog post</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database error: " . $e->getMessage() . "</p>";
}

echo "</body></html>";

// Uncomment this line to enable automatic redirect after testing:
// header("Location: blog-view.php?slug=" . urlencode($slug) . "&like=" . $message);
// exit();
?>