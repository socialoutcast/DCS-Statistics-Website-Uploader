
<?php include 'header.php'; ?>
<?php include 'nav.php'; ?>

<main class="container">
  <h2>Leaderboard</h2>

  <div class="search-container">
    <input type="text" id="searchInput" placeholder="Search for a player...">
  </div>

  <div class="table-responsive">
    <table id="leaderboardTable">
      <thead>
        <tr>
          <th>Rank</th>
          <th>Pilot</th>
          <th>Kills</th>
          <th>Deaths</th>
          <th>K/D Ratio</th>
        </tr>
      </thead>
      <tbody></tbody>
    </table>
  </div>

  <div id="pagination" class="pagination-controls"></div>
</main>

<?php include 'footer.php'; ?>

<script>
let leaderboardData = [];
let currentPage = 1;
const resultsPerPage = 20;

fetch('get_missionstats.php')
  .then(response => response.json())
  .then(data => {
    leaderboardData = data.sort((a, b) => b.kills - a.kills);
    renderTable();
  })
  .catch(error => {
    console.error("Error fetching mission stats:", error);
  });

function renderTable() {
  const searchQuery = document.getElementById("searchInput").value.toLowerCase();
  const filteredData = leaderboardData.filter(player =>
    player.name.toLowerCase().includes(searchQuery)
  );

  const tableBody = document.querySelector("#leaderboardTable tbody");
  tableBody.innerHTML = "";

  const start = (currentPage - 1) * resultsPerPage;
  const paginatedItems = filteredData.slice(start, start + resultsPerPage);

  paginatedItems.forEach((player, index) => {
    const row = document.createElement("tr");
    row.innerHTML = `
      <td>${start + index + 1}</td>
      <td>${player.name}</td>
      <td>${player.kills}</td>
      <td>${player.deaths}</td>
      <td>${(player.kills / (player.deaths || 1)).toFixed(2)}</td>
    `;
    tableBody.appendChild(row);
  });

  renderPagination(filteredData.length);
}

function renderPagination(totalItems) {
  const totalPages = Math.ceil(totalItems / resultsPerPage);
  const pagination = document.getElementById("pagination");
  pagination.innerHTML = `
    <button onclick="changePage(-1)" ${currentPage === 1 ? "disabled" : ""}>Previous</button>
    <span>Page ${currentPage} of ${totalPages}</span>
    <button onclick="changePage(1)" ${currentPage === totalPages ? "disabled" : ""}>Next</button>
  `;
}

function changePage(delta) {
  currentPage += delta;
  renderTable();
}

document.getElementById("searchInput").addEventListener("input", () => {
  currentPage = 1;
  renderTable();
});
</script>
