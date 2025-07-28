<?php include 'header.php'; ?>
<?php include 'nav.php'; ?>

<main>
    <h2>Pilot Statistics</h2>
    
    <div class="search-container">
        <input type="text" id="playerSearchInput" placeholder="Search for a pilot..." />
        <button onclick="searchPilot()">Search</button>
    </div>
    
    <div id="search-results" style="display: none;">
        <div id="pilot-card" class="pilot-card">
            <h3 id="pilot-name"></h3>
            <div class="pilot-stats">
                <div class="stat-group">
                    <h4>Combat Statistics</h4>
                    <div class="stats-grid">
                        <div class="stat-item">
                            <span class="stat-label">Kills:</span>
                            <span class="stat-value" id="pilot-kills">0</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">Sorties:</span>
                            <span class="stat-value" id="pilot-sorties">0</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">Takeoffs:</span>
                            <span class="stat-value" id="pilot-takeoffs">0</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">Landings:</span>
                            <span class="stat-value" id="pilot-landings">0</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">Crashes:</span>
                            <span class="stat-value" id="pilot-crashes">0</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">Ejections:</span>
                            <span class="stat-value" id="pilot-ejections">0</span>
                        </div>
                    </div>
                </div>
                
                <div class="stat-group">
                    <h4>Credits & Squadron</h4>
                    <div class="stats-grid">
                        <div class="stat-item">
                            <span class="stat-label">Credits:</span>
                            <span class="stat-value" id="pilot-credits">0</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">Most Used Aircraft:</span>
                            <span class="stat-value" id="pilot-aircraft">Unknown</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">Squadron:</span>
                            <span class="stat-value" id="pilot-squadron">None</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div id="no-results" style="display: none; text-align: center; color: #ccc; margin-top: 30px;">
        <p>No pilot found with that name. Please check the spelling and try again.</p>
    </div>
    
    <div id="loading" style="display: none; text-align: center; color: #ccc; margin-top: 30px;">
        <p>Searching...</p>
    </div>
</main>

<script>
async function searchPilot() {
    const searchInput = document.getElementById('playerSearchInput');
    const playerName = searchInput.value.trim();
    
    if (!playerName) {
        alert('Please enter a pilot name to search.');
        return;
    }
    
    // Show loading state
    document.getElementById('search-results').style.display = 'none';
    document.getElementById('no-results').style.display = 'none';
    document.getElementById('loading').style.display = 'block';
    
    try {
        // Get player stats
        const statsResponse = await fetch(`get_player_stats.php?name=${encodeURIComponent(playerName)}`);
        const statsData = await statsResponse.json();
        
        if (statsData.error) {
            document.getElementById('loading').style.display = 'none';
            document.getElementById('no-results').style.display = 'block';
            return;
        }
        
        // Get credits data
        let credits = 0;
        try {
            const creditsResponse = await fetch('get_credits.php');
            const creditsData = await creditsResponse.json();
            const playerCredits = creditsData.find(p => p.name.toLowerCase() === playerName.toLowerCase());
            credits = playerCredits ? playerCredits.credits : 0;
        } catch (e) {
            console.warn('Could not load credits data:', e);
        }
        
        // Get squadron data
        let squadron = 'None';
        try {
            // Try to get squadron data from squadrons endpoint
            const squadronResponse = await fetch('data/squadron_members.json');
            if (squadronResponse.ok) {
                const squadronText = await squadronResponse.text();
                const squadronMembers = squadronText.trim().split('\n').map(line => JSON.parse(line));
                
                // Get player UCID to match with squadron
                const playersResponse = await fetch('data/players.json');
                if (playersResponse.ok) {
                    const playersText = await playersResponse.text();
                    const players = playersText.trim().split('\n').map(line => JSON.parse(line));
                    const player = players.find(p => p.name.toLowerCase() === playerName.toLowerCase());
                    
                    if (player) {
                        const memberData = squadronMembers.find(m => m.player_ucid === player.ucid);
                        if (memberData) {
                            // Get squadron name
                            const squadronsResponse = await fetch('data/squadrons.json');
                            if (squadronsResponse.ok) {
                                const squadronsText = await squadronsResponse.text();
                                const squadrons = squadronsText.trim().split('\n').map(line => JSON.parse(line));
                                const squadronInfo = squadrons.find(s => s.id === memberData.squadron_id);
                                squadron = squadronInfo ? squadronInfo.name : 'Unknown Squadron';
                            }
                        }
                    }
                }
            }
        } catch (e) {
            console.warn('Could not load squadron data:', e);
        }
        
        // Populate the results
        document.getElementById('pilot-name').textContent = statsData.name;
        document.getElementById('pilot-kills').textContent = statsData.kills || 0;
        document.getElementById('pilot-sorties').textContent = statsData.sorties || 0;
        document.getElementById('pilot-takeoffs').textContent = statsData.takeoffs || 0;
        document.getElementById('pilot-landings').textContent = statsData.landings || 0;
        document.getElementById('pilot-crashes').textContent = statsData.crashes || 0;
        document.getElementById('pilot-ejections').textContent = statsData.ejections || 0;
        document.getElementById('pilot-credits').textContent = credits;
        document.getElementById('pilot-aircraft').textContent = statsData.mostUsedAircraft || 'Unknown';
        document.getElementById('pilot-squadron').textContent = squadron;
        
        // Show results
        document.getElementById('loading').style.display = 'none';
        document.getElementById('search-results').style.display = 'block';
        
    } catch (error) {
        console.error('Error searching for pilot:', error);
        document.getElementById('loading').style.display = 'none';
        document.getElementById('no-results').style.display = 'block';
    }
}

// Allow Enter key to trigger search
document.getElementById('playerSearchInput').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        searchPilot();
    }
});
</script>

<style>
.pilot-card {
    background-color: #2c2c2c;
    border-radius: 12px;
    padding: 30px;
    margin: 20px auto;
    max-width: 800px;
    color: #fff;
    box-shadow: 0 0 15px rgba(0, 0, 0, 0.5);
}

.pilot-card h3 {
    text-align: center;
    margin-bottom: 30px;
    font-size: 1.8rem;
    color: #4CAF50;
}

.stat-group {
    margin-bottom: 30px;
}

.stat-group h4 {
    color: #ccc;
    margin-bottom: 15px;
    border-bottom: 1px solid #444;
    padding-bottom: 5px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
}

.stat-item {
    display: flex;
    justify-content: space-between;
    padding: 10px;
    background-color: #1e1e1e;
    border-radius: 5px;
}

.stat-label {
    color: #ccc;
}

.stat-value {
    color: #fff;
    font-weight: bold;
}

.search-container {
    text-align: center;
    margin: 30px 0;
}

.search-container input {
    padding: 12px;
    width: 300px;
    max-width: 80%;
    font-size: 16px;
    border: 1px solid #444;
    border-radius: 5px;
    background-color: #1f2b34;
    color: white;
    margin-right: 10px;
}

.search-container button {
    padding: 12px 20px;
    font-size: 16px;
    background-color: #4CAF50;
    color: white;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    transition: background-color 0.3s;
}

.search-container button:hover {
    background-color: #45a049;
}
</style>

<?php include 'footer.php'; ?>
