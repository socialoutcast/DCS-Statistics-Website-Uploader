<?php 
// Start session before any output
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include "header.php"; 
require_once __DIR__ . '/site_features.php';
include "nav.php"; ?>

<main class="container">
  <div class="dashboard-header">
    <h1>Leaderboard</h1>
    <p class="dashboard-subtitle">Top 10 pilots ranked by air-to-air kills</p>
  </div>
  <div id="leaderboard-loading">Loading leaderboard...</div>

  <div id="top3-wrapper">
    <div class="top-3-container" id="top3-leaderboard"></div>
  </div>

  <div class="table-responsive">
    <table id="leaderboardTable">
      <thead>
        <tr>
          <th>Rank</th>
          <th>Name</th>
          <?php if (isFeatureEnabled('leaderboard_kills')): ?>
          <th>Kills</th>
          <?php endif; ?>
          <?php if (isFeatureEnabled('leaderboard_sorties')): ?>
          <th>Sorties</th>
          <?php endif; ?>
          <th>Takeoffs</th>
          <th>Landings</th>
          <th>Crashes</th>
          <th>Ejections</th>
          <?php if (isFeatureEnabled('leaderboard_aircraft')): ?>
          <th>Most Used Aircraft</th>
          <?php endif; ?>
        </tr>
      </thead>
      <tbody></tbody>
    </table>
  </div>
</main>

<script>
let leaderboardData = [];

function renderTable() {
  const tbody = document.querySelector("#leaderboardTable tbody");
  
  // Only show top 10 players
  const top10Data = leaderboardData.slice(0, 10);

  tbody.innerHTML = "";
  top10Data.forEach(player => {
    const row = document.createElement("tr");
    let cells = `
      <td>${escapeHtml(String(player.rank))}</td>
      <td>${escapeHtml(player.name || '')}</td>`;
    
    <?php if (isFeatureEnabled('leaderboard_kills')): ?>
    cells += `<td>${escapeHtml(String(player.kills || 0))}</td>`;
    <?php endif; ?>
    
    <?php if (isFeatureEnabled('leaderboard_sorties')): ?>
    cells += `<td>${escapeHtml(String(player.sorties || 0))}</td>`;
    <?php endif; ?>
    
    cells += `
      <td>${escapeHtml(String(player.takeoffs || 0))}</td>
      <td>${escapeHtml(String(player.landings || 0))}</td>
      <td>${escapeHtml(String(player.crashes || 0))}</td>
      <td>${escapeHtml(String(player.ejections || 0))}</td>`;
    
    <?php if (isFeatureEnabled('leaderboard_aircraft')): ?>
    cells += `<td>${escapeHtml(player.most_used_aircraft || '')}</td>`;
    <?php endif; ?>
    
    row.innerHTML = cells;
    tbody.appendChild(row);
  });
}

async function loadLeaderboardFromMissionstats() {
  try {
    // Use the client-side API
    const result = await window.dcsAPI.getLeaderboard();
    
    // Handle both direct array response and wrapped response
    let data = result;
    if (result.data && Array.isArray(result.data)) {
        data = result.data;
        // Show data source if available
        if (result.source) {
        }
    }
    // Only keep top 10 players
    leaderboardData = data.slice(0, 10);
    document.getElementById("leaderboard-loading").style.display = "none";
    
    // Populate top 3 leaderboard
    const top3Container = document.getElementById("top3-leaderboard");
    const trophies = ['🥇', '🥈', '🥉'];
    leaderboardData.slice(0, 3).forEach((player, i) => {
        const box = document.createElement("div");
        box.className = "trophy-box";
        box.innerHTML = `<span class="trophy">${trophies[i]}</span><strong>${escapeHtml(player.name || '')}</strong><br>${escapeHtml(String(player.kills || 0))} kills`;
        top3Container.appendChild(box);
    });
    
    renderTable();
  } catch (error) {
    document.getElementById("leaderboard-loading").innerText = "Failed to load leaderboard data. Please try again later.";
    console.error("Error loading leaderboard:", error);
  }
}

// Load the leaderboard
loadLeaderboardFromMissionstats();
</script>

<?php include "footer.php"; ?>
