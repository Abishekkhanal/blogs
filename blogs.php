<?php
include 'db.php';

// Search functionality
$searchTerm = $_GET['search'] ?? '';
$categoryFilter = $_GET['category'] ?? '';

// Build search query
$whereClause = "WHERE status='published'";
$params = [];
$types = "";

if ($searchTerm) {
  $whereClause .= " AND (title LIKE ? OR content LIKE ? OR tags LIKE ?)";
  $searchParam = '%' . $searchTerm . '%';
  $params = array_merge($params, [$searchParam, $searchParam, $searchParam]);
  $types .= "sss";
}

if ($categoryFilter) {
  $whereClause .= " AND category = ?";
  $params[] = $categoryFilter;
  $types .= "s";
}

// Fetch all published blogs
$sql = "SELECT * FROM blogs $whereClause ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);

if ($params) {
  $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

$blogs = [];
while ($blog = $result->fetch_assoc()) {
  $blogs[] = $blog;
}

// Fetch categories for filter
$categories = $conn->query("SELECT DISTINCT category FROM blogs WHERE status='published' ORDER BY category")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Travel Blog - Anugra Tours & Travels</title>
  <link rel="stylesheet" href="styles.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <style>
    /* Modern Blog Listing Styles */
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

    .blog-hero {
      background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-hover) 100%);
      color: white;
      padding: 4rem 0;
      text-align: center;
      margin-bottom: 3rem;
    }

    .hero-content {
      max-width: 800px;
      margin: 0 auto;
      padding: 0 1rem;
    }

    .hero-title {
      font-family: 'Playfair Display', serif;
      font-size: clamp(2.5rem, 5vw, 4rem);
      font-weight: 600;
      margin-bottom: 1rem;
      text-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .hero-subtitle {
      font-size: 1.25rem;
      opacity: 0.9;
      max-width: 600px;
      margin: 0 auto;
    }

    .blog-container {
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 1rem;
    }

    .blog-filters {
      background: white;
      border-radius: var(--border-radius);
      box-shadow: var(--shadow-md);
      padding: 2rem;
      margin-bottom: 3rem;
    }

    .filters-grid {
      display: grid;
      grid-template-columns: 1fr auto auto;
      gap: 1rem;
      align-items: end;
    }

    .search-group {
      position: relative;
    }

    .search-input {
      width: 100%;
      padding: 1rem 1rem 1rem 3rem;
      border: 2px solid var(--border-color);
      border-radius: 50px;
      font-size: 1rem;
      transition: var(--transition);
      background: var(--secondary-color);
    }

    .search-input:focus {
      outline: none;
      border-color: var(--primary-color);
      box-shadow: 0 0 0 3px rgb(37 99 235 / 0.1);
      background: white;
    }

    .search-icon {
      position: absolute;
      left: 1rem;
      top: 50%;
      transform: translateY(-50%);
      color: var(--text-secondary);
      font-size: 1.2rem;
    }

    .category-select {
      padding: 1rem 1.5rem;
      border: 2px solid var(--border-color);
      border-radius: 50px;
      font-size: 1rem;
      background: var(--secondary-color);
      color: var(--text-primary);
      cursor: pointer;
      transition: var(--transition);
      min-width: 200px;
    }

    .category-select:focus {
      outline: none;
      border-color: var(--primary-color);
      background: white;
    }

    .filter-btn {
      padding: 1rem 2rem;
      background: linear-gradient(135deg, var(--primary-color), var(--primary-hover));
      color: white;
      border: none;
      border-radius: 50px;
      font-weight: 600;
      cursor: pointer;
      transition: var(--transition);
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    .filter-btn:hover {
      transform: translateY(-2px);
      box-shadow: var(--shadow-lg);
    }

    .results-info {
      margin-bottom: 2rem;
      color: var(--text-secondary);
      font-size: 1.1rem;
    }

    .blogs-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
      gap: 2rem;
      margin-bottom: 3rem;
    }

    .blog-card {
      background: white;
      border-radius: var(--border-radius);
      box-shadow: var(--shadow-md);
      overflow: hidden;
      transition: var(--transition);
      position: relative;
      height: fit-content;
    }

    .blog-card:hover {
      transform: translateY(-8px);
      box-shadow: var(--shadow-lg);
    }

    .blog-image {
      width: 100%;
      height: 250px;
      object-fit: cover;
      display: block;
      transition: var(--transition);
    }

    .blog-card:hover .blog-image {
      transform: scale(1.05);
    }

    .blog-content {
      padding: 1.5rem;
    }

    .blog-category {
      display: inline-block;
      background: linear-gradient(135deg, var(--primary-color), var(--primary-hover));
      color: white;
      padding: 0.3rem 0.8rem;
      border-radius: 50px;
      font-size: 0.8rem;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      margin-bottom: 1rem;
    }

    .blog-title {
      font-family: 'Playfair Display', serif;
      font-size: 1.5rem;
      font-weight: 600;
      color: var(--text-primary);
      margin-bottom: 0.8rem;
      line-height: 1.3;
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }

    .blog-excerpt {
      color: var(--text-secondary);
      font-size: 0.95rem;
      line-height: 1.6;
      margin-bottom: 1rem;
      display: -webkit-box;
      -webkit-line-clamp: 3;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }

    .blog-meta {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 1rem;
      font-size: 0.85rem;
      color: var(--text-secondary);
    }

    .meta-item {
      display: flex;
      align-items: center;
      gap: 0.3rem;
    }

    .blog-link {
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      color: var(--primary-color);
      text-decoration: none;
      font-weight: 600;
      transition: var(--transition);
    }

    .blog-link:hover {
      color: var(--primary-hover);
      gap: 0.8rem;
    }

    .no-results {
      text-align: center;
      padding: 4rem 2rem;
      color: var(--text-secondary);
    }

    .no-results-icon {
      font-size: 4rem;
      margin-bottom: 1rem;
      opacity: 0.3;
    }

    .clear-filters {
      background: none;
      border: 2px solid var(--border-color);
      color: var(--text-secondary);
      padding: 0.5rem 1rem;
      border-radius: 50px;
      cursor: pointer;
      transition: var(--transition);
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      font-size: 0.9rem;
    }

    .clear-filters:hover {
      border-color: var(--primary-color);
      color: var(--primary-color);
    }

    @media (max-width: 768px) {
      .blog-hero {
        padding: 2rem 0;
      }

      .hero-title {
        font-size: 2rem;
      }

      .hero-subtitle {
        font-size: 1rem;
      }

      .blog-filters {
        padding: 1.5rem;
      }

      .filters-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
      }

      .category-select {
        min-width: auto;
      }

      .blogs-grid {
        grid-template-columns: 1fr;
        gap: 1.5rem;
      }

      .blog-card {
        margin: 0 0.5rem;
      }
    }

    @media (max-width: 480px) {
      .blog-container {
        padding: 0 0.5rem;
      }

      .blog-content {
        padding: 1rem;
      }

      .blog-title {
        font-size: 1.3rem;
      }

      .blog-meta {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
      }
    }

    /* Loading animation */
    .loading {
      display: none;
      text-align: center;
      padding: 2rem;
    }

    .spinner {
      border: 3px solid var(--border-color);
      border-top: 3px solid var(--primary-color);
      border-radius: 50%;
      width: 40px;
      height: 40px;
      animation: spin 1s linear infinite;
      margin: 0 auto 1rem;
    }

    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
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

<!-- Hero Section -->
<section class="blog-hero">
  <div class="hero-content">
    <h1 class="hero-title">Travel Stories & Insights</h1>
    <p class="hero-subtitle">Discover amazing destinations, travel tips, and hidden gems from the Himalayas through our curated blog posts</p>
  </div>
</section>

<div class="blog-container">
  <!-- Filters Section -->
  <div class="blog-filters">
    <form method="GET" action="">
      <div class="filters-grid">
        <div class="search-group">
          <input 
            type="text" 
            name="search" 
            placeholder="Search articles, destinations, tips..." 
            value="<?= htmlspecialchars($searchTerm) ?>"
            class="search-input"
          >
          <i class="fas fa-search search-icon"></i>
        </div>
        
        <select name="category" class="category-select">
          <option value="">All Categories</option>
          <?php foreach ($categories as $cat): ?>
            <option value="<?= htmlspecialchars($cat['category']) ?>" 
                    <?= $categoryFilter === $cat['category'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($cat['category']) ?>
            </option>
          <?php endforeach; ?>
        </select>
        
        <button type="submit" class="filter-btn">
          <i class="fas fa-filter"></i> Filter
        </button>
      </div>
    </form>
    
    <?php if ($searchTerm || $categoryFilter): ?>
      <div style="margin-top: 1rem;">
        <a href="blogs.php" class="clear-filters">
          <i class="fas fa-times"></i> Clear Filters
        </a>
      </div>
    <?php endif; ?>
  </div>

  <!-- Results Info -->
  <?php if ($searchTerm || $categoryFilter): ?>
    <div class="results-info">
      <?php 
      $resultCount = count($blogs);
      $filterText = [];
      if ($searchTerm) $filterText[] = "search: \"$searchTerm\"";
      if ($categoryFilter) $filterText[] = "category: \"$categoryFilter\"";
      ?>
      Found <?= $resultCount ?> article<?= $resultCount !== 1 ? 's' : '' ?> for <?= implode(', ', $filterText) ?>
    </div>
  <?php endif; ?>

  <!-- Loading indicator -->
  <div class="loading" id="loading">
    <div class="spinner"></div>
    <p>Loading articles...</p>
  </div>

  <!-- Blog Grid -->
  <?php if (empty($blogs)): ?>
    <div class="no-results">
      <div class="no-results-icon">
        <i class="fas fa-search"></i>
      </div>
      <h3>No articles found</h3>
      <p>Try adjusting your search criteria or browse all articles.</p>
      <?php if ($searchTerm || $categoryFilter): ?>
        <div style="margin-top: 1rem;">
          <a href="blogs.php" class="clear-filters">
            <i class="fas fa-home"></i> View All Articles
          </a>
        </div>
      <?php endif; ?>
    </div>
  <?php else: ?>
    <div class="blogs-grid">
      <?php foreach ($blogs as $blog): ?>
        <article class="blog-card">
          <?php if ($blog['image']): ?>
            <img src="uploads/<?= htmlspecialchars($blog['image']) ?>" 
                 alt="<?= htmlspecialchars($blog['title']) ?>" 
                 class="blog-image">
          <?php endif; ?>
          
          <div class="blog-content">
            <span class="blog-category"><?= htmlspecialchars($blog['category']) ?></span>
            
            <h3 class="blog-title">
              <?= htmlspecialchars($blog['title']) ?>
            </h3>
            
            <p class="blog-excerpt">
              <?= htmlspecialchars(substr(strip_tags($blog['content']), 0, 150)) ?>...
            </p>
            
            <div class="blog-meta">
              <div class="meta-item">
                <i class="fas fa-calendar-alt"></i>
                <span><?= date('M d, Y', strtotime($blog['created_at'])) ?></span>
              </div>
              <div class="meta-item">
                <i class="fas fa-user"></i>
                <span><?= htmlspecialchars($blog['author'] ?: 'Anugra Tours') ?></span>
              </div>
            </div>
            
            <a href="blog-view.php?slug=<?= urlencode($blog['slug']) ?>" class="blog-link">
              Read More <i class="fas fa-arrow-right"></i>
            </a>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<!-- Footer -->
<footer class="site-footer">
  <div class="footer-container">
    <div class="footer-logo-contact">
      <h2>Anugra Tours</h2>
      <ul class="contact-info">
        <li>📞 +91-97321 81111</li>
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
  // Back to top functionality
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

  // Search functionality enhancement
  const searchInput = document.querySelector('.search-input');
  const form = searchInput.closest('form');
  
  searchInput.addEventListener('keypress', (e) => {
    if (e.key === 'Enter') {
      e.preventDefault();
      form.submit();
    }
  });

  // Loading animation on form submit
  form.addEventListener('submit', () => {
    document.getElementById('loading').style.display = 'block';
    document.querySelector('.blogs-grid').style.opacity = '0.5';
  });

  // Smooth scroll for internal links
  document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
      e.preventDefault();
      const target = document.querySelector(this.getAttribute('href'));
      if (target) {
        target.scrollIntoView({
          behavior: 'smooth',
          block: 'start'
        });
      }
    });
  });

  // Enhanced card interactions
  document.querySelectorAll('.blog-card').forEach(card => {
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
