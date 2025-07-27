<?php
include 'header.php';
include 'nav.php';
?>

<main>
    <h1>Squadrons</h1>
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

    <div id="error-message" style="color: red; text-align: center;"></div>
</main>

<script>
document.addEventListener("DOMContentLoaded", () => {
    Promise.all([
        fetch('data/squadrons.json').then(res => res.text()),
        fetch('data/squadron_members.json').then(res => res.text()),
        fetch('data/players.json').then(res => res.text()),
        fetch('data/squadron_credits.json').then(res => res.text())
    ])
    .then(([squadronText, memberText, playerText, creditText]) => {
        const squadrons = squadronText.trim().split('\n').map(line => JSON.parse(line));
        const members = memberText.trim().split('\n').map(line => JSON.parse(line));
        const players = playerText.trim().split('\n').map(line => JSON.parse(line));
        const credits = creditText.trim().split('\n').map(line => JSON.parse(line));

        const squadronsById = {};
        squadrons.forEach(sq => squadronsById[sq.id] = sq);

        const playersByUcid = {};
        players.forEach(p => playersByUcid[p.ucid] = p);

        // === Populate Squadron Table ===
        const squadronBody = document.querySelector('#squadronsTable tbody');
        squadrons.forEach(sq => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td><img src="${sq.image_url}" alt="${sq.name}" style="width: 80px;"></td>
                <td>${sq.name}</td>
                <td>${sq.description}</td>
            `;
            squadronBody.appendChild(row);
        });

        // === Populate Squadron Members Table (grouped with expand/collapse) ===
        const membersBody = document.querySelector('#membersTable tbody');
        squadrons.forEach((sq, index) => {
            const relevantMembers = members.filter(m => m.squadron_id === sq.id);
            const groupId = "group-" + index;

            const headerRow = document.createElement('tr');
            headerRow.classList.add('toggle-header');
            headerRow.style.cursor = "pointer";
            headerRow.innerHTML = `
                <td><img src="${sq.image_url}" alt="${sq.name}" style="width: 60px;"></td>
                <td>${sq.name}</td>
                <td><em>Click to show/hide members</em></td>
            `;
            membersBody.appendChild(headerRow);

            relevantMembers.forEach(member => {
                const player = playersByUcid[member.player_ucid];
                if (!player) return;

                const row = document.createElement('tr');
                row.classList.add(groupId);
                row.style.display = "none";
                row.innerHTML = `
                    <td></td>
                    <td></td>
                    <td>${player.name}</td>
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

        // === Populate Squadron Leaderboard Table with medals ===
        const leaderboardBody = document.querySelector('#leaderboardTable tbody');
        const medals = ['🥇', '🥈', '🥉'];

        credits
            .sort((a, b) => b.points - a.points)
            .forEach((entry, index) => {
                const squadron = squadronsById[entry.squadron_id];
                if (!squadron) return;

                const medal = medals[index] || '';
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td><img src="${squadron.image_url}" alt="${squadron.name}" style="width: 60px;"></td>
                    <td>${medal} ${squadron.name}</td>
                    <td>${entry.points}</td>
                `;
                leaderboardBody.appendChild(row);
            });
    })
    .catch(err => {
        document.getElementById('error-message').textContent = "Error loading data: " + err.message;
        console.error(err);
    });
});
</script>

<?php
include 'footer.php';
?>
