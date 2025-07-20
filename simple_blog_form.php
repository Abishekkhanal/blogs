<?php
session_start();
if (!isset($_SESSION['admin'])) {
  header("Location: admin_login.php");
  exit;
}
include 'db.php';

$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $title = $conn->real_escape_string($_POST['title']);
  $slug = $conn->real_escape_string($_POST['slug']);
  $content = $conn->real_escape_string($_POST['content']);
  $category = $conn->real_escape_string($_POST['category']);
  $seo_title = $conn->real_escape_string($_POST['seo_title'] ?? '');
  $seo_description = $conn->real_escape_string($_POST['seo_description'] ?? '');
  $tags = $conn->real_escape_string($_POST['tags'] ?? '');
  $status = $_POST['status'] ?? 'draft';
  $author = $_SESSION['admin'] ?? 'unknown';

  // Validate required fields
  if (empty($title)) {
    $error_message = "❌ Title is required.";
  } elseif (empty($slug)) {
    $error_message = "❌ URL slug is required.";
  } elseif (empty($content)) {
    $error_message = "❌ Content is required.";
  } elseif (empty($category)) {
    $error_message = "❌ Category is required.";
  }

  if (!$error_message) {
    $stmt = $conn->prepare("INSERT INTO blogs (title, slug, content, category, author, seo_title, seo_description, tags, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    if (!$stmt) {
      $error_message = "❌ Database prepare error: " . $conn->error;
    } else {
      $stmt->bind_param("sssssssss", $title, $slug, $content, $category, $author, $seo_title, $seo_description, $tags, $status);

      if ($stmt->execute()) {
        $success_message = "✅ Blog post " . ($status === 'published' ? 'published' : 'saved as draft') . " successfully!";
      } else {
        $error_message = "❌ Error: " . $stmt->error;
      }
    }
  }
}

// Fetch categories
$categories = $conn->query("SELECT name FROM blog_categories ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Add New Blog Post - Simple</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    body { font-family: Arial, sans-serif; max-width: 800px; margin: 20px auto; padding: 20px; }
    .form-group { margin-bottom: 15px; }
    label { display: block; margin-bottom: 5px; font-weight: bold; }
    input, textarea, select { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
    textarea { height: 200px; }
    button { padding: 10px 20px; margin: 5px; border: none; border-radius: 4px; cursor: pointer; }
    .btn-draft { background: #6c757d; color: white; }
    .btn-publish { background: #28a745; color: white; }
    .alert { padding: 15px; margin: 10px 0; border-radius: 4px; }
    .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
  </style>
</head>
<body>

<h1>Add New Blog Post - Simple Form</h1>

<?php if ($success_message): ?>
  <div class="alert alert-success"><?= $success_message ?></div>
<?php endif; ?>

<?php if ($error_message): ?>
  <div class="alert alert-error"><?= $error_message ?></div>
<?php endif; ?>

<form method="POST">
  <div class="form-group">
    <label>Title *</label>
    <input type="text" name="title" required placeholder="Enter blog title">
  </div>

  <div class="form-group">
    <label>URL Slug *</label>
    <input type="text" name="slug" required placeholder="url-friendly-slug">
  </div>

  <div class="form-group">
    <label>Content *</label>
    <textarea name="content" required placeholder="Write your blog content here..."></textarea>
  </div>

  <div class="form-group">
    <label>Category *</label>
    <select name="category" required>
      <option value="">Select Category</option>
      <?php foreach ($categories as $cat): ?>
        <option value="<?= htmlspecialchars($cat['name']) ?>"><?= htmlspecialchars($cat['name']) ?></option>
      <?php endforeach; ?>
      <option value="travel">Travel</option>
      <option value="food">Food</option>
      <option value="adventure">Adventure</option>
    </select>
  </div>

  <div class="form-group">
    <label>SEO Title</label>
    <input type="text" name="seo_title" placeholder="SEO optimized title">
  </div>

  <div class="form-group">
    <label>SEO Description</label>
    <input type="text" name="seo_description" placeholder="SEO meta description">
  </div>

  <div class="form-group">
    <label>Tags</label>
    <input type="text" name="tags" placeholder="travel, adventure, mountains">
  </div>

  <div class="form-group">
    <button type="submit" name="status" value="draft" class="btn-draft">Save as Draft</button>
    <button type="submit" name="status" value="published" class="btn-publish">Publish Blog</button>
  </div>
</form>

<p><a href="add_blog.php">Back to Original Form</a> | <a href="admin-blog.php">View All Blogs</a></p>

</body>
</html>