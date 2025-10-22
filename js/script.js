// Preloader
window.addEventListener("load", function () {
  const preloader = document.getElementById("preloader");
  if (preloader) {
    preloader.classList.add("hidden");
    setTimeout(() => preloader.style.display = "none", 600);
  }

  // Popup after 10 seconds
  setTimeout(() => {
    const popup = document.getElementById("popup");
    if (popup) popup.style.display = "flex";
  }, 10000);

  // FORCE TOPBAR VISIBILITY AFTER PAGE LOAD
  ensureTopbarVisible();
});

// TOPBAR VISIBILITY ENFORCEMENT FUNCTION
function ensureTopbarVisible() {
  const topbar = document.querySelector('.topbar');
  
  if (topbar) {
    // Force display with strong CSS
    topbar.style.display = 'flex';
    topbar.style.visibility = 'visible';
    topbar.style.opacity = '1';
    topbar.style.height = 'auto';
    topbar.style.backgroundColor = '#003300';
    topbar.style.color = 'white';
    topbar.style.position = 'relative';
    topbar.style.zIndex = '1002';
    
    // Apply mobile styles if on mobile
    if (window.innerWidth <= 768) {
      topbar.style.flexDirection = 'column';
      topbar.style.gap = '10px';
      topbar.style.padding = '12px 15px';
      topbar.style.fontSize = '0.8rem';
      topbar.style.textAlign = 'center';
      topbar.style.minHeight = '70px';
      
      // Style topbar sections
      const topbarLeft = topbar.querySelector('.topbar-left');
      const topbarRight = topbar.querySelector('.topbar-right');
      
      if (topbarLeft) {
        topbarLeft.style.display = 'flex';
        topbarLeft.style.justifyContent = 'center';
        topbarLeft.style.alignItems = 'center';
        topbarLeft.style.flexWrap = 'wrap';
        topbarLeft.style.gap = '10px';
        topbarLeft.style.width = '100%';
      }
      
      if (topbarRight) {
        topbarRight.style.display = 'flex';
        topbarRight.style.justifyContent = 'center';
        topbarRight.style.alignItems = 'center';
        topbarRight.style.flexWrap = 'wrap';
        topbarRight.style.gap = '10px';
        topbarRight.style.width = '100%';
      }
    }
    
    console.log('✅ Topbar visibility enforced for mobile');
  }
}

// Popup Close
function closePopup() {
  const popup = document.getElementById("popup");
  if (popup) popup.style.display = "none";
}

// WhatsApp Form Submit
function sendToWhatsApp(event) {
  event.preventDefault();

  const name = document.getElementById("popup-name").value.trim();
  const phone = document.getElementById("popup-phone").value.trim();

  if (!name || !phone) return;

  if (!/^[6-9]\d{9}$/.test(phone)) {
    alert("Please enter a valid 10-digit Indian phone number.");
    return;
  }

  const message = `Hello, I would like to enquire:\nName: ${name}\nPhone: ${phone}`;
  const whatsappNumber = "919732181111";
  const url = `https://wa.me/${whatsappNumber}?text=${encodeURIComponent(message)}`;
  window.open(url, "_blank");
}

// Escape key to close popup
document.addEventListener("keydown", function (e) {
  if (e.key === "Escape") closePopup();
});

// Back to Top Button
window.addEventListener("DOMContentLoaded", function () {
  const backToTopBtn = document.getElementById("backToTop");
  const slides = document.querySelectorAll(".slide");
  let currentSlide = 0;

  // ENSURE TOPBAR VISIBILITY ON DOM READY
  ensureTopbarVisible();

  if (backToTopBtn) {
    window.addEventListener("scroll", () => {
      const show = document.body.scrollTop > 150 || document.documentElement.scrollTop > 150;
      backToTopBtn.style.display = show ? "block" : "none";
    });

    backToTopBtn.addEventListener("click", () => {
      window.scrollTo({ top: 0, behavior: "smooth" });
    });
  }

  // Slider
  if (slides.length > 0) {
    setInterval(() => {
      slides[currentSlide].classList.remove("active");
      currentSlide = (currentSlide + 1) % slides.length;
      slides[currentSlide].classList.add("active");
    }, 4000);
  }

  // CONTINUOUS TOPBAR MONITORING
  // Run topbar check on window resize
  window.addEventListener('resize', ensureTopbarVisible);
  
  // Use MutationObserver to watch for any changes that might hide the topbar
  const observer = new MutationObserver(function(mutations) {
    mutations.forEach(function(mutation) {
      if (mutation.type === 'attributes' && mutation.attributeName === 'style') {
        const target = mutation.target;
        if (target.classList && target.classList.contains('topbar')) {
          // Re-apply visibility if something tries to hide it
          setTimeout(ensureTopbarVisible, 50);
        }
      }
    });
  });
  
  // Start observing the topbar
  const topbar = document.querySelector('.topbar');
  if (topbar) {
    observer.observe(topbar, {
      attributes: true,
      attributeFilter: ['style', 'class']
    });
  }
  
  // Safety net - check every 3 seconds
  setInterval(ensureTopbarVisible, 3000);
});

// ADDITIONAL SAFETY CHECK - Force topbar visibility every 5 seconds
setInterval(function() {
  const topbar = document.querySelector('.topbar');
  if (topbar) {
    const computedStyle = window.getComputedStyle(topbar);
    if (computedStyle.display === 'none' || computedStyle.visibility === 'hidden' || computedStyle.opacity === '0') {
      console.log('🔧 Topbar hidden detected, forcing visibility...');
      ensureTopbarVisible();
    }
  }
}, 5000);