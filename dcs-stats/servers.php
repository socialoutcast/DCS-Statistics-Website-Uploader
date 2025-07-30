<?php
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
    <h1>Server Status</h1>
    
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
                        <th>Players</th>
                        <th>Map</th>
                        <th>Mission</th>
                        <th>Uptime</th>
                    </tr>
                </thead>
                <tbody></tbody>
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
        const response = await fetch('get_servers.php');
        const data = await response.json();
        
        document.getElementById('servers-loading').style.display = 'none';
        
        if (!data || data.error || !data.servers || data.servers.length === 0) {
            document.getElementById('no-servers').style.display = 'block';
            return;
        }
        
        const tbody = document.querySelector('#serversTable tbody');
        tbody.innerHTML = '';
        
        data.servers.forEach(server => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${escapeHtml(server.name || 'Unknown')}</td>
                <td><span class="status-${server.status || 'offline'}">${escapeHtml(server.status || 'Offline')}</span></td>
                <td>${escapeHtml(String(server.players || 0))}/${escapeHtml(String(server.max_players || 0))}</td>
                <td>${escapeHtml(server.map || 'N/A')}</td>
                <td>${escapeHtml(server.mission || 'N/A')}</td>
                <td>${escapeHtml(server.uptime || 'N/A')}</td>
            `;
            tbody.appendChild(row);
        });
        
        document.getElementById('servers-container').style.display = 'block';
        
    } catch (error) {
        console.error('Error loading servers:', error);
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
}

#serversTable th {
    background-color: #1e1e1e;
    padding: 12px;
    text-align: left;
    border-bottom: 2px solid #4CAF50;
    color: #4CAF50;
}

#serversTable td {
    padding: 10px;
    border-bottom: 1px solid #444;
}

#serversTable tr:hover {
    background-color: #3a3a3a;
}

.status-online {
    color: #4CAF50;
    font-weight: bold;
}

.status-offline {
    color: #f44336;
    font-weight: bold;
}

.status-starting {
    color: #ff9800;
    font-weight: bold;
}
</style>

<?php include 'footer.php'; ?>