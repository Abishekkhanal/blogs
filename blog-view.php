<?php
ob_start();
include 'db.php';
$slug = $_GET['slug'] ?? '';
$stmt = $conn->prepare("SELECT * FROM blogs WHERE slug=? AND status='published'");
$stmt->bind_param("s", $slug);
$stmt->execute();
$post = $stmt->get_result()->fetch_assoc();
if (!$post) exit('Post not found');

$postId = $post['id'];

// Handle like messages
$likeMessage = '';
if (isset($_GET['like'])) {
    switch ($_GET['like']) {
        case 'like_success':
            $likeMessage = '<div class="like-message success"><i class="fas fa-heart"></i> Thank you for liking this post!</div>';
            break;
        case 'already_liked':
            $likeMessage = '<div class="like-message info"><i class="fas fa-info-circle"></i> You have already liked this post.</div>';
            break;
        case 'like_failed':
            $likeMessage = '<div class="like-message error"><i class="fas fa-exclamation-triangle"></i> Failed to like the post. Please try again.</div>';
            break;
    }
}

// Likes
$likesCount = $conn->query("SELECT COUNT(*) AS cnt FROM likes WHERE blog_id=$postId")->fetch_assoc()['cnt'];
$ip = $_SERVER['REMOTE_ADDR'];
$hasLikedStmt = $conn->prepare("SELECT id FROM likes WHERE blog_id=? AND user_ip=?");
$hasLikedStmt->bind_param("is", $postId, $ip);
$hasLikedStmt->execute();
$hasLiked = $hasLikedStmt->get_result()->num_rows > 0;

// Handle comments & replies
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment'])) {
    $cmt = trim($_POST['comment']);
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $parent_id = isset($_POST['parent_id']) && $_POST['parent_id'] !== '' ? (int)$_POST['parent_id'] : null;
    $st = $conn->prepare("INSERT INTO blog_comments (blog_id, name, email, comment, parent_id) VALUES (?, ?, ?, ?, ?)");
    $st->bind_param("isssi", $postId, $name, $email, $cmt, $parent_id);
    $st->execute();
}

// Fetch all comments
$allComments = [];
$result = $conn->prepare("SELECT * FROM blog_comments WHERE blog_id=? ORDER BY created_at ASC");
$result->bind_param("i", $postId);
$result->execute();
$res = $result->get_result();
while ($row = $res->fetch_assoc()) {
    $parent = $row['parent_id'] ?? null;
    $allComments[$parent][] = $row;
}
function renderComments($comments, $allComments) {
    foreach ($comments as $comment) {
        echo "<div class='comment-card'>";
        echo "<div class='comment-header'>";
        echo "<div class='comment-avatar'><i class='fas fa-user-circle'></i></div>";
        echo "<div class='comment-info'>";
        echo "<strong class='comment-author'>" . htmlspecialchars($comment['name']) . "</strong>";
        echo "<span class='comment-date'>" . date('M d, Y', strtotime($comment['created_at'])) . "</span>";
        echo "</div></div>";
        echo "<p class='comment-text'>" . htmlspecialchars($comment['comment']) . "</p>";
        echo "<button class='reply-btn' data-id='{$comment['id']}'><i class='fas fa-reply'></i> Reply</button>";
        echo "<div class='reply-form-container' id='reply-form-{$comment['id']}' style='display:none;'>
                <form method='POST' class='reply-form'>
                  <input type='hidden' name='parent_id' value='{$comment['id']}'>
                  <div class='form-row'>
                    <input name='name' placeholder='Your Name' required class='form-input'>
                    <input type='email' name='email' placeholder='Email (optional)' class='form-input'>
                  </div>
                  <textarea name='comment' rows='3' placeholder='Write your reply...' required class='form-textarea'></textarea>
                  <button type='submit' class='btn-submit'><i class='fas fa-paper-plane'></i> Submit Reply</button>
                </form>
              </div>";
        if (isset($allComments[$comment['id']])) {
            echo "<div class='nested-comments'>";
            renderComments($allComments[$comment['id']], $allComments);
            echo "</div>";
        }
        echo "</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?= htmlspecialchars($post['title']) ?> | Anugra Tours</title>
  <link rel="stylesheet" href="styles.css" />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <style>
    /* Modern Blog View Styles */
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
      line-height: 1.6;
      color: var(--text-primary);
      background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
      min-height: 100vh;
    }

    .blog-container {
      max-width: 900px;
      margin: 2rem auto;
      padding: 0 1rem;
    }

    .blog-header {
      background: white;
      border-radius: var(--border-radius);
      box-shadow: var(--shadow-lg);
      overflow: hidden;
      margin-bottom: 2rem;
    }

    .blog-image {
      width: 100%;
      height: 400px;
      object-fit: cover;
      display: block;
    }

    .blog-content-header {
      padding: 2rem;
    }

    .blog-title {
      font-family: 'Playfair Display', serif;
      font-size: clamp(1.8rem, 4vw, 2.5rem);
      font-weight: 600;
      color: var(--text-primary);
      margin-bottom: 1rem;
      line-height: 1.3;
    }

    .blog-meta {
      display: flex;
      align-items: center;
      gap: 1.5rem;
      font-size: 0.9rem;
      color: var(--text-secondary);
      margin-bottom: 1.5rem;
      flex-wrap: wrap;
    }

    .meta-item {
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    .meta-item i {
      color: var(--primary-color);
    }

    .blog-content {
      background: white;
      border-radius: var(--border-radius);
      box-shadow: var(--shadow-md);
      padding: 2.5rem;
      margin-bottom: 2rem;
      font-size: 1.1rem;
      line-height: 1.8;
      color: var(--text-primary);
    }

    .blog-content p {
      margin-bottom: 1.5rem;
    }

    .blog-content b, .blog-content strong {
      color: var(--text-primary);
      font-weight: 600;
    }

    .blog-content ul, .blog-content ol {
      margin: 1.5rem 0;
      padding-left: 2rem;
    }

    .blog-content li {
      margin-bottom: 0.5rem;
    }

    .engagement-section {
      background: white;
      border-radius: var(--border-radius);
      box-shadow: var(--shadow-md);
      padding: 2rem;
      margin-bottom: 2rem;
    }

    .likes-container {
      display: flex;
      align-items: center;
      gap: 1rem;
      margin-bottom: 2rem;
      padding-bottom: 2rem;
      border-bottom: 1px solid var(--border-color);
    }

    .likes-count {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      font-weight: 600;
      color: var(--text-primary);
    }

    .like-btn {
      background: linear-gradient(135deg, var(--primary-color), var(--primary-hover));
      color: white;
      border: none;
      padding: 0.75rem 1.5rem;
      border-radius: 50px;
      font-weight: 500;
      cursor: pointer;
      transition: var(--transition);
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    .like-btn:hover {
      transform: translateY(-2px);
      box-shadow: var(--shadow-lg);
    }

    .like-btn:disabled {
      background: var(--text-secondary);
      cursor: not-allowed;
      transform: none;
    }

    .social-share {
      margin-bottom: 2rem;
    }

    .social-share h3 {
      font-size: 1.25rem;
      margin-bottom: 1rem;
      color: var(--text-primary);
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    .social-links {
      display: flex;
      gap: 1rem;
      flex-wrap: wrap;
    }

    .social-link {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      padding: 0.75rem 1.5rem;
      border-radius: 50px;
      text-decoration: none;
      font-weight: 500;
      transition: var(--transition);
      color: white;
    }

    .social-link:hover {
      transform: translateY(-2px);
      box-shadow: var(--shadow-md);
    }

    .social-link.facebook { background: #1877f2; }
    .social-link.twitter { background: #1da1f2; }
    .social-link.whatsapp { background: #25d366; }
    .social-link.copy { background: var(--text-secondary); }

    .comments-section {
      background: white;
      border-radius: var(--border-radius);
      box-shadow: var(--shadow-md);
      padding: 2rem;
    }

    .comments-header {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      margin-bottom: 2rem;
      font-size: 1.5rem;
      font-weight: 600;
      color: var(--text-primary);
    }

    .comment-form {
      background: var(--secondary-color);
      padding: 2rem;
      border-radius: var(--border-radius);
      margin-bottom: 2rem;
    }

    .form-row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 1rem;
      margin-bottom: 1rem;
    }

    .form-input, .form-textarea {
      width: 100%;
      padding: 1rem;
      border: 2px solid var(--border-color);
      border-radius: 8px;
      font-size: 1rem;
      transition: var(--transition);
      background: white;
    }

    .form-input:focus, .form-textarea:focus {
      outline: none;
      border-color: var(--primary-color);
      box-shadow: 0 0 0 3px rgb(37 99 235 / 0.1);
    }

    .form-textarea {
      resize: vertical;
      min-height: 120px;
      grid-column: 1 / -1;
    }

    .btn-submit {
      background: linear-gradient(135deg, var(--success-color), #047857);
      color: white;
      border: none;
      padding: 1rem 2rem;
      border-radius: 8px;
      font-weight: 600;
      cursor: pointer;
      transition: var(--transition);
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    .btn-submit:hover {
      transform: translateY(-2px);
      box-shadow: var(--shadow-md);
    }

    .comment-card {
      background: var(--secondary-color);
      border-radius: var(--border-radius);
      padding: 1.5rem;
      margin-bottom: 1.5rem;
      border-left: 4px solid var(--primary-color);
    }

    .comment-header {
      display: flex;
      align-items: center;
      gap: 1rem;
      margin-bottom: 1rem;
    }

    .comment-avatar {
      font-size: 2rem;
      color: var(--primary-color);
    }

    .comment-author {
      font-weight: 600;
      color: var(--text-primary);
    }

    .comment-date {
      color: var(--text-secondary);
      font-size: 0.875rem;
    }

    .comment-text {
      margin-bottom: 1rem;
      line-height: 1.6;
    }

    .reply-btn {
      background: none;
      border: none;
      color: var(--primary-color);
      cursor: pointer;
      font-weight: 500;
      display: flex;
      align-items: center;
      gap: 0.5rem;
      transition: var(--transition);
    }

    .reply-btn:hover {
      color: var(--primary-hover);
    }

    .reply-form-container {
      margin-top: 1rem;
      padding-top: 1rem;
      border-top: 1px solid var(--border-color);
    }

    .nested-comments {
      margin-left: 2rem;
      margin-top: 1rem;
      padding-left: 1rem;
      border-left: 2px dashed var(--border-color);
    }

    .no-comments {
      text-align: center;
      padding: 3rem;
      color: var(--text-secondary);
      font-style: italic;
    }

    /* Like message styles */
    .like-message {
      padding: 1rem 1.5rem;
      border-radius: 8px;
      margin: 1rem 0;
      display: flex;
      align-items: center;
      gap: 0.75rem;
      font-weight: 500;
      animation: slideInDown 0.5s ease-out;
    }

    .like-message.success {
      background: rgba(5, 150, 105, 0.1);
      color: var(--success-color);
      border: 1px solid rgba(5, 150, 105, 0.2);
    }

    .like-message.info {
      background: rgba(37, 99, 235, 0.1);
      color: var(--primary-color);
      border: 1px solid rgba(37, 99, 235, 0.2);
    }

    .like-message.error {
      background: rgba(220, 38, 38, 0.1);
      color: var(--danger-color);
      border: 1px solid rgba(220, 38, 38, 0.2);
    }

    @keyframes slideInDown {
      from {
        transform: translateY(-20px);
        opacity: 0;
      }
      to {
        transform: translateY(0);
        opacity: 1;
      }
    }

    @media (max-width: 768px) {
      .blog-container {
        margin: 1rem auto;
        padding: 0 0.5rem;
      }

      .blog-content-header,
      .blog-content,
      .engagement-section,
      .comments-section {
        padding: 1.5rem;
      }

      .comment-form {
        padding: 1.5rem;
      }

      .form-row {
        grid-template-columns: 1fr;
      }

      .social-links {
        justify-content: center;
      }

      .nested-comments {
        margin-left: 1rem;
      }

      .blog-image {
        height: 250px;
      }
    }

    @media (max-width: 480px) {
      .blog-title {
        font-size: 1.5rem;
      }

      .blog-meta {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
      }

      .likes-container {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
      }

      .social-links {
        flex-direction: column;
        gap: 0.5rem;
      }
    }
  </style>
</head>
<body>

<!-- Topbar -->
<div class="topbar">
  <div class="topbar-left">
    <span><i class="fas fa-blog"></i> Our Blog</span>
    <a href="review.php" style="text-decoration: none; color: #ffff;">
      <span><i class="fas fa-star"></i> Travellers Review</span>
    </a>
  </div>
  <div class="topbar-right">
    <span><i class="fas fa-phone-alt"></i> +91-97321 81111</span>
    <span><i class="fas fa-envelope"></i> info@anugratours.com</span>
    <a href="#"><i class="fab fa-facebook-f"></i></a>
    <a href="#"><i class="fab fa-instagram"></i></a>
    <a href="#"><i class="fab fa-youtube"></i></a>
    <a class="btn-quote" href="#">Get A Quote</a>
  </div>
</div>

<!-- Navbar -->
<header class="navbar">
  <a href="index.php">
    <img src="images/aNUGRHA LOGO.jpg" alt="Anugra Tours Logo" style="height: 40px;" />
  </a>
  <input type="checkbox" id="nav-toggle" class="nav-toggle">
  <label for="nav-toggle" class="nav-toggle-label">&#9776;</label>
  <nav>
    <ul class="nav-links">
      <li><a href="index.php">HOME</a></li>
      <li><a href="explore-sikkim.html">EXPLORE SIKKIM</a></li>
      <li>
        <a href="destinations.html">DESTINATIONS</a>
        <ul class="dropdown">
          <li><a href="gangtok.html">GANGTOK</a></li>
          <li><a href="pelling.html">PELLING</a></li>
          <li><a href="darjeeling.html">DARJEELING</a></li>
          <li><a href="nathula.html">NORTH SIKKIM</a></li>
          <li><a href="namchi-chardham.html">NAMCHI CHARDHAM</a></li>
          <li><a href="lachung.html">LACHUNG YUMTHANG VALLEY</a></li>
          <li><a href="tsomgo-lake.html">TSOMGO LAKE BABA MANDIR</a></li>
        </ul>
      </li>
      <li>
        <a href="#">TOUR PACKAGES</a>
        <ul class="dropdown">
          <li><a href="gangtok.html">GANGTOK TOUR PACKAGE</a></li>
          <li><a href="pelling.html">PELLING TOUR PACKAGE</a></li>
          <li><a href="darjeeling.html">DARJEELING TOUR PACKAGE</a></li>
          <li><a href="nathula.html">NORTH SIKKIM TOUR PACKAGE</a></li>
          <li><a href="namchi.html">NAMCHI CHARDHAM TOUR PACKAGE</a></li>
          <li><a href="yumthang.html">LACHUNG YUMTHANG VALLEY TOUR PACKAGE</a></li>
          <li><a href="tsomgo-lake.html">TSOMGO LAKE BABA MANDIR TOUR PACKAGE</a></li>
        </ul>
      </li>
      <li><a href="hotels.html">HOTELS</a></li>
      <li><a href="contact.html">CONTACT</a></li>
      <li><a href="blogs.php">BLOGS</a></li>
      <li><a href="review.php">TRAVELLERS REVIEW</a></li>
      <li><a href="about.html">ABOUT US</a></li>
    </ul>
  </nav>
</header>

<div class="blog-container">
  <article class="blog-header">
    <?php if ($post['image']): ?>
      <img src="uploads/<?= htmlspecialchars($post['image']) ?>" alt="<?= htmlspecialchars($post['title']) ?>" class="blog-image">
    <?php endif; ?>
    
    <div class="blog-content-header">
      <h1 class="blog-title"><?= htmlspecialchars($post['title']) ?></h1>
      <div class="blog-meta">
        <div class="meta-item">
          <i class="fas fa-calendar-alt"></i>
          <span><?= date('M d, Y', strtotime($post['created_at'])) ?></span>
        </div>
                 <div class="meta-item">
           <i class="fas fa-user"></i>
           <span><?= $post['author'] ? 'by admin' : 'Anugra Tours' ?></span>
         </div>
        <div class="meta-item">
          <i class="fas fa-tags"></i>
          <span><?= htmlspecialchars($post['tags']) ?></span>
        </div>
      </div>
    </div>
  </article>

  <div class="blog-content">
    <?php
    $rawContent = $post['content'];
    $cleanContent = preg_replace("/\s+/", ' ', $rawContent);
    echo $cleanContent;
    ?>
  </div>

  <div class="engagement-section">
    <?= $likeMessage ?>
    <div class="likes-container">
      <div class="likes-count">
        <i class="fas fa-heart" style="color: #dc2626;"></i>
        <span><?= $likesCount ?> likes</span>
      </div>
      <?php if (!$hasLiked): ?>
        <form method="POST" action="like.php">
          <input type="hidden" name="blog_id" value="<?= (int)$post['id'] ?>">
          <input type="hidden" name="slug" value="<?= htmlspecialchars($post['slug']) ?>">
          <button type="submit" class="like-btn">
            <i class="fas fa-thumbs-up"></i> Like this post
          </button>
        </form>
      <?php else: ?>
        <button disabled class="like-btn">
          <i class="fas fa-check"></i> You liked this
        </button>
      <?php endif; ?>
    </div>

    <div class="social-share">
      <h3><i class="fas fa-share-alt"></i> Share this post</h3>
      <?php $url = "https://anugratravels.com/blog-view.php?slug=" . urlencode($post['slug']); ?>
      <div class="social-links">
        <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode($url) ?>" target="_blank" class="social-link facebook">
          <i class="fab fa-facebook-f"></i> Facebook
        </a>
        <a href="https://twitter.com/intent/tweet?url=<?= urlencode($url) ?>&text=<?= urlencode($post['title']) ?>" target="_blank" class="social-link twitter">
          <i class="fab fa-twitter"></i> Twitter
        </a>
        <a href="https://api.whatsapp.com/send?text=<?= urlencode($post['title'] . ' ' . $url) ?>" target="_blank" class="social-link whatsapp">
          <i class="fab fa-whatsapp"></i> WhatsApp
        </a>
        <a href="#" onclick="navigator.clipboard.writeText('<?= $url ?>'); alert('Link copied to clipboard!')" class="social-link copy">
          <i class="fas fa-link"></i> Copy Link
        </a>
      </div>
    </div>
  </div>

  <div class="comments-section">
    <h3 class="comments-header">
      <i class="fas fa-comments"></i> Comments
    </h3>
    
    <div class="comment-form">
      <form method="POST">
        <div class="form-row">
          <input name="name" placeholder="Your Name" required class="form-input">
          <input type="email" name="email" placeholder="Email (optional)" class="form-input">
        </div>
        <textarea name="comment" rows="4" placeholder="Share your thoughts..." required class="form-textarea"></textarea>
        <input type="hidden" name="parent_id" value="">
        <button type="submit" class="btn-submit">
          <i class="fas fa-comment-dots"></i> Post Comment
        </button>
      </form>
    </div>

    <div class="comments-list">
      <?php
      if (isset($allComments[null]) && count($allComments[null]) > 0) {
        renderComments($allComments[null], $allComments);
      } else {
        echo "<div class='no-comments'>";
        echo "<i class='fas fa-comment-slash' style='font-size: 3rem; margin-bottom: 1rem; opacity: 0.3;'></i>";
        echo "<p>No comments yet. Be the first to share your thoughts!</p>";
        echo "</div>";
      }
      ?>
    </div>
  </div>
</div>

<!-- Footer -->
<footer class="site-footer">
  <div class="footer-container">
    <div class="footer-logo-contact">
      <h2>Anugra Tours</h2>
      <ul class="contact-info">
        <li>📞 +91-97321 81111</li>
        <li>📱 +91 97321 81111</li>
        <li>📱 +91 97321 81111</li>
        <li>📱 +91 97321 81111</li>
      </ul>
    </div>

    <div class="footer-links">
      <div>
        <h4>■ COMPANY</h4>
        <ul>
          <li><a href="about.html">About Us</a></li>
          <li>Careers</li>
          <li><a href="review.php">Travellers Review</a></li>
          <li>Privacy Policy</li>
          <li><a href="contact.html">Contact Us</a></li>
        </ul>
      </div>
      <div>
        <h4>■ LINKS</h4>
        <ul>
          <li>Tourism Info</li>
          <li>Gallery</li>
          <li>Video Gallery</li>
          <li>Places of Interests</li>
          <li>Book A Trip</li>
          <li><a href="review.php">Post A Review</a></li>
          <li>Become a Partner</li>
        </ul>
      </div>
      <div>
        <h4>■ EXPLORE SIKKIM</h4>
        <ul>
          <li><a href="gangtok.html">Gangtok</a></li>
          <li><a href="darjeeling.html">Darjeeling</a></li>
          <li><a href="pelling.html">Pelling</a></li>
          <li><a href="namchi-chardham.html">Namchi Chardham</a></li>
          <li><a href="yumthang.html">North Sikkim</a></li>
          <li><a href="lachung.html">Lachung Yumthang</a></li>
        </ul>
      </div>
      <div>
        <h4>■ TOUR PACKAGES</h4>
        <ul>
          <li><a href="explore-sikkim.html">Sikkim Tour Package</a></li>
          <li><a href="darjeeling.html">Sikkim Darjeeling Tours</a></li>
          <li>Student Tours</li>
          <li>Honeymoon Tours</li>
          <li>Bhutan Packages</li>
          <li>Buddhist Tours</li>
          <li>Group Tours</li>
          <li>Offbeat Tours</li>
        </ul>
      </div>
    </div>

    <div class="govt-registration" style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #ccc;">
      <img src="images/govt-of-sikkim-logo.png" alt="Government of Sikkim Logo" style="height: 60px;" />
      <p style="margin-top: 10px; font-weight: 600; color: #ffffff;">Registered with Government of Sikkim</p>
    </div>

    <div class="footer-credit" style="text-align: center; padding: 15px 0; font-size: 14px; color: #666;">
      © 2025 Anugra Tours. All rights reserved. <br>
      Designed & Developed by <strong><a href="https://abishekkhanal.github.io/" style="color: rgb(30, 210, 30); text-decoration: none;">Abishek Khanal</a></strong>
    </div>
  </div>
</footer>

<button id="backToTop" style="display: none; position: fixed; bottom: 30px; right: 30px; z-index: 1000; background: var(--primary-color); color: white; border: none; border-radius: 50%; width: 50px; height: 50px; cursor: pointer; box-shadow: var(--shadow-lg); transition: var(--transition);">
  <i class="fas fa-arrow-up"></i>
</button>

<script src="js/script.js"></script>
<script>
  document.querySelectorAll('.reply-btn').forEach(btn => {
    btn.addEventListener('click', e => {
      e.preventDefault();
      const id = btn.dataset.id;
      document.querySelectorAll('.reply-form-container').forEach(f => f.style.display = 'none');
      const replyForm = document.getElementById('reply-form-' + id);
      replyForm.style.display = replyForm.style.display === 'block' ? 'none' : 'block';
    });
  });

  // Back to top button
  window.addEventListener('scroll', () => {
    const backToTop = document.getElementById('backToTop');
    if (window.pageYOffset > 300) {
      backToTop.style.display = 'block';
    } else {
      backToTop.style.display = 'none';
    }
  });

  document.getElementById('backToTop').addEventListener('click', () => {
    window.scrollTo({ top: 0, behavior: 'smooth' });
  });

  // Auto-hide like messages after 5 seconds
  const likeMessage = document.querySelector('.like-message');
  if (likeMessage) {
    setTimeout(() => {
      likeMessage.style.opacity = '0';
      likeMessage.style.transform = 'translateY(-20px)';
      setTimeout(() => {
        likeMessage.style.display = 'none';
      }, 300);
    }, 5000);
  }
</script>

</body>
<?php ob_end_flush(); ?>
</html>