<?php
session_start();
if (!isset($_SESSION['admin'])) {
  header("Location: admin_login.php");
  exit;
}
include 'db.php';

// Handle status toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_status'])) {
    $id = (int)$_POST['id'];
    $current_status = $_POST['current_status'];
    $new_status = $current_status === 'published' ? 'draft' : 'published';
    
    $stmt = $conn->prepare("UPDATE blogs SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $new_status, $id);
    $stmt->execute();
    
    header("Location: admin-blog.php");
    exit;
}

// Fetch blog statistics
$stats = [
    'total' => $conn->query("SELECT COUNT(*) as count FROM blogs")->fetch_assoc()['count'],
    'published' => $conn->query("SELECT COUNT(*) as count FROM blogs WHERE status='published'")->fetch_assoc()['count'],
    'draft' => $conn->query("SELECT COUNT(*) as count FROM blogs WHERE status='draft'")->fetch_assoc()['count']
];

// Fetch all blogs
$result = $conn->query("SELECT * FROM blogs ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <title>Blog Management - Admin Panel</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
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
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 1rem;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .admin-title {
      font-size: 1.5rem;
      font-weight: 700;
      color: var(--text-primary);
      display: flex;
      align-items: center;
      gap: 0.5rem;
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

    .btn-primary {
      background: linear-gradient(135deg, var(--primary-color), var(--primary-hover));
      color: white;
    }

    .btn-secondary {
      background: var(--secondary-color);
      color: var(--text-secondary);
      border: 1px solid var(--border-color);
    }

    .btn-success {
      background: var(--success-color);
      color: white;
    }

    .btn-warning {
      background: var(--warning-color);
      color: white;
    }

    .btn-danger {
      background: var(--danger-color);
      color: white;
    }

    .btn:hover {
      transform: translateY(-2px);
      box-shadow: var(--shadow-md);
    }

    .btn-sm {
      padding: 0.5rem 1rem;
      font-size: 0.8rem;
    }

    .main-container {
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 1rem;
    }

    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 1.5rem;
      margin-bottom: 2rem;
    }

    .stat-card {
      background: white;
      padding: 1.5rem;
      border-radius: var(--border-radius);
      box-shadow: var(--shadow-md);
      text-align: center;
      transition: var(--transition);
    }

    .stat-card:hover {
      transform: translateY(-4px);
      box-shadow: var(--shadow-lg);
    }

    .stat-icon {
      width: 60px;
      height: 60px;
      border-radius: 50%;
      margin: 0 auto 1rem;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.5rem;
      color: white;
    }

    .stat-icon.total { background: var(--primary-color); }
    .stat-icon.published { background: var(--success-color); }
    .stat-icon.draft { background: var(--warning-color); }

    .stat-number {
      font-size: 2rem;
      font-weight: 700;
      color: var(--text-primary);
      margin-bottom: 0.5rem;
    }

    .stat-label {
      color: var(--text-secondary);
      font-weight: 500;
    }

    .blog-table-container {
      background: white;
      border-radius: var(--border-radius);
      box-shadow: var(--shadow-md);
      overflow: hidden;
    }

    .table-header {
      padding: 1.5rem;
      border-bottom: 1px solid var(--border-color);
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .table-title {
      font-size: 1.25rem;
      font-weight: 600;
      color: var(--text-primary);
    }

    .table-responsive {
      overflow-x: auto;
    }

    .blog-table {
      width: 100%;
      border-collapse: collapse;
    }

    .blog-table th,
    .blog-table td {
      padding: 1rem;
      text-align: left;
      border-bottom: 1px solid var(--border-color);
    }

    .blog-table th {
      background: var(--secondary-color);
      font-weight: 600;
      color: var(--text-primary);
      font-size: 0.9rem;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .blog-table tr {
      transition: var(--transition);
    }

    .blog-table tr:hover {
      background: var(--secondary-color);
    }

    .blog-title-cell {
      max-width: 200px;
    }

    .blog-title-text {
      font-weight: 600;
      color: var(--text-primary);
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
      line-height: 1.4;
    }

    .blog-slug {
      color: var(--text-secondary);
      font-size: 0.85rem;
      font-family: monospace;
    }

    .status-badge {
      display: inline-flex;
      align-items: center;
      gap: 0.3rem;
      padding: 0.3rem 0.8rem;
      border-radius: 50px;
      font-size: 0.8rem;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .status-badge.published {
      background: rgba(5, 150, 105, 0.1);
      color: var(--success-color);
    }

    .status-badge.draft {
      background: rgba(217, 119, 6, 0.1);
      color: var(--warning-color);
    }

    .status-indicator {
      width: 8px;
      height: 8px;
      border-radius: 50%;
    }

    .status-indicator.published { background: var(--success-color); }
    .status-indicator.draft { background: var(--warning-color); }

    .date-cell {
      color: var(--text-secondary);
      font-size: 0.9rem;
    }

    .actions-cell {
      white-space: nowrap;
    }

    .action-buttons {
      display: flex;
      gap: 0.5rem;
      flex-wrap: wrap;
    }

    .no-blogs {
      text-align: center;
      padding: 3rem;
      color: var(--text-secondary);
    }

    .no-blogs-icon {
      font-size: 3rem;
      margin-bottom: 1rem;
      opacity: 0.3;
    }

    @media (max-width: 768px) {
      .header-content {
        flex-direction: column;
        gap: 1rem;
      }

      .header-actions {
        flex-wrap: wrap;
        justify-content: center;
      }

      .stats-grid {
        grid-template-columns: 1fr;
      }

      .table-header {
        flex-direction: column;
        gap: 1rem;
        align-items: flex-start;
      }

      .blog-table th,
      .blog-table td {
        padding: 0.75rem 0.5rem;
        font-size: 0.85rem;
      }

      .action-buttons {
        flex-direction: column;
      }

      .btn-sm {
        padding: 0.4rem 0.8rem;
        font-size: 0.75rem;
      }
    }

    /* Modal styles for confirmations */
    .modal {
      display: none;
      position: fixed;
      z-index: 1000;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0,0,0,0.5);
    }

    .modal-content {
      background-color: white;
      margin: 15% auto;
      padding: 2rem;
      border-radius: var(--border-radius);
      width: 90%;
      max-width: 400px;
      text-align: center;
    }

    .modal h3 {
      margin-bottom: 1rem;
      color: var(--text-primary);
    }

    .modal-buttons {
      display: flex;
      gap: 1rem;
      justify-content: center;
      margin-top: 1.5rem;
    }
  </style>
</head>
<body>

<header class="admin-header">
  <div class="header-content">
    <h1 class="admin-title">
      <i class="fas fa-blog"></i>
      Blog Management
    </h1>
    <div class="header-actions">
      <a href="add_blog.php" class="btn btn-primary">
        <i class="fas fa-plus"></i> Add New Blog
      </a>
      <a href="admin_panel.php" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Back to Dashboard
      </a>
    </div>
  </div>
</header>

<div class="main-container">
  <!-- Statistics -->
  <div class="stats-grid">
    <div class="stat-card">
      <div class="stat-icon total">
        <i class="fas fa-file-alt"></i>
      </div>
      <div class="stat-number"><?= $stats['total'] ?></div>
      <div class="stat-label">Total Blogs</div>
    </div>
    <div class="stat-card">
      <div class="stat-icon published">
        <i class="fas fa-eye"></i>
      </div>
      <div class="stat-number"><?= $stats['published'] ?></div>
      <div class="stat-label">Published</div>
    </div>
    <div class="stat-card">
      <div class="stat-icon draft">
        <i class="fas fa-edit"></i>
      </div>
      <div class="stat-number"><?= $stats['draft'] ?></div>
      <div class="stat-label">Drafts</div>
    </div>
  </div>

  <!-- Blog Table -->
  <div class="blog-table-container">
    <div class="table-header">
      <h2 class="table-title">All Blog Posts</h2>
      <a href="add_blog.php" class="btn btn-primary btn-sm">
        <i class="fas fa-plus"></i> New Post
      </a>
    </div>

    <?php if ($result->num_rows > 0): ?>
      <div class="table-responsive">
        <table class="blog-table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Title & Slug</th>
              <th>Status</th>
              <th>Category</th>
              <th>Created</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
              <tr>
                <td>
                  <strong>#<?= $row['id'] ?></strong>
                </td>
                <td class="blog-title-cell">
                  <div class="blog-title-text">
                    <?= htmlspecialchars($row['title']) ?>
                  </div>
                  <div class="blog-slug">
                    /<em><?= htmlspecialchars($row['slug']) ?></em>
                  </div>
                </td>
                <td>
                  <span class="status-badge <?= $row['status'] ?>">
                    <span class="status-indicator <?= $row['status'] ?>"></span>
                    <?= ucfirst($row['status']) ?>
                  </span>
                </td>
                <td>
                  <span style="padding: 0.2rem 0.6rem; background: var(--secondary-color); border-radius: 6px; font-size: 0.8rem;">
                    <?= htmlspecialchars($row['category']) ?>
                  </span>
                </td>
                <td class="date-cell">
                  <?= date('M d, Y', strtotime($row['created_at'])) ?>
                  <br>
                  <small><?= date('h:i A', strtotime($row['created_at'])) ?></small>
                </td>
                <td class="actions-cell">
                  <div class="action-buttons">
                    <a href="edit_blog.php?id=<?= $row['id'] ?>" class="btn btn-primary btn-sm">
                      <i class="fas fa-edit"></i> Edit
                    </a>
                    
                    <form method="POST" style="display: inline;">
                      <input type="hidden" name="id" value="<?= $row['id'] ?>">
                      <input type="hidden" name="current_status" value="<?= $row['status'] ?>">
                      <button type="submit" name="toggle_status" 
                              class="btn <?= $row['status'] === 'published' ? 'btn-warning' : 'btn-success' ?> btn-sm">
                        <i class="fas fa-<?= $row['status'] === 'published' ? 'eye-slash' : 'eye' ?>"></i>
                        <?= $row['status'] === 'published' ? 'Unpublish' : 'Publish' ?>
                      </button>
                    </form>
                    
                    <form method="POST" action="delete_blog.php" style="display: inline;" 
                          onsubmit="return confirm('Are you sure you want to delete this blog post? This action cannot be undone.')">
                      <input type="hidden" name="id" value="<?= $row['id'] ?>">
                      <button type="submit" class="btn btn-danger btn-sm">
                        <i class="fas fa-trash"></i> Delete
                      </button>
                    </form>
                    
                    <?php if ($row['status'] === 'published'): ?>
                      <a href="blog-view.php?slug=<?= urlencode($row['slug']) ?>" 
                         target="_blank" class="btn btn-secondary btn-sm">
                        <i class="fas fa-external-link-alt"></i> View
                      </a>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <div class="no-blogs">
        <div class="no-blogs-icon">
          <i class="fas fa-file-plus"></i>
        </div>
        <h3>No blog posts yet</h3>
        <p>Start creating your first blog post to engage with your audience.</p>
        <a href="add_blog.php" class="btn btn-primary" style="margin-top: 1rem;">
          <i class="fas fa-plus"></i> Create Your First Blog
        </a>
      </div>
    <?php endif; ?>
  </div>
</div>

<script>
  // Enhanced confirmation dialogs
  document.querySelectorAll('form[onsubmit]').forEach(form => {
    form.addEventListener('submit', function(e) {
      const confirmed = confirm(this.getAttribute('onsubmit').replace('return ', '').replace(/'/g, ''));
      if (!confirmed) {
        e.preventDefault();
      }
    });
  });

  // Add loading states to buttons
  document.querySelectorAll('form button[type="submit"]').forEach(button => {
    button.addEventListener('click', function() {
      const originalText = this.innerHTML;
      this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
      this.disabled = true;
      
      // Re-enable after 3 seconds as fallback
      setTimeout(() => {
        this.innerHTML = originalText;
        this.disabled = false;
      }, 3000);
    });
  });

  // Auto-refresh status indicators
  setInterval(() => {
    // This could be enhanced to check for real-time updates
    // For now, it's just a placeholder for future enhancement
  }, 30000);

  // Keyboard shortcuts
  document.addEventListener('keydown', function(e) {
    // Ctrl/Cmd + N for new blog
    if ((e.ctrlKey || e.metaKey) && e.key === 'n') {
      e.preventDefault();
      window.location.href = 'add_blog.php';
    }
    
    // Ctrl/Cmd + B to go back
    if ((e.ctrlKey || e.metaKey) && e.key === 'b') {
      e.preventDefault();
      window.location.href = 'admin_panel.php';
    }
  });

  // Enhanced hover effects
  document.querySelectorAll('.stat-card').forEach(card => {
    card.addEventListener('mouseenter', function() {
      this.style.transform = 'translateY(-8px) scale(1.02)';
    });
    
    card.addEventListener('mouseleave', function() {
      this.style.transform = 'translateY(0) scale(1)';
    });
  });
</script>

</body>
</html>