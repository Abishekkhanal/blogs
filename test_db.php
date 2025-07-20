<?php
include 'db.php';

echo "<h2>Database Test</h2>";

// Test 1: Check connection
echo "<h3>1. Database Connection</h3>";
if ($conn) {
    echo "✅ Connected to database<br>";
} else {
    echo "❌ Database connection failed<br>";
    exit;
}

// Test 2: Check blogs table structure
echo "<h3>2. Blogs Table Structure</h3>";
$result = $conn->query("DESCRIBE blogs");
if ($result) {
    echo "<table border='1'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . $row['Default'] . "</td>";
        echo "<td>" . $row['Extra'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "❌ Error describing blogs table: " . $conn->error . "<br>";
}

// Test 3: Count existing blogs
echo "<h3>3. Existing Blogs Count</h3>";
$count_result = $conn->query("SELECT COUNT(*) as count FROM blogs");
if ($count_result) {
    $count = $count_result->fetch_assoc()['count'];
    echo "✅ Found $count blogs in database<br>";
} else {
    echo "❌ Error counting blogs: " . $conn->error . "<br>";
}

// Test 4: Show recent blogs
echo "<h3>4. Recent Blogs</h3>";
$recent_result = $conn->query("SELECT id, title, status, created_at FROM blogs ORDER BY created_at DESC LIMIT 5");
if ($recent_result) {
    if ($recent_result->num_rows > 0) {
        echo "<table border='1'>";
        echo "<tr><th>ID</th><th>Title</th><th>Status</th><th>Created</th></tr>";
        while ($row = $recent_result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['id'] . "</td>";
            echo "<td>" . $row['title'] . "</td>";
            echo "<td>" . $row['status'] . "</td>";
            echo "<td>" . $row['created_at'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "No blogs found<br>";
    }
} else {
    echo "❌ Error fetching blogs: " . $conn->error . "<br>";
}

// Test 5: Try a simple insert
echo "<h3>5. Test Insert</h3>";
$test_title = "Test Post " . date('Y-m-d H:i:s');
$test_slug = "test-post-" . time();
$test_content = "This is a test post content";
$test_category = "travel";
$test_author = "test_user";
$test_status = "draft";

$stmt = $conn->prepare("INSERT INTO blogs (title, slug, content, category, author, status) VALUES (?, ?, ?, ?, ?, ?)");
if ($stmt) {
    $stmt->bind_param("ssssss", $test_title, $test_slug, $test_content, $test_category, $test_author, $test_status);
    if ($stmt->execute()) {
        echo "✅ Test insert successful! Insert ID: " . $conn->insert_id . "<br>";
        echo "Now deleting the test post...<br>";
        $conn->query("DELETE FROM blogs WHERE id = " . $conn->insert_id);
        echo "✅ Test post deleted<br>";
    } else {
        echo "❌ Test insert failed: " . $stmt->error . "<br>";
    }
} else {
    echo "❌ Test prepare failed: " . $conn->error . "<br>";
}

echo "<br><a href='add_blog.php'>Back to Add Blog</a>";
?>