<?php
include 'db.php';

// Step 1: Check for blog_id
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['blog_id'])) {
    $blogId = (int)$_POST['blog_id'];
    $slug = $_POST['slug'] ?? '';
} elseif (isset($_GET['blog_id'])) {
    $blogId = (int)$_GET['blog_id'];
    $slug = $_GET['slug'] ?? '';
} else {
    // Redirect back if no blog_id found
    header("Location: blogs.php");
    exit();
}

// Step 2: Get the IP address
$ip = $_SERVER['REMOTE_ADDR'];

// Step 3: Check if already liked
$stmt = $conn->prepare("SELECT id FROM likes WHERE blog_id = ? AND user_ip = ?");
$stmt->bind_param("is", $blogId, $ip);
$stmt->execute();
$result = $stmt->get_result();

$message = '';
$success = false;

if ($result->num_rows > 0) {
    $message = 'already_liked';
} else {
    // Step 4: Insert the like
    $insert = $conn->prepare("INSERT INTO likes (blog_id, user_ip) VALUES (?, ?)");
    $insert->bind_param("is", $blogId, $ip);
    if ($insert->execute()) {
        $message = 'like_success';
        $success = true;
    } else {
        $message = 'like_failed';
    }
}

// Step 5: Redirect back to the blog-view page with message
if ($slug) {
    $redirectUrl = "blog-view.php?slug=" . urlencode($slug);
    if ($message) {
        $redirectUrl .= "&like=" . $message;
    }
    header("Location: $redirectUrl");
    exit();
} else {
    // Fallback redirect to blogs page
    header("Location: blogs.php");
    exit();
}
?>