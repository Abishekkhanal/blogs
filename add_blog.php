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
  $seo_title = $conn->real_escape_string($_POST['seo_title']);
  $seo_description = $conn->real_escape_string($_POST['seo_description']);
  $tags = $conn->real_escape_string($_POST['tags']);
  $status = $_POST['status'] ?? 'draft';
  $author = $_SESSION['admin'];

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
    $stmt->bind_param("ssssssssss", $title, $slug, $content, $category, $image, $author, $seo_title, $seo_description, $tags, $status);

    if ($stmt->execute()) {
      $success_message = "✅ Blog post " . ($status === 'published' ? 'published' : 'saved as draft') . " successfully!";
    } else {
      $error_message = "❌ Error: " . $stmt->error;
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
  <script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/7/tinymce.min.js" referrerpolicy="origin"></script>
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
            
            <!-- Editor Selection -->
            <div class="editor-selector">
              <div class="editor-tabs">
                <button type="button" class="editor-tab active" onclick="switchEditor('tinymce')">
                  <i class="fas fa-feather-alt"></i> TinyMCE
                </button>
                <button type="button" class="editor-tab" onclick="switchEditor('ckeditor')">
                  <i class="fas fa-pen-nib"></i> CKEditor 5
                </button>
                <button type="button" class="editor-tab" onclick="switchEditor('plain')">
                  <i class="fas fa-code"></i> Plain Text
                </button>
              </div>
            </div>
            
            <!-- TinyMCE Editor -->
            <div id="tinymce-container" class="editor-container">
              <textarea id="tinymce-editor" name="content" required class="form-textarea content-editor" 
                        placeholder="Write your engaging blog content here..."></textarea>
            </div>
            
            <!-- CKEditor 5 Container -->
            <div id="ckeditor-container" class="editor-container" style="display: none;">
              <div id="ckeditor-editor"></div>
              <textarea id="ckeditor-content" name="content_ckeditor" style="display: none;"></textarea>
            </div>
            
            <!-- Plain Text Editor -->
            <div id="plain-container" class="editor-container" style="display: none;">
              <textarea id="plain-editor" name="content_plain" class="form-textarea content-editor" 
                        placeholder="Write your blog content here using HTML tags..."></textarea>
            </div>
            
            <div class="form-help">
              <i class="fas fa-info-circle"></i>
              Choose your preferred editor above. TinyMCE and CKEditor provide rich text editing with formatting tools.
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
        </div>
      </form>
    </div>

    <div class="submit-section">
      <button type="submit" form="blogForm" name="status" value="draft" class="btn-submit btn-draft">
        <i class="fas fa-save"></i>
        Save as Draft
      </button>
      <button type="submit" form="blogForm" name="status" value="published" class="btn-submit btn-publish">
        <i class="fas fa-rocket"></i>
        Publish Blog
      </button>
    </div>
  </div>
</div>

<script>
  // Character counters
  function setupCharacterCounter(inputId, counterId, maxLength) {
    const input = document.getElementById(inputId);
    const counter = document.getElementById(counterId);
    
    input.addEventListener('input', function() {
      const length = this.value.length;
      counter.textContent = length;
      counter.parentElement.style.color = length > maxLength * 0.9 ? 'var(--danger-color)' : 'var(--text-secondary)';
    });
  }

  setupCharacterCounter('titleInput', 'titleCount', 255);
  setupCharacterCounter('seoTitleInput', 'seoTitleCount', 60);
  setupCharacterCounter('seoDescInput', 'seoDescCount', 160);

  // Auto-generate slug from title
  document.getElementById('titleInput').addEventListener('input', function() {
    const title = this.value;
    const slug = title
      .toLowerCase()
      .replace(/[^a-z0-9\s-]/g, '')
      .replace(/\s+/g, '-')
      .replace(/-+/g, '-')
      .trim('-');
    
    document.getElementById('slugInput').value = slug;
    document.getElementById('slugPreview').textContent = slug || 'your-blog-url';
  });

  // Manual slug update
  document.getElementById('slugInput').addEventListener('input', function() {
    document.getElementById('slugPreview').textContent = this.value || 'your-blog-url';
  });

  // Auto-generate SEO title from main title
  document.getElementById('titleInput').addEventListener('input', function() {
    const seoTitleInput = document.getElementById('seoTitleInput');
    if (!seoTitleInput.value) {
      seoTitleInput.value = this.value;
      setupCharacterCounter('seoTitleInput', 'seoTitleCount', 60);
      document.getElementById('seoTitleCount').textContent = this.value.length;
    }
  });

  // Image preview
  document.getElementById('imageInput').addEventListener('change', function() {
    const file = this.files[0];
    const preview = document.getElementById('imagePreview');
    
    if (file) {
      const reader = new FileReader();
      reader.onload = function(e) {
        preview.innerHTML = `
          <div style="display: flex; align-items: center; gap: 1rem;">
            <img src="${e.target.result}" style="width: 100px; height: 60px; object-fit: cover; border-radius: 6px;">
            <div>
              <div style="font-weight: 600;">${file.name}</div>
              <div style="font-size: 0.85rem; color: var(--text-secondary);">
                ${(file.size / 1024 / 1024).toFixed(2)} MB
              </div>
            </div>
            <button type="button" onclick="clearImage()" style="margin-left: auto; background: var(--danger-color); color: white; border: none; padding: 0.5rem; border-radius: 4px; cursor: pointer;">
              <i class="fas fa-times"></i>
            </button>
          </div>
        `;
        preview.style.display = 'block';
      };
      reader.readAsDataURL(file);
    }
  });

  function clearImage() {
    document.getElementById('imageInput').value = '';
    document.getElementById('imagePreview').style.display = 'none';
  }

  // Form submission loading states
  document.querySelectorAll('.btn-submit').forEach(button => {
    button.addEventListener('click', function() {
      const form = document.getElementById('blogForm');
      
      // Basic validation
      if (!form.checkValidity()) {
        form.reportValidity();
        return;
      }

      const originalContent = this.innerHTML;
      this.innerHTML = '<div class="spinner"></div> Processing...';
      this.disabled = true;

      // Re-enable after timeout as fallback
      setTimeout(() => {
        this.innerHTML = originalContent;
        this.disabled = false;
      }, 5000);
    });
  });

  // Keyboard shortcuts
  document.addEventListener('keydown', function(e) {
    // Ctrl/Cmd + S for save as draft
    if ((e.ctrlKey || e.metaKey) && e.key === 's') {
      e.preventDefault();
      document.querySelector('.btn-draft').click();
    }
    
    // Ctrl/Cmd + Enter for publish
    if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
      e.preventDefault();
      document.querySelector('.btn-publish').click();
    }
  });

  // Auto-save draft functionality (could be enhanced)
  let autoSaveTimer;
  function startAutoSave() {
    clearTimeout(autoSaveTimer);
    autoSaveTimer = setTimeout(() => {
      // This could save draft automatically
      console.log('Auto-save would trigger here');
    }, 30000); // 30 seconds
  }

  document.querySelectorAll('input, textarea, select').forEach(element => {
    element.addEventListener('input', startAutoSave);
  });

  // Rich Text Editors
  let tinymceEditor = null;
  let ckeditorEditor = null;
  let currentEditor = 'tinymce';

  // Initialize TinyMCE
  function initTinyMCE() {
    if (tinymceEditor) {
      tinymce.remove('#tinymce-editor');
    }
    
    tinymce.init({
      selector: '#tinymce-editor',
      height: 400,
      menubar: true,
      plugins: [
        'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
        'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
        'insertdatetime', 'media', 'table', 'help', 'wordcount'
      ],
      toolbar: 'undo redo | blocks | bold italic forecolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat | help',
      content_style: 'body { font-family: Inter, Arial, sans-serif; font-size: 14px; line-height: 1.6; }',
      setup: function(editor) {
        tinymceEditor = editor;
      }
    });
  }

  // Initialize CKEditor 5
  function initCKEditor() {
    if (ckeditorEditor) {
      ckeditorEditor.destroy();
    }
    
    ClassicEditor
      .create(document.querySelector('#ckeditor-editor'), {
        toolbar: {
          items: [
            'heading', '|',
            'bold', 'italic', 'link', '|',
            'bulletedList', 'numberedList', '|',
            'outdent', 'indent', '|',
            'imageUpload', 'blockQuote', 'insertTable', '|',
            'undo', 'redo'
          ]
        },
        language: 'en',
        image: {
          toolbar: [
            'imageTextAlternative',
            'imageStyle:full',
            'imageStyle:side'
          ]
        },
        table: {
          contentToolbar: [
            'tableColumn',
            'tableRow',
            'mergeTableCells'
          ]
        }
      })
      .then(editor => {
        ckeditorEditor = editor;
        // Set min height
        editor.editing.view.change(writer => {
          writer.setStyle('min-height', '300px', editor.editing.view.document.getRoot());
        });
      })
      .catch(error => {
        console.error('CKEditor initialization error:', error);
      });
  }

  // Switch between editors
  function switchEditor(editorType) {
    // Update tab states
    document.querySelectorAll('.editor-tab').forEach(tab => {
      tab.classList.remove('active');
    });
    event.target.closest('.editor-tab').classList.add('active');

    // Get current content
    let currentContent = '';
    if (currentEditor === 'tinymce' && tinymceEditor) {
      currentContent = tinymceEditor.getContent();
    } else if (currentEditor === 'ckeditor' && ckeditorEditor) {
      currentContent = ckeditorEditor.getData();
    } else if (currentEditor === 'plain') {
      currentContent = document.getElementById('plain-editor').value;
    }

    // Hide all containers
    document.getElementById('tinymce-container').style.display = 'none';
    document.getElementById('ckeditor-container').style.display = 'none';
    document.getElementById('plain-container').style.display = 'none';

    // Show selected container and set content
    if (editorType === 'tinymce') {
      document.getElementById('tinymce-container').style.display = 'block';
      setTimeout(() => {
        if (tinymceEditor) {
          tinymceEditor.setContent(currentContent);
        } else {
          initTinyMCE();
          setTimeout(() => {
            if (tinymceEditor) tinymceEditor.setContent(currentContent);
          }, 500);
        }
      }, 100);
    } else if (editorType === 'ckeditor') {
      document.getElementById('ckeditor-container').style.display = 'block';
      setTimeout(() => {
        if (ckeditorEditor) {
          ckeditorEditor.setData(currentContent);
        } else {
          initCKEditor();
          setTimeout(() => {
            if (ckeditorEditor) ckeditorEditor.setData(currentContent);
          }, 500);
        }
      }, 100);
    } else if (editorType === 'plain') {
      document.getElementById('plain-container').style.display = 'block';
      document.getElementById('plain-editor').value = currentContent;
    }

    currentEditor = editorType;
    updateFormSubmission();
  }

  // Update form submission to use correct content
  function updateFormSubmission() {
    const form = document.querySelector('form');
    const originalSubmit = form.onsubmit;
    
    form.onsubmit = function(e) {
      // Get content from active editor
      let content = '';
      if (currentEditor === 'tinymce' && tinymceEditor) {
        content = tinymceEditor.getContent();
        document.getElementById('tinymce-editor').value = content;
      } else if (currentEditor === 'ckeditor' && ckeditorEditor) {
        content = ckeditorEditor.getData();
        document.getElementById('ckeditor-content').value = content;
        // Update main content field
        document.getElementById('tinymce-editor').value = content;
      } else if (currentEditor === 'plain') {
        content = document.getElementById('plain-editor').value;
        document.getElementById('tinymce-editor').value = content;
      }
      
      return originalSubmit ? originalSubmit.call(this, e) : true;
    };
  }

  // Initialize editors when page loads
  window.addEventListener('load', function() {
    initTinyMCE();
    updateFormSubmission();
  });

  // Show helpful tips
  setTimeout(() => {
    if (!localStorage.getItem('blogTipsShown')) {
      alert('💡 Pro Tips:\n\n• Use Ctrl+S to save as draft\n• Use Ctrl+Enter to publish\n• SEO title should be under 60 characters\n• SEO description should be under 160 characters\n• Use relevant tags for better discoverability\n• Switch between TinyMCE, CKEditor, and Plain Text for different editing experiences');
      localStorage.setItem('blogTipsShown', 'true');
    }
  }, 3000);
</script>

</body>
</html>