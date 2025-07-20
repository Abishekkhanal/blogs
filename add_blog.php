<?php
session_start();
if (!isset($_SESSION['admin'])) {
  header("Location: admin_login.php");
  exit;
}
include 'db.php';

// ============================================================================
// RICH TEXT EDITOR API CONFIGURATION
// ============================================================================
// TinyMCE API Key (Optional - for premium features)
// Get your free API key from: https://www.tiny.cloud/
// Replace 'YOUR_TINYMCE_API_KEY' in the script tag below with your actual key
$TINYMCE_API_KEY = 'YOUR_TINYMCE_API_KEY'; // Change this to your actual API key

// CKEditor 5 - No API key needed for basic version
// For premium CKEditor features, visit: https://ckeditor.com/pricing/
// ============================================================================

$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {

  
  $title = $conn->real_escape_string($_POST['title']);
  $slug = $conn->real_escape_string($_POST['slug']);
  $content = $conn->real_escape_string($_POST['content']);
  $category = $conn->real_escape_string($_POST['category']);
  $seo_title = $conn->real_escape_string($_POST['seo_title']);
  $seo_description = $conn->real_escape_string($_POST['seo_description']);
  $tags = $conn->real_escape_string($_POST['tags']);
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

  $image = '';
  if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
    $targetDir = "uploads/";
    if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
    $fileName = time() . '_' . basename($_FILES["image"]["name"]);
    $targetFile = $targetDir . $fileName;
    if (move_uploaded_file($_FILES["image"]["tmp_name"], $targetFile)) {
      $image = $fileName;
    } else {
      $error_message = "❌ Failed to upload image.";
    }
  }

  if (!$error_message) {
    $stmt = $conn->prepare("INSERT INTO blogs (title, slug, content, category, image, author, seo_title, seo_description, tags, status)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    if (!$stmt) {
      $error_message = "❌ Database prepare error: " . $conn->error;
    } else {
      $stmt->bind_param("ssssssssss", $title, $slug, $content, $category, $image, $author, $seo_title, $seo_description, $tags, $status);

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
  <meta charset="UTF-8" />
  <title>Add New Blog Post - Admin Panel</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  
  <!-- Rich Text Editors -->
  <!-- TinyMCE: Get your free API key from https://www.tiny.cloud/ -->
  <script src="https://cdn.tiny.cloud/1/<?= $TINYMCE_API_KEY ?>/tinymce/7/tinymce.min.js" referrerpolicy="origin"></script>
  <!-- CKEditor 5: No API key needed for basic version -->
  <script src="https://cdn.ckeditor.com/ckeditor5/40.1.0/classic/ckeditor.js"></script>
  <style>
    :root {
      --primary-color: #2563eb;
      --primary-hover: #1d4ed8;
      --secondary-color: #f8fafc;
      --text-primary: #1e293b;
      --text-secondary: #64748b;
      --border-color: #e2e8f0;
      --success-color: #059669;
      --warning-color: #d97706;
      --danger-color: #dc2626;
      --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
      --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
      --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
      --border-radius: 12px;
      --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Inter', sans-serif;
      background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
      color: var(--text-primary);
      line-height: 1.6;
      min-height: 100vh;
    }

    .admin-header {
      background: white;
      box-shadow: var(--shadow-sm);
      padding: 1rem 0;
      margin-bottom: 2rem;
      position: sticky;
      top: 0;
      z-index: 100;
    }

    .header-content {
      max-width: 1000px;
      margin: 0 auto;
      padding: 0 1rem;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .admin-title {
      font-family: 'Playfair Display', serif;
      font-size: 1.75rem;
      font-weight: 600;
      color: var(--text-primary);
      display: flex;
      align-items: center;
      gap: 0.75rem;
    }

    .header-actions {
      display: flex;
      gap: 1rem;
      align-items: center;
    }

    .btn {
      padding: 0.75rem 1.5rem;
      border: none;
      border-radius: 8px;
      font-weight: 600;
      cursor: pointer;
      transition: var(--transition);
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      font-size: 0.9rem;
    }

    .btn-secondary {
      background: var(--secondary-color);
      color: var(--text-secondary);
      border: 1px solid var(--border-color);
    }

    .btn:hover {
      transform: translateY(-2px);
      box-shadow: var(--shadow-md);
    }

    .main-container {
      max-width: 1000px;
      margin: 0 auto;
      padding: 0 1rem;
    }

    .form-container {
      background: white;
      border-radius: var(--border-radius);
      box-shadow: var(--shadow-lg);
      overflow: hidden;
    }

    .form-header {
      background: linear-gradient(135deg, var(--primary-color), var(--primary-hover));
      color: white;
      padding: 2rem;
      text-align: center;
    }

    .form-header h2 {
      font-family: 'Playfair Display', serif;
      font-size: 2rem;
      margin-bottom: 0.5rem;
    }

    .form-header p {
      opacity: 0.9;
      font-size: 1.1rem;
    }

    .form-content {
      padding: 2.5rem;
    }

    .alert {
      padding: 1rem 1.5rem;
      border-radius: 8px;
      margin-bottom: 2rem;
      display: flex;
      align-items: center;
      gap: 0.75rem;
      font-weight: 500;
    }

    .alert-success {
      background: rgba(5, 150, 105, 0.1);
      color: var(--success-color);
      border: 1px solid rgba(5, 150, 105, 0.2);
    }

    .alert-error {
      background: rgba(220, 38, 38, 0.1);
      color: var(--danger-color);
      border: 1px solid rgba(220, 38, 38, 0.2);
    }

    .form-grid {
      display: grid;
      gap: 2rem;
    }

    .form-row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 1.5rem;
    }

    .form-group {
      display: flex;
      flex-direction: column;
    }

    .form-group.full-width {
      grid-column: 1 / -1;
    }

    .form-label {
      font-weight: 600;
      color: var(--text-primary);
      margin-bottom: 0.5rem;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    .required {
      color: var(--danger-color);
      font-size: 0.9rem;
    }

    .form-input,
    .form-textarea,
    .form-select {
      width: 100%;
      padding: 1rem;
      border: 2px solid var(--border-color);
      border-radius: 8px;
      font-size: 1rem;
      transition: var(--transition);
      background: white;
      font-family: inherit;
    }

    .form-input:focus,
    .form-textarea:focus,
    .form-select:focus {
      outline: none;
      border-color: var(--primary-color);
      box-shadow: 0 0 0 3px rgb(37 99 235 / 0.1);
    }

    .form-textarea {
      resize: vertical;
      min-height: 200px;
    }

    .form-textarea.content-editor {
      min-height: 300px;
      font-family: 'Georgia', serif;
      font-size: 1.05rem;
      line-height: 1.8;
    }

    /* Rich Text Editors */
    .editor-selector {
      margin-bottom: 1rem;
    }

    .editor-tabs {
      display: flex;
      gap: 0.5rem;
      margin-bottom: 1rem;
    }

    .editor-tab {
      padding: 0.75rem 1.5rem;
      border: 2px solid var(--border-color);
      background: white;
      color: var(--text-secondary);
      border-radius: 8px;
      cursor: pointer;
      font-weight: 500;
      transition: var(--transition);
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    .editor-tab:hover {
      background: var(--bg-secondary);
      border-color: var(--primary-color);
      color: var(--text-primary);
    }

    .editor-tab.active {
      background: var(--primary-color);
      color: white;
      border-color: var(--primary-color);
    }

    .editor-container {
      border: 2px solid var(--border-color);
      border-radius: 8px;
      overflow: hidden;
    }

    /* CKEditor 5 Styling */
    .ck-editor__editable {
      min-height: 300px !important;
    }

    .ck.ck-editor {
      border: none !important;
    }

    .ck.ck-editor__main > .ck-editor__editable {
      border: none !important;
      border-radius: 0 !important;
    }

    /* TinyMCE Styling */
    .tox .tox-editor-header {
      border-bottom: 1px solid var(--border-color) !important;
    }

    .tox .tox-edit-area {
      border: none !important;
    }

    .form-help {
      font-size: 0.875rem;
      color: var(--text-secondary);
      margin-top: 0.5rem;
      display: flex;
      align-items: center;
      gap: 0.3rem;
    }

    .file-input-wrapper {
      position: relative;
      overflow: hidden;
      display: inline-block;
      width: 100%;
    }

    .file-input {
      position: absolute;
      left: -9999px;
    }

    .file-input-button {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 0.75rem;
      padding: 1rem;
      border: 2px dashed var(--border-color);
      border-radius: 8px;
      cursor: pointer;
      transition: var(--transition);
      color: var(--text-secondary);
      background: var(--secondary-color);
    }

    .file-input-button:hover {
      border-color: var(--primary-color);
      background: rgba(37, 99, 235, 0.05);
      color: var(--primary-color);
    }

    .file-preview {
      margin-top: 1rem;
      padding: 1rem;
      background: var(--secondary-color);
      border-radius: 8px;
      display: none;
    }

    .seo-section {
      background: var(--secondary-color);
      padding: 1.5rem;
      border-radius: 8px;
      border-left: 4px solid var(--primary-color);
    }

    .seo-title {
      font-weight: 600;
      color: var(--text-primary);
      margin-bottom: 1rem;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    .submit-section {
      background: var(--secondary-color);
      padding: 2rem;
      border-top: 1px solid var(--border-color);
      display: flex;
      gap: 1rem;
      justify-content: center;
      flex-wrap: wrap;
    }

    .btn-submit {
      padding: 1rem 2rem;
      border: none;
      border-radius: 8px;
      font-weight: 600;
      cursor: pointer;
      transition: var(--transition);
      display: flex;
      align-items: center;
      gap: 0.75rem;
      font-size: 1rem;
      min-width: 160px;
      justify-content: center;
    }

    .btn-draft {
      background: var(--warning-color);
      color: white;
    }

    .btn-publish {
      background: linear-gradient(135deg, var(--success-color), #047857);
      color: white;
    }

    .btn-submit:hover {
      transform: translateY(-2px);
      box-shadow: var(--shadow-lg);
    }

    .btn-submit:disabled {
      opacity: 0.6;
      cursor: not-allowed;
      transform: none;
    }

    .character-count {
      font-size: 0.8rem;
      color: var(--text-secondary);
      text-align: right;
      margin-top: 0.3rem;
    }

    .slug-preview {
      background: var(--secondary-color);
      padding: 0.75rem;
      border-radius: 6px;
      margin-top: 0.5rem;
      font-family: monospace;
      font-size: 0.9rem;
      color: var(--text-secondary);
    }

    @media (max-width: 768px) {
      .header-content {
        flex-direction: column;
        gap: 1rem;
      }

      .admin-title {
        font-size: 1.5rem;
      }

      .form-content {
        padding: 1.5rem;
      }

      .form-row {
        grid-template-columns: 1fr;
      }

      .submit-section {
        flex-direction: column;
        align-items: center;
      }

      .btn-submit {
        width: 100%;
        max-width: 300px;
      }
    }

    /* Animation for success/error messages */
    @keyframes slideIn {
      from {
        transform: translateY(-20px);
        opacity: 0;
      }
      to {
        transform: translateY(0);
        opacity: 1;
      }
    }

    .alert {
      animation: slideIn 0.3s ease-out;
    }

    /* Loading spinner */
    .spinner {
      border: 2px solid transparent;
      border-top: 2px solid currentColor;
      border-radius: 50%;
      width: 16px;
      height: 16px;
      animation: spin 1s linear infinite;
    }

    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }
  </style>
</head>
<body>

<header class="admin-header">
  <div class="header-content">
    <h1 class="admin-title">
      <i class="fas fa-pen-nib"></i>
      Create New Blog Post
    </h1>
    <div class="header-actions">
      <a href="admin-blog.php" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Back to Blog Management
      </a>
    </div>
  </div>
</header>

<div class="main-container">
  <div class="form-container">
    <div class="form-header">
      <h2>Share Your Story</h2>
      <p>Create engaging content that inspires travelers to explore</p>
    </div>

    <div class="form-content">
      <?php if ($success_message): ?>
        <div class="alert alert-success">
          <i class="fas fa-check-circle"></i>
          <?= $success_message ?>
        </div>
      <?php endif; ?>

      <?php if ($error_message): ?>
        <div class="alert alert-error">
          <i class="fas fa-exclamation-circle"></i>
          <?= $error_message ?>
        </div>
      <?php endif; ?>

      <form method="POST" enctype="multipart/form-data" id="blogForm">
        <div class="form-grid">
          <!-- Basic Information -->
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">
                <i class="fas fa-heading"></i>
                Blog Title <span class="required">*</span>
              </label>
              <input type="text" name="title" required class="form-input" id="titleInput" 
                     placeholder="Enter an engaging title..." maxlength="255">
              <div class="character-count">
                <span id="titleCount">0</span>/255 characters
              </div>
            </div>

            <div class="form-group">
              <label class="form-label">
                <i class="fas fa-link"></i>
                URL Slug <span class="required">*</span>
              </label>
              <input type="text" name="slug" required class="form-input" id="slugInput" 
                     placeholder="url-friendly-slug">
              <div class="slug-preview">
                Preview: <span id="slugPreview">your-blog-url</span>
              </div>
            </div>
          </div>

          <!-- Content -->
          <div class="form-group full-width">
            <label class="form-label">
              <i class="fas fa-edit"></i>
              Blog Content <span class="required">*</span>
            </label>
            
            <!-- Simple Content Editor -->
            <textarea name="content" required class="form-textarea content-editor" 
                      placeholder="Write your engaging blog content here..."></textarea>
            
            <div class="form-help">
              <i class="fas fa-info-circle"></i>
              Use HTML tags for formatting: &lt;b&gt;, &lt;p&gt;, &lt;ul&gt;, &lt;li&gt;, etc.
            </div>
          </div>

          <!-- Image and Category -->
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">
                <i class="fas fa-image"></i>
                Featured Image
              </label>
              <div class="file-input-wrapper">
                <input type="file" name="image" accept="image/*" class="file-input" id="imageInput">
                <label for="imageInput" class="file-input-button">
                  <i class="fas fa-cloud-upload-alt"></i>
                  Choose Image
                </label>
              </div>
              <div class="file-preview" id="imagePreview"></div>
              <div class="form-help">
                <i class="fas fa-info-circle"></i>
                Recommended: 1200x630px, JPG or PNG, max 2MB
              </div>
            </div>

            <div class="form-group">
              <label class="form-label">
                <i class="fas fa-folder"></i>
                Category <span class="required">*</span>
              </label>
              <select name="category" required class="form-select">
                <option value="">Select Category</option>
                <?php foreach ($categories as $cat): ?>
                  <option value="<?= htmlspecialchars($cat['name']) ?>">
                    <?= htmlspecialchars($cat['name']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <!-- SEO Section -->
          <div class="form-group full-width">
            <div class="seo-section">
              <h3 class="seo-title">
                <i class="fas fa-search"></i>
                SEO Optimization
              </h3>

              <div class="form-group" style="margin-bottom: 1.5rem;">
                <label class="form-label">SEO Title</label>
                <input type="text" name="seo_title" class="form-input" id="seoTitleInput"
                       placeholder="Optimized title for search engines..." maxlength="60">
                <div class="character-count">
                  <span id="seoTitleCount">0</span>/60 characters (optimal for search results)
                </div>
              </div>

              <div class="form-group" style="margin-bottom: 1.5rem;">
                <label class="form-label">SEO Description</label>
                <textarea name="seo_description" class="form-textarea" rows="3" id="seoDescInput"
                          placeholder="Brief description for search engine results..." maxlength="160"></textarea>
                <div class="character-count">
                  <span id="seoDescCount">0</span>/160 characters (optimal for search results)
                </div>
              </div>

              <div class="form-group">
                <label class="form-label">Tags</label>
                <input type="text" name="tags" class="form-input" 
                       placeholder="sikkim, travel, adventure, mountains (comma-separated)">
                <div class="form-help">
                  <i class="fas fa-tags"></i>
                  Use relevant keywords separated by commas for better SEO
                </div>
              </div>
            </div>
          </div>
          
          <!-- Submit Buttons Inside Form -->
          <div class="submit-section">
            <button type="submit" name="status" value="draft" class="btn-submit btn-draft">
              <i class="fas fa-save"></i>
              Save as Draft
            </button>
            <button type="submit" name="status" value="published" class="btn-submit btn-publish">
              <i class="fas fa-rocket"></i>
              Publish Blog
            </button>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>



<script>
  // Character counters removed to fix form submission

  // Auto-generate functions removed to fix form submission

  // Image preview removed to fix form submission

  // All form interference removed

  // Minimal JavaScript - no interference
  console.log('Blog form loaded - basic functionality');
</script>



</body>
</html>