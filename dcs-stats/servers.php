<?php
// Start session before any output
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'header.php';
require_once __DIR__ . '/site_features.php';
include 'nav.php';

if (!isFeatureEnabled('nav_servers')):
?>
<main>
    <div class="alert" style="text-align: center; padding: 50px;">
        <h2>Server Status Disabled</h2>
        <p>The server status page is currently disabled.</p>
    </div>
</main>
<?php include 'footer.php'; exit; ?>
<?php endif; ?>

<main>
    <div class="dashboard-header">
        <h1>Server Status</h1>
        <p class="dashboard-subtitle">Live DCS server information and player counts</p>
    </div>
    
    <div id="servers-loading" style="text-align: center; padding: 50px;">
        <p>Loading server information...</p>
    </div>
    
    <div id="servers-container" style="display: none;">
        <div class="table-responsive">
            <table id="serversTable">
                <thead>
                    <tr>
                        <th>Server Name</th>
                        <th>Status</th>
                        <th>Address</th>
                        <th>Password</th>
                        <th>Mission</th>
                        <th>Theatre</th>
                        <th>Players</th>
                        <th>Uptime</th>
                    </tr>
                </thead>
                <tbody id="serversTableBody"></tbody>
            </table>
        </div>
    </div>
    
    <div id="no-servers" style="display: none; text-align: center; padding: 50px;">
        <p>No server information available.</p>
    </div>
</main>

<script>
async function loadServers() {
    try {
        // Use client-side API
        const result = await window.dcsAPI.getServers();
        
        document.getElementById('servers-loading').style.display = 'none';
        
        // Handle both direct data and wrapped response
        const data = result.data || result;
        
        if (!data || result.error || data.length === 0) {
            document.getElementById('no-servers').style.display = 'block';
            return;
        }
        
        const tbody = document.getElementById('serversTableBody');
        tbody.innerHTML = '';
        
        // Handle both array and object with servers property
        const servers = Array.isArray(data) ? data : (data.servers || []);
        
        servers.forEach((server, index) => {
            
            const row = document.createElement('tr');
            
            // Determine status class
            const statusClass = server.status ? `status-${server.status.toLowerCase()}` : 'status-unknown';
            
            // Extract mission data if available
            let missionName = 'N/A';
            let theatre = 'N/A';
            let playerCount = 'N/A';
            let uptime = 'N/A';
            
            if (server.mission) {
                missionName = server.mission.name || 'N/A';
                theatre = server.mission.theatre || 'N/A';
                
                // Calculate player counts
                const blueUsed = server.mission.blue_slots_used || 0;
                const blueTotal = server.mission.blue_slots || 0;
                const redUsed = server.mission.red_slots_used || 0;
                const redTotal = server.mission.red_slots || 0;
                const totalUsed = blueUsed + redUsed;
                const totalSlots = blueTotal + redTotal;
                
                playerCount = `${totalUsed}/${totalSlots} (B:${blueUsed}/${blueTotal} R:${redUsed}/${redTotal})`;
                
                // Format uptime
                if (server.mission.uptime !== undefined) {
                    const hours = Math.floor(server.mission.uptime / 3600);
                    const minutes = Math.floor((server.mission.uptime % 3600) / 60);
                    uptime = `${hours}h ${minutes}m`;
                }
            }
            
            // Check if password protected
            const passwordStatus = server.password ? '🔒 Yes' : '🔓 No';
            
            // Create cells individually to ensure proper count
            const cells = [
                `<td>${escapeHtml(server.name || 'Unknown')}</td>`,
                `<td><span class="${statusClass}">${escapeHtml(server.status || 'Unknown')}</span></td>`,
                `<td>${escapeHtml(server.address || 'N/A')}</td>`,
                `<td>${passwordStatus}</td>`,
                `<td>${escapeHtml(missionName)}</td>`,
                `<td>${escapeHtml(theatre)}</td>`,
                `<td>${escapeHtml(playerCount)}</td>`,
                `<td>${escapeHtml(uptime)}</td>`
            ];
            
            row.innerHTML = cells.join('');
            tbody.appendChild(row);
        });
        
        document.getElementById('servers-container').style.display = 'block';
        
    } catch (error) {
        // Error loading servers
        document.getElementById('servers-loading').style.display = 'none';
        document.getElementById('no-servers').style.display = 'block';
    }
}

// Load servers on page load
document.addEventListener('DOMContentLoaded', loadServers);

// Refresh every 30 seconds
setInterval(loadServers, 30000);
</script>

<style>
.table-responsive {
    overflow-x: auto;
    margin: 20px 0;
}

#serversTable {
    width: 100%;
    border-collapse: collapse;
    background-color: #2c2c2c;
    color: #fff;
    table-layout: auto;
}

#serversTable th {
    background-color: #1e1e1e;
    padding: 12px;
    text-align: left;
    border-bottom: 2px solid #4CAF50;
    color: #4CAF50;
    white-space: nowrap;
}

#serversTable td {
    padding: 10px;
    border-bottom: 1px solid #444;
    vertical-align: middle;
}

/* Ensure all content is left-aligned */
#serversTable th, #serversTable td {
    text-align: left;
}

#serversTable tr:hover {
    background-color: #3a3a3a;
}

.status-online, .status-running {
    color: #4CAF50;
    font-weight: bold;
}

.status-offline, .status-shutdown {
    color: #f44336;
    font-weight: bold;
}

.status-starting, .status-paused {
    color: #ff9800;
    font-weight: bold;
}

.status-unknown {
    color: #9e9e9e;
    font-weight: bold;
}
</style>

<?php include 'footer.php'; ?>