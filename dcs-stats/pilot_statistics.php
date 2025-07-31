<?php 
// Start session before any output
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'header.php'; 
?>
<?php require_once __DIR__ . '/site_features.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<?php include 'nav.php'; ?>

<main>
    <div class="dashboard-header">
        <h1>Pilot Statistics</h1>
        <p class="dashboard-subtitle">Search and analyze individual pilot performance</p>
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
        <div id="pilot-card" class="pilot-card">
            <h3 id="pilot-name"></h3>
            <div class="pilot-stats">
                <div class="stat-group" id="combat-stats-group">
                    <h4>Combat Statistics</h4>
                    <div class="stats-grid" id="combat-stats-grid">
                        <!-- Combat stats will be dynamically added here -->
                    </div>
                </div>
                
                <div class="stat-group" id="secondary-stats-group" style="display: none;">
                    <h4>Additional Information</h4>
                    <div class="stats-grid" id="secondary-stats-grid">
                        <!-- Secondary stats will be dynamically added here -->
                    </div>
                </div>
                
                <div class="stat-group" id="session-stats-group" style="display: none;">
                    <h4>Last Session</h4>
                    <div class="stats-grid" id="session-stats-grid">
                        <!-- Session stats will be dynamically added here -->
                    </div>
                </div>
            </div>
            
            <div class="charts-container">
                <?php if (isFeatureEnabled('pilot_combat_stats')): ?>
                <div class="chart-wrapper" title="Shows your air-to-air kills vs deaths in combat">
                    <h4>Combat Performance <span class="chart-info">ⓘ</span></h4>
                    <canvas id="combatChart"></canvas>
                </div>
                <?php endif; ?>
                <?php if (isFeatureEnabled('pilot_flight_stats')): ?>
                <div class="chart-wrapper" title="Breakdown of your flight outcomes: successful landings, crashes, ejections, and aircraft still in flight">
                    <h4>Flight Statistics <span class="chart-info">ⓘ</span></h4>
                    <canvas id="flightChart"></canvas>
                </div>
                <?php endif; ?>
                <?php if (isFeatureEnabled('pilot_aircraft_chart')): ?>
                <div class="chart-wrapper" id="aircraftChartWrapper" style="display: none;" title="Shows which aircraft you've scored the most kills with">
                    <h4>Aircraft Usage <span class="chart-info">ⓘ</span></h4>
                    <canvas id="aircraftChart"></canvas>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div id="no-results" style="display: none; text-align: center; color: #ccc; margin-top: 30px;">
        <p id="no-results-message">No pilot found with that name. Please check the spelling and try again.</p>
    </div>
    
    <div id="loading" style="display: none; text-align: center; color: #ccc; margin-top: 30px;">
        <p>Searching...</p>
    </div>
</main>

<script>
// Feature flags from PHP
const siteFeatures = {
    credits: <?php echo json_encode(isFeatureEnabled('credits_enabled')); ?>,
    squadrons: <?php echo json_encode(isFeatureEnabled('squadrons_enabled')); ?>,
    leaderboard_kills: <?php echo json_encode(isFeatureEnabled('leaderboard_kills')); ?>,
    leaderboard_deaths: <?php echo json_encode(isFeatureEnabled('leaderboard_deaths')); ?>,
    leaderboard_kd_ratio: <?php echo json_encode(isFeatureEnabled('leaderboard_kd_ratio')); ?>,
    leaderboard_flight_hours: <?php echo json_encode(isFeatureEnabled('leaderboard_flight_hours')); ?>,
    leaderboard_aircraft: <?php echo json_encode(isFeatureEnabled('leaderboard_aircraft')); ?>,
    pilot_combat_stats: <?php echo json_encode(isFeatureEnabled('pilot_combat_stats')); ?>,
    pilot_flight_stats: <?php echo json_encode(isFeatureEnabled('pilot_flight_stats')); ?>,
    pilot_session_stats: <?php echo json_encode(isFeatureEnabled('pilot_session_stats')); ?>,
    pilot_aircraft_chart: <?php echo json_encode(isFeatureEnabled('pilot_aircraft_chart')); ?>
};

// Function to create stat items dynamically
function createStatItem(label, value, id) {
    return `
        <div class="stat-item">
            <span class="stat-label">${label}:</span>
            <span class="stat-value" id="${id}">${value}</span>
        </div>
    `;
}

// Function to populate stats based on available data and enabled features
function populateStatsGrid(stats) {
    // Combat stats grid
    const combatGrid = document.getElementById('combat-stats-grid');
    const combatGroup = combatGrid.parentElement.parentElement; // .stat-group
    combatGrid.innerHTML = '';
    let hasCombatStats = false;
    
    // Show combat stats if enabled and data exists
    if (siteFeatures.pilot_combat_stats) {
        if (stats.kills !== undefined) {
            combatGrid.innerHTML += createStatItem('Kills', stats.kills || 0, 'pilot-kills');
            hasCombatStats = true;
        }
        if (stats.deaths !== undefined) {
            combatGrid.innerHTML += createStatItem('Deaths', stats.deaths || 0, 'pilot-deaths');
            hasCombatStats = true;
        }
        if (stats.kd_ratio !== undefined) {
            combatGrid.innerHTML += createStatItem('K/D Ratio', (stats.kd_ratio || 0).toFixed(2), 'pilot-kd');
            hasCombatStats = true;
        }
    }
    
    // Show flight stats if enabled and data exists
    if (siteFeatures.pilot_flight_stats) {
        if (stats.takeoffs !== undefined) {
            combatGrid.innerHTML += createStatItem('Takeoffs', stats.takeoffs || 0, 'pilot-takeoffs');
            hasCombatStats = true;
        }
        if (stats.landings !== undefined) {
            combatGrid.innerHTML += createStatItem('Landings', stats.landings || 0, 'pilot-landings');
            hasCombatStats = true;
        }
        if (stats.crashes !== undefined) {
            combatGrid.innerHTML += createStatItem('Crashes', stats.crashes || 0, 'pilot-crashes');
            hasCombatStats = true;
        }
        if (stats.ejections !== undefined) {
            combatGrid.innerHTML += createStatItem('Ejections', stats.ejections || 0, 'pilot-ejections');
            hasCombatStats = true;
        }
    }
    
    // Hide entire combat stats group if no stats to show
    combatGroup.style.display = hasCombatStats ? 'block' : 'none';
    
    // Secondary stats grid
    const secondaryGrid = document.getElementById('secondary-stats-grid');
    secondaryGrid.innerHTML = '';
    let hasSecondaryStats = false;
    
    // Credits if enabled
    if (siteFeatures.credits && stats.credits !== undefined) {
        secondaryGrid.innerHTML += createStatItem('Credits', stats.credits || 0, 'pilot-credits');
        hasSecondaryStats = true;
    }
    
    // Aircraft if we have data
    if (stats.most_used_aircraft && stats.most_used_aircraft !== 'N/A') {
        secondaryGrid.innerHTML += createStatItem('Most Used Aircraft', stats.most_used_aircraft, 'pilot-aircraft');
        hasSecondaryStats = true;
    }
    
    // Squadron if enabled
    if (siteFeatures.squadrons && stats.squadron) {
        const squadronHtml = `
            <div class="stat-item" id="squadron-info">
                <span class="stat-label">Squadron:</span>
                <span class="stat-value" id="pilot-squadron">${stats.squadron}</span>
            </div>
        `;
        secondaryGrid.innerHTML += squadronHtml;
        hasSecondaryStats = true;
    }
    
    // Show/hide secondary stats group
    document.getElementById('secondary-stats-group').style.display = hasSecondaryStats ? 'block' : 'none';
    
    // Session stats grid
    const sessionGrid = document.getElementById('session-stats-grid');
    sessionGrid.innerHTML = '';
    let hasSessionStats = false;
    
    if (siteFeatures.pilot_session_stats && (stats.last_session_kills !== undefined || stats.last_session_deaths !== undefined)) {
        if (stats.last_session_kills !== undefined) {
            sessionGrid.innerHTML += createStatItem('Session Kills', stats.last_session_kills || 0, 'pilot-session-kills');
            hasSessionStats = true;
        }
        if (stats.last_session_deaths !== undefined) {
            sessionGrid.innerHTML += createStatItem('Session Deaths', stats.last_session_deaths || 0, 'pilot-session-deaths');
            hasSessionStats = true;
        }
    }
    
    // Show/hide session stats group
    document.getElementById('session-stats-group').style.display = hasSessionStats ? 'block' : 'none';
}

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
            await loadPilotStats(searchData.results[0].name);
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

function showMultipleResults(results) {
    const resultsList = document.getElementById('results-list');
    resultsList.innerHTML = '';
    
    results.forEach(pilot => {
        const resultItem = document.createElement('div');
        resultItem.className = 'result-item';
        resultItem.textContent = pilot.name;
        resultItem.onclick = () => {
            document.getElementById('multiple-results').style.display = 'none';
            document.getElementById('loading').style.display = 'block';
            loadPilotStats(pilot.name);
        };
        resultsList.appendChild(resultItem);
    });
    
    document.getElementById('multiple-results').style.display = 'block';
}

async function loadPilotStats(playerName) {
    document.getElementById('multiple-results').style.display = 'none';
    
    try {
        
        // Get player stats using client-side API
        const statsResult = await window.dcsAPI.getPlayerStats(playerName);
        
        if (statsResult.error) {
            document.getElementById('loading').style.display = 'none';
            
            // Show more detailed error message
            let errorMessage = statsResult.error || 'No pilot found with that name.';
            if (statsResult.message) {
                errorMessage += '<br><br>' + statsResult.message;
            }
            
            document.getElementById('no-results-message').innerHTML = errorMessage;
            document.getElementById('no-results').style.display = 'block';
            return;
        }
        
        // Extract actual stats data from the response
        const statsData = statsResult.data || statsResult;
        
        // If statsData doesn't have the expected structure, use the raw result
        const finalStats = statsData.data || statsData;
        
        // Get credits data for this specific player if credits are enabled
        let credits = undefined;
        if (siteFeatures.credits) {
            try {
                // Get API config
                const config = await window.dcsAPI.loadConfig();
                if (config.use_api && config.api_base_url) {
                    // Call credits endpoint with player name and current date
                    const basePath = window.DCS_CONFIG ? window.DCS_CONFIG.basePath : '';
                    const buildUrl = (path) => basePath ? `${basePath}/${path}` : path;
                    const response = await fetch(buildUrl('api_proxy.php?endpoint=' + encodeURIComponent('/credits') + '&method=POST'), {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            nick: playerName,
                            date: new Date().toISOString().split('T')[0] // YYYY-MM-DD format
                        })
                    });
                    
                    if (response.ok) {
                        const creditsData = await response.json();
                        // The response might be a number or an object with the player's credits
                        if (typeof creditsData === 'number') {
                            credits = creditsData;
                        } else if (creditsData && creditsData[playerName] !== undefined) {
                            credits = creditsData[playerName];
                        } else {
                            credits = 0;
                        }
                    }
                }
            } catch (e) {
                console.warn('Could not load credits data:', e);
                credits = 0;
            }
        }
        
        // Squadron data not available in API-only mode
        let squadron = undefined;
        let squadronLogo = null;
        
        // Helper function to safely update element text
        function updateElement(id, value) {
            const element = document.getElementById(id);
            if (element) {
                element.textContent = value;
            } else {
                console.warn(`Element with id '${id}' not found`);
            }
        }
        
        // Update pilot name
        updateElement('pilot-name', finalStats.name || playerName);
        
        // Prepare stats object with all available data
        const displayStats = {
            ...finalStats,
            credits: credits,
            squadron: squadron
        };
        
        // Dynamically populate stats grids based on available data
        populateStatsGrid(displayStats);
        
        // Check if pilot card has any visible content
        const combatGroup = document.getElementById('combat-stats-group');
        const secondaryGroup = document.getElementById('secondary-stats-group');
        const sessionGroup = document.getElementById('session-stats-group');
        
        const hasAnyStats = (combatGroup && combatGroup.style.display !== 'none') ||
                           (secondaryGroup && secondaryGroup.style.display !== 'none') ||
                           (sessionGroup && sessionGroup.style.display !== 'none');
        
        // If no stats are visible, show a message
        if (!hasAnyStats) {
            const pilotCard = document.getElementById('pilot-card');
            pilotCard.innerHTML = `
                <h3 id="pilot-name">${finalStats.name || playerName}</h3>
                <div class="no-stats-message">
                    <p>No statistics are currently enabled for display.</p>
                    <p>Contact your administrator to enable pilot statistics features.</p>
                </div>
            `;
            
            // Also hide the charts container if no stats are shown
            const chartsContainer = document.querySelector('.charts-container');
            if (chartsContainer) {
                chartsContainer.style.display = 'none';
            }
        } else {
            // Check if any charts are visible, if not hide the container
            const visibleCharts = document.querySelectorAll('.chart-wrapper:not([style*="display: none"])');
            const chartsContainer = document.querySelector('.charts-container');
            if (chartsContainer && visibleCharts.length === 0) {
                chartsContainer.style.display = 'none';
            }
        }
        
        // Update squadron info with logo if available (only if element exists)
        const squadronInfoDiv = document.getElementById('squadron-info');
        if (squadronInfoDiv) {
            if (squadronLogo && squadron !== 'None' && squadron !== 'N/A') {
                squadronInfoDiv.innerHTML = `
                    <span class="stat-label">Squadron:</span>
                    <div class="squadron-display">
                        <img src="${squadronLogo}" alt="${squadron}" class="squadron-logo">
                        <span class="stat-value">${squadron}</span>
                    </div>
                `;
            } else {
                const squadronElement = document.getElementById('pilot-squadron');
                if (squadronElement) {
                    squadronElement.textContent = squadron;
                }
            }
        }
        
        // Show results
        document.getElementById('loading').style.display = 'none';
        document.getElementById('search-results').style.display = 'block';
        
        // Create charts based on enabled features
        if (siteFeatures.pilot_combat_stats) {
            createCombatChart(finalStats);
        } else {
            // Hide combat chart if feature disabled
            const combatChartWrapper = document.querySelector('.chart-wrapper[title*="combat"]');
            if (combatChartWrapper) {
                combatChartWrapper.style.display = 'none';
            }
        }
        
        if (siteFeatures.pilot_flight_stats) {
            createFlightChart(finalStats);
        } else {
            // Hide flight chart if feature disabled
            const flightChartWrapper = document.querySelector('.chart-wrapper[title*="flight"]');
            if (flightChartWrapper) {
                flightChartWrapper.style.display = 'none';
            }
        }
        
        // Check for aircraft usage data only if feature is enabled
        if (siteFeatures.pilot_aircraft_chart) {
            if (finalStats.aircraftUsage && finalStats.aircraftUsage.length > 0) {
                createAircraftChart(finalStats.aircraftUsage);
            } else if (finalStats.kills_by_module && Object.keys(finalStats.kills_by_module).length > 0) {
                // Convert kills_by_module from API format to aircraftUsage format
                const aircraftUsage = Object.entries(finalStats.kills_by_module).map(([name, count]) => ({
                    name,
                    count
                })).sort((a, b) => b.count - a.count).slice(0, 5); // Top 5 aircraft
                if (aircraftUsage.length > 0) {
                    createAircraftChart(aircraftUsage);
                }
            }
        } else {
            // Hide aircraft chart if feature disabled
            const aircraftChartWrapper = document.getElementById('aircraftChartWrapper');
            if (aircraftChartWrapper) {
                aircraftChartWrapper.style.display = 'none';
            }
        }
        
        // Trap scores removed - not available in API
        
    } catch (error) {
        console.error('Error loading pilot stats:', error);
        document.getElementById('loading').style.display = 'none';
        document.getElementById('no-results-message').innerHTML = `Error loading pilot stats: ${error.message}<br><br>Please check the console for more details.`;
        document.getElementById('no-results').style.display = 'block';
    }
}

// Chart instances
let combatChart = null;
let flightChart = null;
let aircraftChart = null;
let trapScoresChart = null;

// Chart configuration with dark theme
const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            labels: {
                color: '#ccc',
                font: {
                    size: 12
                }
            }
        },
        tooltip: {
            backgroundColor: '#1e1e1e',
            titleColor: '#4CAF50',
            bodyColor: '#ccc',
            borderColor: '#444',
            borderWidth: 1
        }
    },
    scales: {
        x: {
            ticks: {
                color: '#ccc'
            },
            grid: {
                color: '#333',
                borderColor: '#444'
            },
            title: {
                display: true,
                text: 'Statistics',
                color: '#4CAF50',
                font: {
                    size: 14,
                    weight: 'bold'
                }
            }
        },
        y: {
            ticks: {
                color: '#ccc'
            },
            grid: {
                color: '#333',
                borderColor: '#444'
            },
            title: {
                display: true,
                text: 'Count',
                color: '#4CAF50',
                font: {
                    size: 14,
                    weight: 'bold'
                }
            }
        }
    }
};

function createCombatChart(statsData) {
    const ctx = document.getElementById('combatChart').getContext('2d');
    
    // Destroy existing chart if it exists
    if (combatChart) {
        combatChart.destroy();
    }
    
    // Only show data that exists
    const labels = [];
    const data = [];
    const backgroundColor = [];
    const borderColor = [];
    
    if (statsData.kills !== undefined) {
        labels.push('Kills');
        data.push(statsData.kills || 0);
        backgroundColor.push('rgba(76, 175, 80, 0.6)');
        borderColor.push('rgba(76, 175, 80, 1)');
    }
    
    if (statsData.deaths !== undefined) {
        labels.push('Deaths');
        data.push(statsData.deaths || 0);
        backgroundColor.push('rgba(244, 67, 54, 0.6)');
        borderColor.push('rgba(244, 67, 54, 1)');
    }
    
    // Don't create chart if no data
    if (labels.length === 0) {
        const chartWrapper = ctx.parentElement.parentElement;
        if (chartWrapper) {
            chartWrapper.style.display = 'none';
        }
        return;
    }
    
    combatChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Combat Stats',
                data: data,
                backgroundColor: backgroundColor,
                borderColor: borderColor,
                borderWidth: 1
            }]
        },
        options: {
            ...chartOptions,
            plugins: {
                ...chartOptions.plugins,
                legend: {
                    display: false
                }
            },
            scales: {
                ...chartOptions.scales,
                x: {
                    ...chartOptions.scales.x,
                    title: {
                        display: true,
                        text: 'Combat Metrics',
                        color: '#4CAF50',
                        font: {
                            size: 14,
                            weight: 'bold'
                        }
                    }
                },
                y: {
                    ...chartOptions.scales.y,
                    title: {
                        display: true,
                        text: 'Number of Events',
                        color: '#4CAF50',
                        font: {
                            size: 14,
                            weight: 'bold'
                        }
                    }
                }
            }
        }
    });
}

function createFlightChart(statsData) {
    const ctx = document.getElementById('flightChart').getContext('2d');
    
    // Destroy existing chart if it exists
    if (flightChart) {
        flightChart.destroy();
    }
    
    const takeoffs = statsData.takeoffs || 0;
    const landings = statsData.landings || 0;
    const crashes = statsData.crashes || 0;
    const ejections = statsData.ejections || 0;
    
    // Only include data that exists
    const labels = [];
    const data = [];
    const backgroundColor = [];
    const borderColor = [];
    
    if (statsData.landings !== undefined) {
        labels.push('Successful Landings');
        data.push(landings);
        backgroundColor.push('rgba(76, 175, 80, 0.6)');
        borderColor.push('rgba(76, 175, 80, 1)');
    }
    
    if (statsData.crashes !== undefined) {
        labels.push('Crashes');
        data.push(crashes);
        backgroundColor.push('rgba(244, 67, 54, 0.6)');
        borderColor.push('rgba(244, 67, 54, 1)');
    }
    
    if (statsData.ejections !== undefined) {
        labels.push('Ejections');
        data.push(ejections);
        backgroundColor.push('rgba(255, 152, 0, 0.6)');
        borderColor.push('rgba(255, 152, 0, 1)');
    }
    
    if (statsData.takeoffs !== undefined && takeoffs > (landings + crashes + ejections)) {
        labels.push('In Flight');
        data.push(Math.max(0, takeoffs - landings - crashes - ejections));
        backgroundColor.push('rgba(158, 158, 158, 0.6)');
        borderColor.push('rgba(158, 158, 158, 1)');
    }
    
    // Don't create chart if no data
    if (labels.length === 0) {
        const chartWrapper = ctx.parentElement.parentElement;
        if (chartWrapper) {
            chartWrapper.style.display = 'none';
        }
        return;
    }
    
    flightChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: data,
                backgroundColor: backgroundColor,
                borderColor: borderColor,
                borderWidth: 1
            }]
        },
        options: {
            ...chartOptions,
            scales: {} // Remove scales for doughnut chart
        }
    });
}

function createAircraftChart(aircraftData) {
    const ctx = document.getElementById('aircraftChart').getContext('2d');
    
    // Show the wrapper
    document.getElementById('aircraftChartWrapper').style.display = 'block';
    
    // Destroy existing chart if it exists
    if (aircraftChart) {
        aircraftChart.destroy();
    }
    
    const labels = aircraftData.map(a => a.name);
    const data = aircraftData.map(a => a.count);
    
    // Generate colors for each aircraft
    const colors = [
        'rgba(76, 175, 80, 0.6)',
        'rgba(33, 150, 243, 0.6)',
        'rgba(255, 193, 7, 0.6)',
        'rgba(233, 30, 99, 0.6)',
        'rgba(156, 39, 176, 0.6)'
    ];
    
    const borderColors = colors.map(c => c.replace('0.6', '1'));
    
    aircraftChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Times Used',
                data: data,
                backgroundColor: colors.slice(0, data.length),
                borderColor: borderColors.slice(0, data.length),
                borderWidth: 1
            }]
        },
        options: {
            ...chartOptions,
            indexAxis: 'y', // Horizontal bar chart
            plugins: {
                ...chartOptions.plugins,
                legend: {
                    display: false
                }
            },
            scales: {
                ...chartOptions.scales,
                x: {
                    ...chartOptions.scales.x,
                    beginAtZero: true,
                    ticks: {
                        ...chartOptions.scales.x.ticks,
                        stepSize: 1
                    },
                    title: {
                        display: true,
                        text: 'Times Used',
                        color: '#4CAF50',
                        font: {
                            size: 14,
                            weight: 'bold'
                        }
                    }
                },
                y: {
                    ...chartOptions.scales.y,
                    title: {
                        display: true,
                        text: 'Aircraft Type',
                        color: '#4CAF50',
                        font: {
                            size: 14,
                            weight: 'bold'
                        }
                    }
                }
            }
        }
    });
}

function createTrapScoresChart(trapScores) {
    const ctx = document.getElementById('trapScoresChart').getContext('2d');
    
    // Show the wrapper
    document.getElementById('trapScoresChartWrapper').style.display = 'block';
    
    // Destroy existing chart if it exists
    if (trapScoresChart) {
        trapScoresChart.destroy();
    }
    
    // Create histogram data for trap scores with carrier-specific grading
    const scoreBuckets = {
        'OK (4.0)': 0,
        'Fair (3.0-3.9)': 0,
        'No Grade (2.0-2.9)': 0,
        'Cut (1.0-1.9)': 0,
        'Wave Off (0-0.9)': 0
    };
    
    trapScores.forEach(score => {
        if (score >= 4) scoreBuckets['OK (4.0)']++;
        else if (score >= 3) scoreBuckets['Fair (3.0-3.9)']++;
        else if (score >= 2) scoreBuckets['No Grade (2.0-2.9)']++;
        else if (score >= 1) scoreBuckets['Cut (1.0-1.9)']++;
        else scoreBuckets['Wave Off (0-0.9)']++;
    });
    
    const labels = Object.keys(scoreBuckets);
    const data = Object.values(scoreBuckets);
    
    // Generate colors matching carrier grading standards
    const colors = [
        'rgba(76, 175, 80, 0.6)',   // OK - green (perfect)
        'rgba(255, 193, 7, 0.6)',   // Fair - yellow
        'rgba(158, 158, 158, 0.6)', // No Grade - gray
        'rgba(255, 152, 0, 0.6)',   // Cut - orange (dangerous)
        'rgba(244, 67, 54, 0.6)'    // Wave Off - red
    ];
    
    const borderColors = colors.map(c => c.replace('0.6', '1'));
    
    trapScoresChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Number of Traps',
                data: data,
                backgroundColor: colors,
                borderColor: borderColors,
                borderWidth: 1
            }]
        },
        options: {
            ...chartOptions,
            plugins: {
                ...chartOptions.plugins,
                legend: {
                    display: false
                },
                tooltip: {
                    ...chartOptions.plugins.tooltip,
                    callbacks: {
                        afterLabel: function(context) {
                            const total = trapScores.length;
                            const percentage = ((context.parsed.y / total) * 100).toFixed(1);
                            return `${percentage}% of total traps`;
                        }
                    }
                }
            },
            scales: {
                ...chartOptions.scales,
                x: {
                    ...chartOptions.scales.x,
                    title: {
                        display: true,
                        text: 'Landing Grade',
                        color: '#4CAF50',
                        font: {
                            size: 14,
                            weight: 'bold'
                        }
                    }
                },
                y: {
                    ...chartOptions.scales.y,
                    beginAtZero: true,
                    ticks: {
                        ...chartOptions.scales.y.ticks,
                        stepSize: 1
                    },
                    title: {
                        display: true,
                        text: 'Number of Carrier Traps',
                        color: '#4CAF50',
                        font: {
                            size: 14,
                            weight: 'bold'
                        }
                    }
                }
            }
        }
    });
}

// Allow Enter key to trigger search
document.getElementById('playerSearchInput').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        searchForPlayers();
    }
});
</script>

<style>
/* Main content uses responsive styling from styles.css */

/* Pilot card styling moved to unified styles.css */

/* Search styling moved to unified styles.css */

/* Results styling moved to unified styles.css */

/* Squadron styling moved to unified styles.css */

.charts-container {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 25px;
    margin: 40px auto 0;
    max-width: 1000px;
}

@media (max-width: 768px) {
    .charts-container {
        grid-template-columns: 1fr;
    }
}

.chart-wrapper {
    background-color: #1e1e1e;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.3);
}

.chart-wrapper h4 {
    color: #4CAF50;
    margin-bottom: 20px;
    text-align: center;
    font-size: 1.2rem;
}

.chart-wrapper canvas {
    max-height: 250px;
}

.chart-info {
    display: inline-block;
    width: 16px;
    height: 16px;
    line-height: 16px;
    text-align: center;
    background-color: #444;
    color: #ccc;
    border-radius: 50%;
    font-size: 12px;
    margin-left: 5px;
    cursor: help;
    transition: all 0.3s ease;
}

.chart-info:hover {
    background-color: #4CAF50;
    color: white;
    transform: scale(1.1);
}

.chart-wrapper {
    position: relative;
}

.chart-wrapper:hover {
    box-shadow: 0 0 15px rgba(76, 175, 80, 0.3);
}

.chart-wrapper[title] {
    cursor: help;
}

.no-stats-message {
    text-align: center;
    padding: 40px 20px;
    color: #888;
    background-color: #1a1a1a;
    border-radius: 8px;
    margin: 20px 0;
}

.no-stats-message p {
    margin: 10px 0;
    font-size: 1rem;
}

.no-stats-message p:first-child {
    font-size: 1.2rem;
    color: #ccc;
}

/* Enhanced tooltip styling */
.chart-wrapper:hover::after {
    content: attr(title);
    position: absolute;
    bottom: 100%;
    left: 50%;
    transform: translateX(-50%);
    background-color: #333;
    color: #fff;
    padding: 8px 12px;
    border-radius: 6px;
    font-size: 14px;
    white-space: nowrap;
    z-index: 1000;
    pointer-events: none;
    opacity: 0;
    animation: fadeIn 0.3s forwards;
    margin-bottom: 10px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
}

.chart-wrapper:hover::before {
    content: '';
    position: absolute;
    bottom: 100%;
    left: 50%;
    transform: translateX(-50%);
    border: 8px solid transparent;
    border-top-color: #333;
    margin-bottom: 2px;
    opacity: 0;
    animation: fadeIn 0.3s forwards;
}

@keyframes fadeIn {
    to {
        opacity: 1;
    }
}
</style>

<?php include 'footer.php'; ?>
