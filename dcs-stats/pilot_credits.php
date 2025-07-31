<?php 
// Start session before any output
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'header.php'; 
?>
<?php require_once __DIR__ . '/site_features.php'; ?>
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
    <div class="search-container">
        <input type="text" 
               id="pilot-name" 
               placeholder="Search for a pilot..." 
               autocomplete="off">
        <button onclick="searchPilotCredits()">Search</button>
        
        <div id="search-results" style="display: none;">
            <h3 style="text-align: center; color: #ccc; margin-bottom: 20px;">Search Results</h3>
            <div id="results-list" class="results-list"></div>
        </div>
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
                        <div class="stat-item">
                            <span class="stat-label">Last Updated:</span>
                            <span class="stat-value" id="credits-date"></span>
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

</style>

<script>
// Handle form submission
document.getElementById('pilot-search-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const pilotName = document.getElementById('pilot-name').value.trim();
    if (!pilotName) return;
    
    // Hide all sections
    document.getElementById('search-results').style.display = 'none';
    document.getElementById('credits-display').style.display = 'none';
    document.getElementById('no-results').style.display = 'none';
    
    // Show loading
    document.getElementById('loading').style.display = 'block';
    
    // Search for pilot credits
    await searchPilotCredits(pilotName);
});

async function searchPilotCredits(pilotName) {
    try {
        // Get API config
        const config = await window.dcsAPI.loadConfig();
        
        if (!config.use_api) {
            document.getElementById('loading').style.display = 'none';
            document.getElementById('no-results-message').textContent = 'API is not enabled. Credits search requires API access.';
            document.getElementById('no-results').style.display = 'block';
            return;
        }
        
        // First, search for the pilot to get exact name
        const searchResults = await window.dcsAPI.searchPlayers(pilotName);
        
        if (searchResults.count === 0) {
            document.getElementById('loading').style.display = 'none';
            document.getElementById('no-results-message').innerHTML = `No pilots found matching "${pilotName}".<br><br>Try:<br>• Checking the spelling<br>• Using a partial name<br>• Searching for the beginning of the name`;
            document.getElementById('no-results').style.display = 'block';
            return;
        }
        
        // If multiple results, show selection
        if (searchResults.count > 1) {
            document.getElementById('loading').style.display = 'none';
            showMultipleResults(searchResults.results);
            return;
        }
        
        // Single result - get credits for exact name
        const exactName = searchResults.results[0].name;
        
        // Call credits endpoint with exact name
        const basePath = window.DCS_CONFIG ? window.DCS_CONFIG.basePath : '';
        const buildUrl = (path) => basePath ? `${basePath}/${path}` : path;
        const response = await fetch(buildUrl('api_proxy.php?endpoint=' + encodeURIComponent('/credits') + '&method=POST'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                nick: exactName,
                date: new Date().toISOString().split('T')[0] // YYYY-MM-DD format
            })
        });
        
        document.getElementById('loading').style.display = 'none';
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const creditsData = await response.json();
        
        // Check if we got credits data
        let credits = 0;
        let hasCredits = false;
        
        if (typeof creditsData === 'number') {
            credits = creditsData;
            hasCredits = true;
        } else if (creditsData && Object.keys(creditsData).length > 0) {
            // It might return an object with player name as key
            const playerKey = Object.keys(creditsData).find(key => 
                key.toLowerCase() === exactName.toLowerCase()
            );
            if (playerKey !== undefined) {
                credits = creditsData[playerKey];
                hasCredits = true;
            }
        }
        
        if (hasCredits || credits >= 0) {
            // Display credits (even if 0)
            document.getElementById('pilot-display-name').textContent = exactName;
            document.getElementById('credits-value').textContent = credits.toLocaleString();
            document.getElementById('credits-date').textContent = new Date().toLocaleDateString();
            document.getElementById('credits-display').style.display = 'block';
        } else {
            // No credits found
            document.getElementById('no-results-message').innerHTML = `No credits data found for pilot "${exactName}".<br><br>This pilot may not have any recorded transactions yet.`;
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
    const html = `
        <h3>Multiple pilots found. Please select one:</h3>
        <div class="results-list">
            ${results.map(pilot => 
                `<div class="result-item" onclick="selectPilot('${pilot.name.replace(/'/g, "\\'")}')">${pilot.name}</div>`
            ).join('')}
        </div>
    `;
    document.getElementById('search-results').innerHTML = html;
    document.getElementById('search-results').style.display = 'block';
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
