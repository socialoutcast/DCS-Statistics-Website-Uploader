<?php 
// Start session before any output
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'header.php'; 
?>
<?php require_once __DIR__ . '/site_features.php'; ?>
<?php require_once __DIR__ . '/table-responsive.php'; ?>
<?php include 'nav.php'; ?>

<?php if (!isFeatureEnabled('credits_enabled')): ?>
<main>
    <div class="alert" style="text-align: center; padding: 50px;">
        <h2>Credits System Disabled</h2>
        <p>The credits system is currently disabled for this server.</p>
    </div>
</main>
<?php include 'footer.php'; exit; ?>
<?php endif; ?>

<main>
    <div class="dashboard-header">
        <h1>Pilot Credits Search</h1>
        <p class="dashboard-subtitle">Search for a pilot to view their current credits balance</p>
    </div>

    <?php if (isFeatureEnabled('pilot_search')): ?>
    <div class="search-container">
        <input type="text" id="playerSearchInput" placeholder="Search for a pilot..." />
        <button onclick="searchForPlayers()">Search</button>
    </div>
    <?php else: ?>
    <div class="alert" style="text-align: center; padding: 20px;">
        <p>Pilot search functionality is currently disabled.</p>
    </div>
    <?php endif; ?>

    <div id="multiple-results" style="display: none;">
        <h3 style="text-align: center; color: #ccc;">Multiple pilots found. Please select one:</h3>
        <div id="results-list" class="results-list"></div>
    </div>

    <div id="search-results" style="display: none;">
        <h3 style="text-align: center; color: #ccc; margin-bottom: 20px;">Search Results</h3>
        <div id="results-list" class="results-list"></div>
    </div>

    <!-- Loading indicator -->
    <div id="loading" class="loading-spinner" style="display: none;">
        <p>Searching for pilot credits...</p>
    </div>
    
    <!-- Credits display -->
    <div id="credits-display" style="display: none;">
        <div id="pilot-card" class="pilot-card">
            <h3 id="pilot-display-name"></h3>
            <div class="pilot-stats">
                <div class="stat-group">
                    <h4>Credits Information</h4>
                    <div class="stats-grid">
                        <div class="stat-item credits-stat-item">
                            <span class="stat-label">Current Balance:</span>
                            <span class="stat-value credits-value" id="credits-value">0</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="search-again">
                <button onclick="searchAgain()" class="search-container button">Search Another Pilot</button>
            </div>
        </div>
    </div>
    
    <!-- No results message -->
    <div id="no-results" class="no-results" style="display: none;">
        <p id="no-results-message">No credits data found for this pilot.</p>
        <button onclick="searchAgain()" class="btn-secondary">Try Another Search</button>
    </div>
</main>

<style>
/* Credits-specific styles using unified pilot card design */

.loading-spinner {
    text-align: center;
    padding: 50px;
    color: #4CAF50;
    font-size: 1.2rem;
}

.credits-stat-item .stat-value {
    font-size: 2rem;
    color: #4CAF50;
    text-shadow: 0 0 10px rgba(76, 175, 80, 0.3);
}

.search-again {
    text-align: center;
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid #444;
}

.no-results {
    text-align: center;
    padding: 50px;
    color: #ccc;
    background-color: #2c2c2c;
    border-radius: 12px;
    margin: 20px auto;
    max-width: 600px;
}

#no-results-message {
    margin-bottom: 20px;
}

/* Mobile Responsive Styles */
@media screen and (max-width: 768px) {
    /* Search container mobile optimization */
    .search-container {
        padding: 0 15px;
        margin-bottom: 20px;
        display: flex;
        flex-direction: column;
        gap: 10px;
    }
    
    #pilot-name {
        width: 100%;
        padding: 12px 15px;
        font-size: 16px; /* Prevents zoom on iOS */
        border-radius: 25px;
    }
    
    .search-container button {
        width: 100%;
        padding: 12px 20px;
        font-size: 1rem;
        border-radius: 25px;
        min-height: 44px;
    }
    
    /* Results list mobile optimization */
    .results-list {
        padding: 0 10px;
        max-height: 300px;
        overflow-y: auto;
    }
    
    /* Pilot card mobile optimization */
    .pilot-card {
        padding: 15px;
        margin: 15px;
    }
    
    .pilot-card h3 {
        font-size: 1.5rem;
        margin-bottom: 15px;
    }
    
    /* Stats grid mobile layout */
    .stats-grid {
        grid-template-columns: 1fr;
        gap: 15px;
    }
    
    .stat-item {
        display: flex;
        justify-content: space-between;
        padding: 12px;
        background: rgba(0, 0, 0, 0.3);
        border-radius: 5px;
    }
    
    .stat-label {
        font-weight: 600;
        color: #4CAF50;
    }
    
    .stat-value {
        font-weight: bold;
    }
    
    /* Credits value stays large on mobile */
    .credits-stat-item .stat-value {
        font-size: 1.8rem;
    }
    
    /* Search again button */
    .search-again {
        padding: 15px;
    }
    
    .search-again button {
        width: 100%;
        padding: 12px;
        min-height: 44px;
    }
    
    /* No results section */
    .no-results {
        padding: 20px;
    }
    
    .no-results button {
        width: 100%;
        margin-top: 15px;
        padding: 12px;
        min-height: 44px;
    }
    
    /* Dashboard header mobile */
    .dashboard-header h1 {
        font-size: 1.8rem;
    }
    
    .dashboard-subtitle {
        font-size: 0.9rem;
        padding: 0 10px;
    }
    
    /* Loading spinner */
    .loading-spinner {
        padding: 30px;
        font-size: 1rem;
    }
}

/* Very small devices */
@media screen and (max-width: 480px) {
    .pilot-card {
        margin: 10px;
        padding: 10px;
    }
    
    .pilot-card h3 {
        font-size: 1.2rem;
    }
    
    .stat-item {
        font-size: 0.9rem;
        padding: 10px;
    }
    
    .credits-stat-item .stat-value {
        font-size: 1.5rem;
    }
}

/* Touch-friendly hover states */
@media (hover: none) and (pointer: coarse) {
    button:hover {
        transform: none;
    }
    
    button:active {
        transform: scale(0.95);
    }
}

</style>

<?php tableResponsiveStyles(); ?>

<script>
// Allow Enter key to trigger search
document.getElementById('playerSearchInput').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        searchForPlayers();
    }
});

// Check for search parameter in URL and auto-search
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const searchParam = urlParams.get('search');

    if (searchParam) {
        // Set the search input value
        const searchInput = document.getElementById('playerSearchInput');
        if (searchInput) {
            searchInput.value = searchParam;
            // Trigger the search automatically
            searchForPlayers();
        }
    }
});

async function searchForPlayers() {
    const searchInput = document.getElementById('playerSearchInput');
    const searchTerm = searchInput.value.trim();

    if (!searchTerm) {
        alert('Please enter a pilot name to search.');
        return;
    }

    // Hide all sections
    document.getElementById('search-results').style.display = 'none';
    document.getElementById('multiple-results').style.display = 'none';
    document.getElementById('no-results').style.display = 'none';
    document.getElementById('loading').style.display = 'block';

    try {
        // Search for players using client-side API
        const searchData = await window.dcsAPI.searchPlayers(searchTerm);


        document.getElementById('loading').style.display = 'none';

        if (searchData.error || searchData.count === 0) {
            let errorMessage = searchData.error || `No pilots found matching "${searchTerm}". Try:\n• Checking the spelling\n• Using a partial name\n• Searching for the beginning of the name`;
            if (searchData.message) {
                errorMessage += '\n\n' + searchData.message;
            }
            document.getElementById('no-results-message').innerHTML = errorMessage.replace(/\n/g, '<br>');
            document.getElementById('no-results').style.display = 'block';
            return;
        }

        if (searchData.count === 1) {
            // Single result - load directly
            await loadPilotCredits(searchData.results[0]);
        } else {
            // Multiple results - show selection
            showMultipleResults(searchData.results);
        }

    } catch (error) {
        console.error('Error searching for pilots:', error);
        document.getElementById('loading').style.display = 'none';
        document.getElementById('no-results-message').textContent = 'Error searching for pilots: ' + error.message;
        document.getElementById('no-results').style.display = 'block';
    }
}

async function loadPilotCredits(pilot) {
    try {
        // Call credits endpoint with exact name
        const basePath = window.DCS_CONFIG ? window.DCS_CONFIG.basePath : '';
        const buildUrl = (path) => basePath ? `${basePath}/${path}` : path;
        const response = await fetch(buildUrl('api_proxy.php?endpoint=' + encodeURIComponent('/credits') + '&method=POST'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                nick: pilot.nick,
                date: pilot.date
            })
        });
        
        document.getElementById('loading').style.display = 'none';
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const creditsData = await response.json();
        
        if (creditsData && creditsData.credits !== undefined) {
            // Display credits data
            document.getElementById('pilot-display-name').textContent = creditsData.name;
            document.getElementById('credits-value').textContent = creditsData.credits.toLocaleString();
            document.getElementById('credits-display').style.display = 'block';
        } else {
            // No credits found
            document.getElementById('no-results-message').innerHTML = `No credits data found for pilot "${pilot.nick}".<br><br>This pilot may not have any recorded transactions yet.`;
            document.getElementById('no-results').style.display = 'block';
        }
        
    } catch (error) {
        console.error('Error searching for pilot credits:', error);
        document.getElementById('loading').style.display = 'none';
        document.getElementById('no-results-message').innerHTML = `Error searching for pilot credits.<br><br>Please try again later.`;
        document.getElementById('no-results').style.display = 'block';
    }
}

// Add function to show multiple results
function showMultipleResults(results) {
    const resultsList = document.getElementById('results-list');
    resultsList.innerHTML = '';

    results.forEach(pilot => {
        const resultItem = document.createElement('div');
        resultItem.className = 'result-item';
        resultItem.textContent = pilot.nick;
        resultItem.onclick = () => {
            document.getElementById('multiple-results').style.display = 'none';
            document.getElementById('loading').style.display = 'block';
            loadPilotCredits(pilot);
        };
        resultsList.appendChild(resultItem);
    });

    document.getElementById('multiple-results').style.display = 'block';
}

// Add function to handle pilot selection
function selectPilot(pilotName) {
    document.getElementById('search-results').style.display = 'none';
    document.getElementById('loading').style.display = 'block';
    searchPilotCredits(pilotName);
}

function searchAgain() {
    // Reset the input
    document.getElementById('pilot-name').value = '';
    document.getElementById('credits-display').style.display = 'none';
    document.getElementById('no-results').style.display = 'none';
    document.getElementById('search-results').style.display = 'none';
    document.getElementById('pilot-name').focus();
}

// Check if there's a pilot parameter in the URL
const urlParams = new URLSearchParams(window.location.search);
const pilotParam = urlParams.get('pilot');
if (pilotParam) {
    document.getElementById('pilot-name').value = pilotParam;
    searchPilotCredits();
}
</script>

<?php include 'footer.php'; ?>
