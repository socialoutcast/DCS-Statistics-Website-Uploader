/**
 * DCS Stats API Client
 * Handles all API calls from the browser, bypassing PHP restrictions
 */

class DCSStatsAPI {
    constructor() {
        this.config = null;
        this.configLoaded = false;
    }

    async loadConfig() {
        if (this.configLoaded) return this.config;
        
        try {
            const response = await fetch('get_api_config.php');
            this.config = await response.json();
            this.configLoaded = true;
            return this.config;
        } catch (error) {
            console.error('Failed to load API config:', error);
            this.config = { use_api: false, fallback_to_json: true };
            return this.config;
        }
    }

    async makeAPICall(endpoint, options = {}) {
        const config = await this.loadConfig();
        
        if (!config.use_api || !config.api_base_url) {
            throw new Error('API not enabled');
        }

        const url = `${config.api_base_url}${endpoint}`;
        const timeout = (config.timeout || 30) * 1000;

        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), timeout);

        try {
            const response = await fetch(url, {
                ...options,
                signal: controller.signal,
                headers: {
                    'Accept': 'application/json',
                    ...options.headers
                }
            });

            clearTimeout(timeoutId);

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            return await response.json();
        } catch (error) {
            clearTimeout(timeoutId);
            throw error;
        }
    }

    async getLeaderboard() {
        const config = await this.loadConfig();
        
        try {
            if (config.use_api) {
                // Try API first
                const data = await this.makeAPICall('/topkills');
                
                // Transform API data to match expected format
                return {
                    data: data.map((player, index) => ({
                        rank: index + 1,
                        name: player.fullNickname || player.name,
                        kills: player.AAkills || 0,
                        deaths: player.deaths || 0,
                        kd_ratio: player.AAKDR || 0,
                        sorties: 0,
                        takeoffs: 0,
                        landings: 0,
                        crashes: 0,
                        ejections: 0,
                        most_used_aircraft: 'N/A'
                    })),
                    source: 'api-client',
                    count: data.length,
                    generated: new Date().toISOString()
                };
            }
        } catch (error) {
            console.warn('Client-side API call failed:', error);
            
            if (config.fallback_to_json) {
                // Fall back to PHP endpoint
                const response = await fetch('get_leaderboard.php');
                return await response.json();
            }
            throw error;
        }

        // Default to PHP endpoint
        const response = await fetch('get_leaderboard.php');
        return await response.json();
    }

    async getServerStats() {
        const config = await this.loadConfig();
        
        try {
            if (config.use_api) {
                // Try multiple API endpoints to build server stats
                const [topKills, topKDR] = await Promise.all([
                    this.makeAPICall('/topkills').catch(() => []),
                    this.makeAPICall('/topkdr').catch(() => [])
                ]);

                // Calculate stats from API data
                const allPlayers = [...topKills, ...topKDR];
                const uniquePlayers = new Map();
                
                allPlayers.forEach(player => {
                    const name = player.fullNickname || player.name;
                    if (!uniquePlayers.has(name)) {
                        uniquePlayers.set(name, {
                            name: name,
                            kills: player.AAkills || 0,
                            deaths: player.deaths || 0,
                            kdr: player.AAKDR || 0,
                            visits: player.AAkills || 0
                        });
                    }
                });

                const top5Pilots = Array.from(uniquePlayers.values())
                    .sort((a, b) => b.kills - a.kills)
                    .slice(0, 5);

                const totalKills = Array.from(uniquePlayers.values())
                    .reduce((sum, p) => sum + p.kills, 0);
                const totalDeaths = Array.from(uniquePlayers.values())
                    .reduce((sum, p) => sum + p.deaths, 0);

                return {
                    totalPlayers: uniquePlayers.size,
                    totalKills: totalKills,
                    totalDeaths: totalDeaths,
                    top5Pilots: top5Pilots,
                    top3Squadrons: [],
                    source: 'api-client',
                    generated: new Date().toISOString()
                };
            }
        } catch (error) {
            console.warn('Client-side API call failed:', error);
            
            if (config.fallback_to_json) {
                const response = await fetch('get_server_stats.php');
                return await response.json();
            }
            throw error;
        }

        // Default to PHP endpoint
        const response = await fetch('get_server_stats.php');
        return await response.json();
    }

    async searchPlayers(searchTerm) {
        const config = await this.loadConfig();
        
        if (config.use_api) {
            // API doesn't have search, so get all and filter client-side
            try {
                const data = await this.makeAPICall('/topkills');
                const filtered = data.filter(player => {
                    const name = (player.fullNickname || player.name || '').toLowerCase();
                    return name.includes(searchTerm.toLowerCase());
                });

                return {
                    count: filtered.length,
                    results: filtered.map(p => ({
                        name: p.fullNickname || p.name,
                        ucid: null
                    }))
                };
            } catch (error) {
                console.warn('Client-side search failed:', error);
            }
        }

        // Fall back to PHP endpoint
        const response = await fetch(`search_players.php?search=${encodeURIComponent(searchTerm)}`);
        return await response.json();
    }

    async getPlayerStats(playerName) {
        const config = await this.loadConfig();
        
        if (config.use_api) {
            try {
                // Get user data first
                const formData = new FormData();
                formData.append('nick', playerName);
                
                const userResponse = await fetch(`${config.api_base_url}/getuser`, {
                    method: 'POST',
                    body: formData
                });
                
                if (userResponse.ok) {
                    const users = await userResponse.json();
                    if (users && users.length > 0) {
                        const user = users[0];
                        
                        // Get stats
                        const statsFormData = new FormData();
                        statsFormData.append('nick', playerName);
                        statsFormData.append('date', user.date || new Date().toISOString());
                        
                        const statsResponse = await fetch(`${config.api_base_url}/stats`, {
                            method: 'POST',
                            body: statsFormData
                        });
                        
                        if (statsResponse.ok) {
                            const stats = await statsResponse.json();
                            
                            return {
                                source: 'api-client',
                                data: {
                                    name: playerName,
                                    kills: stats.aakills || 0,
                                    deaths: stats.deaths || 0,
                                    kd_ratio: stats.aakdr || 0,
                                    kills_by_module: stats.killsbymodule || {},
                                    last_session_kills: stats.lastSessionKills || 0,
                                    last_session_deaths: stats.lastSessionDeaths || 0,
                                    takeoffs: 0,
                                    landings: 0,
                                    crashes: 0,
                                    ejections: 0,
                                    sorties: 0,
                                    most_used_aircraft: 'N/A'
                                }
                            };
                        }
                    }
                }
            } catch (error) {
                console.warn('Client-side player stats failed:', error);
            }
        }

        // Fall back to PHP endpoint
        const response = await fetch(`get_player_stats.php?name=${encodeURIComponent(playerName)}`);
        return await response.json();
    }

    async getCredits() {
        // Credits not available via API, use PHP endpoint
        const response = await fetch('get_credits.php');
        return await response.json();
    }

    async getSquadronData(file) {
        // Squadron data not available via API, use PHP endpoint
        const response = await fetch(`get_squadron.php?file=${file}`);
        return await response.text();
    }
}

// Create global instance
window.dcsAPI = new DCSStatsAPI();