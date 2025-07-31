<?php
// Start session before any output
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'header.php';
require_once __DIR__ . '/site_features.php';
include 'nav.php';

if (!isFeatureEnabled('squadrons_enabled')):
?>
<main>
    <div class="alert" style="text-align: center; padding: 50px;">
        <h2>Squadron System Disabled</h2>
        <p>The squadron system is currently disabled for this server.</p>
    </div>
</main>
<?php include 'footer.php'; exit; ?>
<?php endif; ?>

<main>
    <div class="dashboard-header">
        <h1>Squadrons</h1>
        <p class="dashboard-subtitle">Squadron rankings and member information</p>
    </div>

    <div class="search-container">
        <input type="text" id="searchInput" placeholder="Search squadrons, members, or credits...">
    </div>

    <div class="table-responsive">
        <table id="squadronsTable" style="width: 100%;">
            <thead>
                <tr>
                    <th style="width: 10%;">Logo</th>
                    <th style="width: 40%;">Name</th>
                    <th style="width: 50%;">Description</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>

    <?php if (isFeatureEnabled('squadron_management')): ?>
    <h2>Squadron Members</h2>
    <div class="table-responsive">
        <table id="membersTable" style="width: 100%;">
            <thead>
                <tr>
                    <th style="width: 10%;">Logo</th>
                    <th style="width: 30%;">Squadron Name</th>
                    <th style="width: 60%;">Member</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
    <?php endif; ?>

    <?php if (isFeatureEnabled('squadron_statistics') && isFeatureEnabled('credits_enabled')): ?>
    <h2>Squadron Leaderboard</h2>
    <div class="table-responsive">
        <table id="leaderboardTable" style="width: 100%;">
            <thead>
                <tr>
                    <th style="width: 10%;">Logo</th>
                    <th style="width: 60%;">Squadron Name</th>
                    <th style="width: 30%;">Credits</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
    <?php endif; ?>

    <div id="error-message" style="color: red; text-align: center;"></div>
</main>

<script>
document.addEventListener("DOMContentLoaded", () => {
    const searchInput = document.getElementById('searchInput');

    // Helper to build URLs
    const basePath = window.DCS_CONFIG ? window.DCS_CONFIG.basePath : '';
    const buildUrl = (path) => basePath ? `${basePath}/${path}` : path;
    
    // Load squadron data from API
    async function loadSquadronData() {
        try {
            // First, get the list of squadrons
            const squadronsResponse = await fetch(buildUrl('get_squadrons.php'));
            if (!squadronsResponse.ok) {
                throw new Error('Failed to load squadrons');
            }
            const squadronsData = await squadronsResponse.json();
            const squadrons = squadronsData.data || [];
            
            // Load players for member name mapping
            const playersResponse = await fetch(buildUrl('search_players.php?search=&limit=1000'));
            if (!playersResponse.ok) {
                throw new Error('Failed to load players');
            }
            const playersData = await playersResponse.json();
            const players = playersData.results || playersData.data || [];
            
            // Load member and credit data for each squadron
            const allMembers = [];
            const allCredits = [];
            
            for (const squadron of squadrons) {
                // Get squadron members
                try {
                    const membersResp = await fetch(buildUrl('get_squadron_members.php'), {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ name: squadron.name })
                    });
                    if (membersResp.ok) {
                        const membersData = await membersResp.json();
                        if (membersData.data) {
                            allMembers.push(...membersData.data);
                        }
                    }
                } catch (error) {
                    console.warn(`Failed to load members for squadron ${squadron.name}:`, error);
                }
                
                // Get squadron credits
                try {
                    const creditsResp = await fetch(buildUrl('get_squadron_credits.php'), {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ name: squadron.name })
                    });
                    if (creditsResp.ok) {
                        const creditsData = await creditsResp.json();
                        if (creditsData.data) {
                            // Add squadron_id to credits data for compatibility
                            const credits = creditsData.data;
                            if (credits && typeof credits === 'object') {
                                credits.squadron_id = squadron.id;
                                allCredits.push(credits);
                            }
                        }
                    }
                } catch (error) {
                    console.warn(`Failed to load credits for squadron ${squadron.name}:`, error);
                }
            }
            
            return { 
                squadrons, 
                members: allMembers, 
                players, 
                credits: allCredits 
            };
            
        } catch (error) {
            console.error('Error loading squadron data:', error);
            throw error;
        }
    }
    
    // Main execution
    loadSquadronData().then(({ squadrons, members, players, credits }) => {

        const squadronsById = Object.fromEntries(squadrons.map(sq => [sq.id, sq]));
        const playersByUcid = Object.fromEntries(players.map(p => [p.ucid, p]));

        const squadronBody = document.querySelector('#squadronsTable tbody');
        const membersBody = document.querySelector('#membersTable tbody');
        const leaderboardBody = document.querySelector('#leaderboardTable tbody');

        const medals = ['🥇', '🥈', '🥉'];

        function renderTables(filter = '') {
            squadronBody.innerHTML = '';
            membersBody.innerHTML = '';
            leaderboardBody.innerHTML = '';

            // === Squadrons Table ===
            squadrons.forEach(sq => {
                if (!sq.name.toLowerCase().includes(filter) && !sq.description.toLowerCase().includes(filter)) return;

                const row = document.createElement('tr');
                row.innerHTML = `
                    <td><img src="${escapeHtml(sq.image_url || '')}" alt="${escapeHtml(sq.name || '')}" style="width: 80px;"></td>
                    <td>${escapeHtml(sq.name || '')}</td>
                    <td>${escapeHtml(sq.description || '')}</td>
                `;
                squadronBody.appendChild(row);
            });

            // === Members Table ===
            squadrons.forEach((sq, index) => {
                const relevantMembers = members.filter(m => m.squadron_id === sq.id);
                const groupId = "group-" + index;
                const lowerName = sq.name.toLowerCase();
                const matchFound = lowerName.includes(filter) || relevantMembers.some(m => playersByUcid[m.player_ucid]?.name.toLowerCase().includes(filter));
                if (!matchFound) return;

                const headerRow = document.createElement('tr');
                headerRow.classList.add('toggle-header');
                headerRow.style.cursor = "pointer";
                headerRow.innerHTML = `
                    <td><img src="${escapeHtml(sq.image_url || '')}" alt="${escapeHtml(sq.name || '')}" style="width: 60px;"></td>
                    <td>${escapeHtml(sq.name || '')}</td>
                    <td><em>Click to show/hide members</em></td>
                `;
                membersBody.appendChild(headerRow);

                relevantMembers.forEach(member => {
                    const player = playersByUcid[member.player_ucid];
                    if (!player || !player.name.toLowerCase().includes(filter) && !lowerName.includes(filter)) return;

                    const row = document.createElement('tr');
                    row.classList.add(groupId);
                    row.style.display = "none";
                    row.innerHTML = `
                        <td></td>
                        <td></td>
                        <td>${escapeHtml(player.name || '')}</td>
                    `;
                    membersBody.appendChild(row);
                });

                headerRow.addEventListener('click', () => {
                    const groupRows = document.querySelectorAll('.' + groupId);
                    groupRows.forEach(r => {
                        r.style.display = (r.style.display === 'none') ? 'table-row' : 'none';
                    });
                });
            });

            // === Leaderboard Table ===
            credits
                .sort((a, b) => b.points - a.points)
                .forEach((entry, index) => {
                    const squadron = squadronsById[entry.squadron_id];
                    if (!squadron) return;
                    if (!squadron.name.toLowerCase().includes(filter)) return;

                    const medal = medals[index] || '';
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td><img src="${escapeHtml(squadron.image_url || '')}" alt="${escapeHtml(squadron.name || '')}" style="width: 60px;"></td>
                        <td>${medal} ${escapeHtml(squadron.name || '')}</td>
                        <td>${escapeHtml(String(entry.points || 0))}</td>
                    `;
                    leaderboardBody.appendChild(row);
                });
        }

        searchInput.addEventListener('input', () => {
            const filter = searchInput.value.toLowerCase().trim();
            renderTables(filter);
        });

        renderTables(); // Initial load
    }).catch(err => {
        console.error('Error loading squadron data:', err);
        document.querySelector('main').innerHTML = `
            <div class="alert" style="text-align: center; padding: 50px;">
                <h2>Error Loading Squadron Data</h2>
                <p>${err.message}</p>
                <p>Please check your configuration and try again.</p>
            </div>
        `;
    });
});
</script>

<?php
include 'footer.php';
?>
