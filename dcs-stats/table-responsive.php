<?php
/**
 * Utility functions for making tables responsive
 */

function tableResponsiveStart() {
    echo '<div class="table-wrapper">';
}

function tableResponsiveEnd() {
    echo '</div>';
}

// CSS for responsive tables (to be included in pages that use tables)
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
    
    @media screen and (max-width: 768px) {
        .table-wrapper {
            margin: 0 -1rem;
            border-radius: 0;
        }
        
        .table-wrapper table {
            min-width: 600px;
        }
        
        /* Add scroll indicator */
        .table-wrapper::after {
            content: "← Scroll →";
            position: absolute;
            bottom: 10px;
            right: 10px;
            background: rgba(76, 175, 80, 0.9);
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 0.8rem;
            pointer-events: none;
            opacity: 0.7;
        }
        
        .table-wrapper::-webkit-scrollbar {
            height: 8px;
        }
        
        .table-wrapper::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.3);
        }
        
        .table-wrapper::-webkit-scrollbar-thumb {
            background: #4CAF50;
            border-radius: 4px;
        }
    }
    </style>
    <?php
}
?>