// Mobile Topbar Visibility Fix
document.addEventListener('DOMContentLoaded', function() {
  
  // Function to force topbar visibility
  function ensureTopbarVisible() {
    const topbar = document.querySelector('.topbar');
    
    if (topbar) {
      // Force display
      topbar.style.display = 'flex';
      topbar.style.visibility = 'visible';
      topbar.style.opacity = '1';
      topbar.style.height = 'auto';
      
      // Apply mobile styles if on mobile
      if (window.innerWidth <= 768) {
        topbar.style.flexDirection = 'column';
        topbar.style.gap = '8px';
        topbar.style.padding = '10px 12px';
        topbar.style.fontSize = '0.8rem';
        topbar.style.textAlign = 'center';
        topbar.style.minHeight = '60px';
        
        // Style topbar sections
        const topbarLeft = topbar.querySelector('.topbar-left');
        const topbarRight = topbar.querySelector('.topbar-right');
        
        if (topbarLeft) {
          topbarLeft.style.display = 'flex';
          topbarLeft.style.justifyContent = 'center';
          topbarLeft.style.alignItems = 'center';
          topbarLeft.style.flexWrap = 'wrap';
          topbarLeft.style.gap = '8px';
          topbarLeft.style.width = '100%';
        }
        
        if (topbarRight) {
          topbarRight.style.display = 'flex';
          topbarRight.style.justifyContent = 'center';
          topbarRight.style.alignItems = 'center';
          topbarRight.style.flexWrap = 'wrap';
          topbarRight.style.gap = '8px';
          topbarRight.style.width = '100%';
        }
      }
      
      console.log('Topbar visibility ensured');
    }
  }
  
  // Run immediately
  ensureTopbarVisible();
  
  // Run on window resize
  window.addEventListener('resize', ensureTopbarVisible);
  
  // Use MutationObserver to watch for any changes that might hide the topbar
  const observer = new MutationObserver(function(mutations) {
    mutations.forEach(function(mutation) {
      if (mutation.type === 'attributes' && mutation.attributeName === 'style') {
        const target = mutation.target;
        if (target.classList.contains('topbar')) {
          // Re-apply visibility if something tries to hide it
          setTimeout(ensureTopbarVisible, 10);
        }
      }
    });
  });
  
  // Start observing
  const topbar = document.querySelector('.topbar');
  if (topbar) {
    observer.observe(topbar, {
      attributes: true,
      attributeFilter: ['style', 'class']
    });
  }
  
  // Additional check every 2 seconds (safety net)
  setInterval(ensureTopbarVisible, 2000);
  
});

// Additional function to run after page fully loads
window.addEventListener('load', function() {
  setTimeout(function() {
    const topbar = document.querySelector('.topbar');
    if (topbar) {
      topbar.style.display = 'flex';
      topbar.style.visibility = 'visible';
      topbar.style.opacity = '1';
      console.log('Topbar final visibility check completed');
    }
  }, 500);
});