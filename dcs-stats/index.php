
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>DCS Statistics Dashboard</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background: #f5f5f5;
            color: #333;
        }
        header {
            background-color: #2c3e50;
            color: white;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem 2rem;
        }
        header .logo {
            display: flex;
            align-items: center;
        }
        header .logo img {
            height: 50px;
            margin-right: 1rem;
        }
        footer {
            background-color: #2c3e50;
            color: white;
            text-align: center;
            padding: 1rem;
            position: relative;
            bottom: 0;
            width: 100%;
            margin-top: 2rem;
        }
        main {
            padding: 2rem;
        }
    </style>
</head>
<body>
<header>
    <div class="logo">
        <img src="A_digital_illustration_of_an_F-14_Tomcat,_a_twin-e.png" alt="F-14 Logo">
        <h1>Player Statistics Dashboard</h1>
    </div>
</header>
<nav style="margin-top: 10px; padding: 10px; background-color: #e3e3e3; text-align: center; font-family: Arial, sans-serif;">
    <a href="index.php" style="margin: 0 15px; text-decoration: none; font-weight: bold; color: #333;">Home</a>
    <a href="https://discord.com" target="_blank" style="margin: 0 15px; text-decoration: none; font-weight: bold; color: #333;">Discord</a>
</nav>

<main>
<?php
$playersData = file_get_contents(__DIR__ . '/data/players.json');
$creditsData = file_get_contents(__DIR__ . '/data/credits.json');

// Parse players line-by-line (NDJSON)
$playersLines = preg_split('/\r\n|\r|\n/', $playersData);
$players = [];
foreach ($playersLines as $line) {
    if (trim($line) === '') continue;
    $player = json_decode($line, true);
    if ($player && isset($player['ucid'])) {
        $players[$player['ucid']] = $player;
    }
}

$creditsLines = preg_split('/\r\n|\r|\n/', file_get_contents(__DIR__ . '/data/credits.json'));
$credits = [];
foreach ($creditsLines as $line) {
    if (trim($line) === '') continue;
    $entry = json_decode($line, true);
    if ($entry && isset($entry['player_ucid'])) {
        $credits[] = $entry;
    }
}

// Calculate credit totals
$playerPoints = [];
foreach ($credits as $entry) {
    $ucid = $entry['player_ucid'];
    $points = $entry['points'] ?? 0;
    $playerPoints[$ucid] = ($playerPoints[$ucid] ?? 0) + $points;
}

$rows = [];
foreach ($playerPoints as $ucid => $points) {
    $name = isset($players[$ucid]) ? $players[$ucid]['name'] : "Unknown";
    if ($points > 10) {
        $lastSeen = isset($players[$ucid]['last_seen']) 
        ? date("d M Y H:i T", strtotime($players[$ucid]['last_seen'])) 
        : "N/A";
    $rows[] = ['name' => $name, 'points' => $points, 'last_seen' => $lastSeen];
    }
}

usort($rows, fn($a, $b) => $b['points'] <=> $a['points']);
?>

<!DOCTYPE html>
<html>
<head><meta charset="UTF-8" />
    <style>
        body { font-family: Arial, sans-serif; background: #1e1e1e; color: #f0f0f0; padding: 20px; }
        h1 { color: #00e676; }
        input[type="text"], select {
            padding: 10px; margin: 10px 0;
            border: none; border-radius: 5px;
            font-size: 16px;
        }
        table { border-collapse: collapse; width: 100%; margin-top: 10px; }
        th, td { padding: 10px; border: 1px solid #444; text-align: left; }
        th { background-color: #333; }
        tr:nth-child(even) { background-color: #2a2a2a; }
        tr:hover { background-color: #444; }
        .pagination { margin-top: 15px; }
        .pagination button {
            padding: 8px 12px; margin-right: 5px;
            background-color: #00e676; border: none;
            cursor: pointer; border-radius: 4px; font-weight: bold;
        }
        .pagination button:disabled {
            background-color: #555; cursor: not-allowed;
        }
    </style>
</head>
<body>
<div class="tabs">
    <button class="tablink active" onclick="openTab(event, 'tab1')">Pilot Credits</button>
    <button class="tablink" onclick="openTab(event, 'tab2')">Leaderboard</button>
    <button class="tablink" onclick="openTab(event, 'tab3')">Pilot Statistics</button>
</div>

<div id="tab1" class="tabcontent" style="display:block;"><input type="text" id="searchInput" placeholder="Search by player name..." onkeyup="filterAndPaginate()" />
<h2>Pilot Credits</h2>
    <label for="rowsPerPage">Rows per page:</label>
    <select id="rowsPerPage" onchange="filterAndPaginate()">
        <option>10</option>
        <option selected>25</option>
        <option>50</option>
        <option>100</option>
    </select>

    <table id="statsTable">
        <thead>
            <tr>
                <th>#</th>
                <th>Player Name</th>
                <th>Credits</th><th>Last Seen</th>
            </tr>
        </thead>
        <tbody id="tableBody"></tbody>
    </table>

    <div class="pagination">
        <button onclick="prevPage()" id="prevBtn">Prev</button>
        <button onclick="nextPage()" id="nextBtn">Next</button>
    </div>

    <script>
        const allData = <?= json_encode($rows) ?>;
        let filteredData = [...allData];
        let currentPage = 1;

        function renderTable() {
            const rowsPerPage = parseInt(document.getElementById("rowsPerPage").value);
            const start = (currentPage - 1) * rowsPerPage;
            const end = start + rowsPerPage;
            const tableBody = document.getElementById("tableBody");

            tableBody.innerHTML = "";

            if (filteredData.length === 0) {
                tableBody.innerHTML = `<tr><td colspan="3">No results found.</td></tr>`;
                return;
            }

            filteredData.slice(start, end).forEach((row, i) => {
                const tr = document.createElement("tr");
                tr.innerHTML = `<td>${start + i + 1}</td><td>${row.name}</td><td>${row.points}</td><td>${row.last_seen}</td>`;
                tableBody.appendChild(tr);
            });

            document.getElementById("prevBtn").disabled = currentPage === 1;
            document.getElementById("nextBtn").disabled = end >= filteredData.length;
        }

        function filterAndPaginate() {
            const query = document.getElementById("searchInput").value.toLowerCase();
            filteredData = allData.filter(row => row.name.toLowerCase().includes(query));
            currentPage = 1;
            renderTable();
        }

        function nextPage() {
            currentPage++;
            renderTable();
        }

        function prevPage() {
            currentPage--;
            renderTable();
        }

        window.onload = renderTable;
    </script>

</div>

<div id="tab2" class="tabcontent" style="display:none;">
<h2>Leaderboard</h2>
<div id="leaderboardTable" class="table-container">Loading leaderboard...</div>
</div>


<div id="tab3" class="tabcontent" style="display:none;">
  <h2>Pilot Statistics</h2>
  <input type="text" id="pilotSearchInput" placeholder="Search by pilot name..." style="margin: 10px; padding: 5px; font-size: 16px;" />
  <div id="pilotStatsResult" style="margin-top: 20px;"></div>
</div>


<script>
function openTab(evt, tabId) {
    document.querySelectorAll('.tabcontent').forEach(el => el.style.display = 'none');
    document.querySelectorAll('.tablink').forEach(el => el.classList.remove('active'));
    document.getElementById(tabId).style.display = 'block';
    evt.currentTarget.classList.add('active');
}
</script>

<style>
.tabs {
    margin-bottom: 20px;
}
.tablink {
    background-color: #333;
    border: none;
    color: white;
    padding: 10px 20px;
    cursor: pointer;
    font-size: 16px;
}
.tablink.active {
    background-color: #00e676;
    color: black;
}
.tabcontent {
    display: none;
}
</style>


<script>
async function loadLeaderboardTable() {
    const res = await fetch('data/leaderboard.json');
    const lines = (await res.text()).split(/\r?\n/).filter(l => l.trim());
    const players = lines.map(l => JSON.parse(l));

    const formatDuration = (s) => {
        const d = Math.floor(s / 86400);
        const h = Math.floor((s % 86400) / 3600);
        const m = Math.floor((s % 3600) / 60);
        return `${d ? d + 'd ' : ''}${h ? h + 'h ' : ''}${m}m`;
    };

    let html = '<table><thead><tr>';
    const cols = Object.keys(players[0]);
    for (const col of cols) html += `<th>${col}</th>`;
    html += '</tr></thead><tbody>';
    for (const row of players) {
        html += '<tr>';
        for (const col of cols) {
            const val = col === 'duration' ? formatDuration(row[col]) : row[col];
            html += `<td>${val}</td>`;
        }
        html += '</tr>';
    }
    html += '</tbody></table>';
    document.getElementById('leaderboardTable').innerHTML = html;
}
document.addEventListener('DOMContentLoaded', function() {
    const tab2 = document.getElementById('tab2');
    if (tab2) {
        const observer = new MutationObserver(() => {
            if (tab2.style.display !== 'none') {
                loadLeaderboardTable();
                observer.disconnect();
            }
        });
        observer.observe(tab2, { attributes: true });
    }
});
</script>


<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
async function setupPilotSearch() {
    const [statsRes, playersRes] = await Promise.all([
        fetch('data/missionstats.json?v=' + Date.now()),
        fetch('data/players.json?v=' + Date.now())
    ]);
    const eventLines = (await statsRes.text()).split(/\r?\n/).filter(Boolean);
    const playerLines = (await playersRes.text()).split(/\r?\n/).filter(Boolean);
    const events = eventLines.map(line => JSON.parse(line));
    const playerMap = {};
    for (const line of playerLines) {
        const player = JSON.parse(line);
        playerMap[player.ucid] = player.name;
    }

    const pilots = {};
    const toDate = s => new Date(s);

    for (const evt of events) {
        const id = evt.init_id;
        if (!id || id === "-1") continue;
        if (!pilots[id]) {
            pilots[id] = {
                ucid: id,
                name: playerMap[id] || "Unknown",
                kills: 0,
                sorties: 0,
                deaths: 0,
                takeoffs: 0,
                landings: 0,
                crashes: 0,
                ejections: 0,
                timestamps: []
            };
        }

        const p = pilots[id];
        const e = evt.event;
        if (evt.time) p.timestamps.push(toDate(evt.time));
        if (e === "S_EVENT_TAKEOFF") { p.sorties++; p.takeoffs++; }
        else if (e === "S_EVENT_KILL") p.kills++;
        else if (e === "S_EVENT_DEAD") p.deaths++;
        else if (e === "S_EVENT_LAND") p.landings++;
        else if (e === "S_EVENT_CRASH") p.crashes++;
        else if (e === "S_EVENT_EJECTION") p.ejections++;
    }

    for (const id in pilots) {
        const p = pilots[id];
        const times = p.timestamps;
        const duration = times.length ? (Math.max(...times.map(d => d.getTime())) - Math.min(...times.map(d => d.getTime()))) / 1000 : 0;
        p.duration = Math.round(duration);
    }

    const formatDuration = s => {
        const d = Math.floor(s / 86400), h = Math.floor((s % 86400) / 3600), m = Math.floor((s % 3600) / 60);
        return `${d ? d + 'd ' : ''}${h ? h + 'h ' : ''}${m}m`;
    };

    const input = document.getElementById("pilotSearchInput");
    const result = document.getElementById("pilotStatsResult");

    input.addEventListener("input", () => {
        const query = input.value.toLowerCase();
        const match = Object.values(pilots).find(p => p.name.toLowerCase().includes(query));
        if (!query || !match) {
            result.innerHTML = "<p>No matching pilot found.</p>";
            return;
        }

        const p = match;
        result.innerHTML = `
            <h3>${p.name}</h3>
            <ul>
                <li>Kills: ${p.kills}</li>
                <li>Sorties: ${p.sorties}</li>
                <li>Duration: ${formatDuration(p.duration)}</li>
                <li>Deaths: ${p.deaths}</li>
                <li>Takeoffs: ${p.takeoffs}</li>
                <li>Landings: ${p.landings}</li>
                <li>Crashes: ${p.crashes}</li>
                <li>Ejections: ${p.ejections}</li>
            </ul>
            <canvas id="pilotChart" width="400" height="300"></canvas>
        `;

        const ctx = document.getElementById('pilotChart').getContext('2d');
        if (window.pilotChartInstance) {
            window.pilotChartInstance.destroy();
        }
        window.pilotChartInstance = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Kills', 'Sorties', 'Deaths', 'Takeoffs', 'Landings', 'Crashes', 'Ejections'],
                datasets: [{
                    label: 'Pilot Stats',
                    data: [p.kills, p.sorties, p.deaths, p.takeoffs, p.landings, p.crashes, p.ejections],
                    backgroundColor: 'rgba(0, 123, 255, 0.6)',
                    borderColor: 'rgba(0, 123, 255, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
    });
}

document.addEventListener('DOMContentLoaded', () => {
    const tab3 = document.getElementById('tab3');
    if (tab3) {
        const observer = new MutationObserver(() => {
            if (tab3.style.display !== 'none') {
                setupPilotSearch();
                observer.disconnect();
            }
        });
        observer.observe(tab3, { attributes: true });
    }
});
</script>


<script>
async function loadLeaderboardFromMissionstats() {
    const [statsRes, playersRes] = await Promise.all([
        fetch('data/missionstats.json?v=' + Date.now()),
        fetch('data/players.json?v=' + Date.now())
    ]);

    const eventLines = (await statsRes.text()).split(/\r?\n/).filter(Boolean);
    const playerLines = (await playersRes.text()).split(/\r?\n/).filter(Boolean);
    const events = eventLines.map(line => JSON.parse(line));
    const playerMap = {};
    for (const line of playerLines) {
        const player = JSON.parse(line);
        playerMap[player.ucid] = player.name;
    }

    const players = {};
    const toDate = s => new Date(s);

    for (const evt of events) {
        const id = evt.init_id;
        if (!id || id === "-1") continue;
        if (!players[id]) {
            players[id] = {
                ucid: id,
                name: playerMap[id] || "Unknown",
                kills: 0,
                sorties: 0,
                deaths: 0,
                takeoffs: 0,
                landings: 0,
                crashes: 0,
                ejections: 0,
                timestamps: []
            };
        }

        const p = players[id];
        const e = evt.event;
        if (evt.time) p.timestamps.push(toDate(evt.time));
        if (e === "S_EVENT_TAKEOFF") { p.sorties++; p.takeoffs++; }
        else if (e === "S_EVENT_KILL") p.kills++;
        else if (e === "S_EVENT_DEAD") p.deaths++;
        else if (e === "S_EVENT_LAND") p.landings++;
        else if (e === "S_EVENT_CRASH") p.crashes++;
        else if (e === "S_EVENT_EJECTION") p.ejections++;
    }

    for (const id in players) {
        const p = players[id];
        const times = p.timestamps;
        const duration = times.length ? (Math.max(...times.map(d => d.getTime())) - Math.min(...times.map(d => d.getTime()))) / 1000 : 0;
        p.duration = Math.round(duration);
    }

    const sorted = Object.values(players).sort((a, b) => b.kills - a.kills).slice(0, 20);
    sorted.forEach((p, i) => p.rank = (i + 1));

    const formatDuration = s => {
        const d = Math.floor(s / 86400), h = Math.floor((s % 86400) / 3600), m = Math.floor((s % 3600) / 60);
        return `${d ? d + 'd ' : ''}${h ? h + 'h ' : ''}${m}m`;
    };

    let html = '<table><thead><tr><th>Rank</th><th>Name</th><th>Kills</th><th>Sorties</th><th>Duration</th><th>Deaths</th><th>Takeoffs</th><th>Landings</th><th>Crashes</th><th>Ejections</th></tr></thead><tbody>';
    for (const p of sorted) {
        html += `<tr>
        <td>${p.rank}</td>
        <td>${p.name}</td>
        <td>${p.kills}</td>
        <td>${p.sorties}</td>
        <td>${formatDuration(p.duration)}</td>
        <td>${p.deaths}</td>
        <td>${p.takeoffs}</td>
        <td>${p.landings}</td>
        <td>${p.crashes}</td>
        <td>${p.ejections}</td>
        </tr>`;
    }
    html += '</tbody></table>';
    document.getElementById('leaderboardTable').innerHTML = html;
}

document.addEventListener('DOMContentLoaded', function() {
    const tab2 = document.getElementById('tab2');
    if (tab2) {
        const observer = new MutationObserver(() => {
            if (tab2.style.display !== 'none') {
                loadLeaderboardFromMissionstats();
                observer.disconnect();
            }
        });
        observer.observe(tab2, { attributes: true });
    }
});
</script>

</body>
</html>

</main>
<footer>
    &copy; 2025 All rights reserved.
</footer>
</body>
</html>
