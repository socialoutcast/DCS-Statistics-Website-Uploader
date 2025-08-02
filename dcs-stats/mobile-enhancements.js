/**
 * Mobile Enhancement JavaScript
 * Improves mobile experience for DCS Statistics Dashboard
 */

document.addEventListener('DOMContentLoaded', function() {
    // Detect if user is on mobile
    const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
    const isSmallScreen = window.innerWidth <= 768;
    
    if (isMobile || isSmallScreen) {
        // Add mobile class to body for CSS targeting
        document.body.classList.add('is-mobile');
        
        // Enhance table scrolling
        enhanceTableScrolling();
        
        // Optimize charts for mobile
        optimizeChartsForMobile();
        
        // Add touch gestures
        addTouchGestures();
        
        // Fix viewport height on mobile browsers
        fixViewportHeight();
    }
    
    // Handle orientation changes
    window.addEventListener('orientationchange', function() {
        setTimeout(function() {
            fixViewportHeight();
            if (window.Chart) {
                // Resize all charts on orientation change
                Chart.helpers.each(Chart.instances, function(instance) {
                    instance.resize();
                });
            }
        }, 200);
    });
});

/**
 * Enhance table scrolling (removed indicators per user request)
 */
function enhanceTableScrolling() {
    // Function kept for compatibility but indicators removed
}

/**
 * Optimize Chart.js charts for mobile
 */
function optimizeChartsForMobile() {
    if (typeof Chart === 'undefined') return;
    
    // Update default options for mobile
    Chart.defaults.font.size = 10;
    Chart.defaults.plugins.legend.labels.boxWidth = 12;
    Chart.defaults.plugins.legend.labels.padding = 10;
    
    // Reduce animation duration on mobile
    Chart.defaults.animation.duration = 500;
    
    // Update existing chart options
    Chart.helpers.each(Chart.instances, function(instance) {
        // Update font sizes
        if (instance.options.plugins) {
            if (instance.options.plugins.legend) {
                instance.options.plugins.legend.labels = {
                    ...instance.options.plugins.legend.labels,
                    font: { size: 10 }
                };
            }
            if (instance.options.plugins.tooltip) {
                instance.options.plugins.tooltip.titleFont = { size: 11 };
                instance.options.plugins.tooltip.bodyFont = { size: 10 };
            }
        }
        
        // Update scales font sizes
        if (instance.options.scales) {
            Object.keys(instance.options.scales).forEach(scale => {
                if (instance.options.scales[scale].ticks) {
                    instance.options.scales[scale].ticks.font = { size: 9 };
                }
                if (instance.options.scales[scale].title) {
                    instance.options.scales[scale].title.font = { size: 11 };
                }
            });
        }
        
        instance.update();
    });
}

/**
 * Add touch gesture support
 */
function addTouchGestures() {
    // Swipe to close mobile menu
    const navBar = document.getElementById('navBar');
    if (navBar) {
        let touchStartX = 0;
        let touchEndX = 0;
        
        navBar.addEventListener('touchstart', function(e) {
            touchStartX = e.changedTouches[0].screenX;
        });
        
        navBar.addEventListener('touchend', function(e) {
            touchEndX = e.changedTouches[0].screenX;
            handleSwipe();
        });
        
        function handleSwipe() {
            if (touchEndX < touchStartX - 50) { // Swipe left
                navBar.classList.remove('mobile-menu-open');
                document.getElementById('mobileMenuOverlay').classList.remove('active');
                document.body.style.overflow = '';
            }
        }
    }
    
    // Improve touch targets
    const buttons = document.querySelectorAll('button, .button, .nav-link');
    buttons.forEach(button => {
        // Ensure minimum touch target size
        const rect = button.getBoundingClientRect();
        if (rect.height < 44) {
            button.style.minHeight = '44px';
            button.style.display = 'flex';
            button.style.alignItems = 'center';
            button.style.justifyContent = 'center';
        }
    });
}

/**
 * Fix viewport height on mobile browsers
 */
function fixViewportHeight() {
    // Fix for mobile browsers with dynamic viewport height
    const vh = window.innerHeight * 0.01;
    document.documentElement.style.setProperty('--vh', `${vh}px`);
}

/**
 * Utility function to debounce resize events
 */
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Handle window resize
window.addEventListener('resize', debounce(function() {
    const isNowMobile = window.innerWidth <= 768;
    
    if (isNowMobile && !document.body.classList.contains('is-mobile')) {
        document.body.classList.add('is-mobile');
        optimizeChartsForMobile();
    } else if (!isNowMobile && document.body.classList.contains('is-mobile')) {
        document.body.classList.remove('is-mobile');
        // Restore desktop chart settings
        if (window.Chart) {
            Chart.defaults.font.size = 12;
            Chart.helpers.each(Chart.instances, function(instance) {
                instance.resize();
            });
        }
    }
    
    fixViewportHeight();
}, 250));