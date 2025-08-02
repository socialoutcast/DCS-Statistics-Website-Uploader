<?php
/**
 * Utility functions for making tables responsive with mobile card support
 */

function tableResponsiveStart($includeCards = true, $cardId = '') {
    echo '<div class="table-wrapper">';
}

function tableResponsiveEnd($includeCards = true, $cardId = '') {
    echo '</div>';
    
    // Add mobile cards container if requested
    if ($includeCards && $cardId) {
        echo "\n<!-- Mobile Cards Container -->\n";
        echo '<div class="mobile-cards" id="' . htmlspecialchars($cardId) . '"></div>';
    }
}

// CSS for responsive tables with card layout support
function tableResponsiveStyles() {
    ?>
    <style>
    .table-wrapper {
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        background: rgba(0, 0, 0, 0.3);
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
    }
    
    /* Mobile Cards Container - Hidden by default */
    .mobile-cards {
        display: none;
    }
    
    @media screen and (max-width: 768px) {
        /* Hide table wrapper on mobile */
        .table-wrapper {
            display: none;
        }
        
        /* Show mobile cards instead */
        .mobile-cards {
            display: block;
            padding: 0 10px;
        }
        
        /* Card base styles */
        .mobile-card {
            background: rgba(0, 0, 0, 0.6);
            border: 1px solid rgba(76, 175, 80, 0.3);
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 15px;
            transition: all 0.3s ease;
        }
        
        .mobile-card:active {
            transform: scale(0.98);
            background: rgba(76, 175, 80, 0.1);
        }
    }
    </style>
    <?php
}

// Helper function to create a mobile card
function createMobileCard($content, $classes = '') {
    return '<div class="mobile-card ' . htmlspecialchars($classes) . '">' . $content . '</div>';
}

// Helper function to escape HTML
function tableResponsiveEscape($text) {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}
?>