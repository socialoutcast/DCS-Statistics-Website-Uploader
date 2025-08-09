/**
 * DCS Stats API Client
 * Handles all API calls from the browser, bypassing PHP restrictions
 */

class DCSStatsAPI {
    constructor() {
        this.config = null;
        this.configLoaded = false;
        this.basePath = window.DCS_CONFIG ? window.DCS_CONFIG.basePath : '';
    }

    // Helper to build proper URLs
    buildUrl(path) {
        if (!path) return this.basePath;
        const cleanPath = path.replace(/^\//, '');
        return this.basePath ? `${this.basePath}/${cleanPath}` : `/${cleanPath}`;
    }

    async loadConfig() {
        if (this.configLoaded) return this.config;
        
        try {
            const response = await fetch(this.buildUrl('get_api_config.php'));
            this.config = await response.json();
            this.configLoaded = true;
            return this.config;
        } catch (error) {
            // Failed to load API config
            this.config = { use_api: true };
            return this.config;
        }
    }

    async makeDirectAPICall(endpoint, options = {}) {
        const config = await this.loadConfig();
        
        // Determine API base URL
        let apiUrl = config.api_base_url;
        if (!apiUrl && config.api_host) {
            // Auto-detect protocol
            const protocols = ['https', 'http'];
            for (const protocol of protocols) {
                try {
                    const testUrl = `${protocol}://${config.api_host}/servers`;
                    const testResponse = await fetch(testUrl, { 
                        method: 'HEAD',
                        mode: 'cors',
                        timeout: 3000 
                    });
                    if (testResponse.ok || testResponse.status < 500) {
                        apiUrl = `${protocol}://${config.api_host}`;
                        // Detected working protocol
                        break;
                    }
                } catch (e) {
                    // Protocol failed
                }
            }
            if (!apiUrl) {
                apiUrl = `https://${config.api_host}`; // Default to HTTPS
            }
        }
        
        const url = apiUrl + endpoint;
        const method = options.method || 'GET';
        
        // Making direct API call
        
        try {
            const fetchOptions = {
                method: method,
                mode: 'cors',
                headers: {
                    'Accept': 'application/json'
                }
            };
            
            // Add data for POST requests
            if (method === 'POST' && options.data) {
                fetchOptions.headers['Content-Type'] = 'application/x-www-form-urlencoded';
                fetchOptions.body = new URLSearchParams(options.data).toString();
            }
            
            const response = await fetch(url, fetchOptions);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            // Direct API response received
            return data;
        } catch (error) {
            // Direct API call error
            throw error;
        }
    }
    
    async makeAPICall(endpoint, options = {}) {
        const config = await this.loadConfig();
        
        if (!config.use_api || (!config.api_base_url && !config.api_host)) {
            // API not enabled or no base URL configured
            throw new Error('API not enabled');
        }

        // First try proxy endpoint
        const method = options.method || 'GET';
        const proxyUrl = this.buildUrl(`api_proxy.php?endpoint=${encodeURIComponent(endpoint)}&method=${method}`);
        
        // Making API call via proxy

        const timeout = (config.timeout || 30) * 1000;
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), timeout);

        try {
            const fetchOptions = {
                signal: controller.signal,
                headers: {
                    'Accept': 'application/json'
                }
            };

            // For POST requests, send data as JSON in body
            if (method === 'POST' && (options.body || options.data)) {
                const data = options.body || options.data || {};
                
                // If body is FormData, convert to object
                if (data instanceof FormData) {
                    const formObject = {};
                    for (let [key, value] of data.entries()) {
                        formObject[key] = value;
                    }
                    fetchOptions.body = JSON.stringify(formObject);
                } else {
                    fetchOptions.body = JSON.stringify(data);
                }
                
                fetchOptions.method = 'POST';
                fetchOptions.headers['Content-Type'] = 'application/json';
            }

            const response = await fetch(proxyUrl, fetchOptions);
            clearTimeout(timeoutId);

            if (!response.ok) {
                // API call failed
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            // API response received
            return data;
        } catch (error) {
            clearTimeout(timeoutId);
            // API proxy call error
            
            // If proxy fails, try direct API call (for sites that block PHP calls)
            if (config.api_base_url || config.api_host) {
                // Proxy failed, attempting direct API call
                return this.makeDirectAPICall(endpoint, options);
            }
            
            throw error;
        }
    }

    async getLeaderboard() {
        const config = await this.loadConfig();
        
        if (!config.use_api) {
            throw new Error('API is not enabled');
        }

        // API only - no fallback
        const topKillsData = await this.makeAPICall('/topkills');
        
        // For each player in top 10, fetch their detailed stats
        const detailedPlayers = await Promise.all(
            topKillsData.slice(0, 10).map(async (player, index) => {
                try {

                    // Now get their detailed stats
                    const stats = await this.makeAPICall('/stats', {
                        method: 'POST',
                        data: {
                            nick: player.nick,
                            date: player.date
                        }
                    });


                    // Find most used aircraft from killsByModule
                    let mostUsedAircraft = 'N/A';
                    if (stats.killsByModule && stats.killsByModule.length > 0) {
                        // Sort by kills to find most used
                        const sorted = [...stats.killsByModule].sort((a, b) => b.kills - a.kills);
                        mostUsedAircraft = sorted[0].module || 'N/A';
                    }

                    return {
                        rank: index + 1,
                        nick: player.nick,
                        kills: player.AAkills || 0,
                        deaths: player.deaths || 0,
                        kd_ratio: player.AAKDR || 0,
                        sorties: stats.takeoffs || 0, // Use takeoffs as sorties
                        takeoffs: stats.takeoffs || 0,
                        landings: stats.landings || 0,
                        crashes: stats.crashes || 0,
                        ejections: stats.ejections || 0,
                        most_used_aircraft: mostUsedAircraft
                    };
                } catch (e) {
                    console.error(`Failed to get detailed stats for ${player.nick}:`, e);
                }
                
                // Fallback to basic data if detailed stats fail
                return {
                    rank: index + 1,
                    name: player.nick,
                    kills: player.AAkills || 0,
                    deaths: player.deaths || 0,
                    kd_ratio: player.AAKDR || 0,
                    sorties: 0,
                    takeoffs: 0,
                    landings: 0,
                    crashes: 0,
                    ejections: 0,
                    most_used_aircraft: 'N/A'
                };
            })
        );
        
        return {
            data: detailedPlayers,
            source: 'api-client',
            count: detailedPlayers.length,
            generated: new Date().toISOString()
        };
    }

    async getServerStats() {
        const config = await this.loadConfig();
        
        if (!config.use_api) {
            throw new Error('API is not enabled');
        }

        // get /serverstats data
        try {
            const stats = await this.makeAPICall('/serverstats', {
                data: {}
            });
            
            // If stats has overall server statistics, use them
            if (stats.totalPlayers !== undefined) {
                return {
                    totalPlayers: stats.totalPlayers || 0,
                    totalPlaytime: stats.totalPlaytime / 3600 || 0,
                    avgPlaytime: stats.avgPlaytime / 3600 || 0,
                    activePlayers: stats.activePlayers || 0,
                    totalSorties: stats.totalSorties || 0,
                    totalKills: stats.totalKills || 0,
                    totalDeaths: stats.totalDeaths || 0,
                    totalAAKills: stats.totalKills || 0,
                    totalAADeaths: stats.totalDeaths || 0
                };
            }
        } catch (error) {
            // Enhanced stats endpoint failed
        }
        
        // Use multiple API endpoints to build server stats
        const [topKills, topKDR] = await Promise.all([
            this.makeAPICall('/topkills').catch(() => []),
            this.makeAPICall('/topkdr').catch(() => [])
        ]);

        // Calculate stats from API data
        const allPlayers = [...topKills, ...topKDR];
        const uniquePlayers = new Map();

        allPlayers.forEach(player => {
            const name = player.nick;
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

    async searchPlayers(searchTerm) {
        const config = await this.loadConfig();
        
        if (!config.use_api) {
            throw new Error('API is not enabled');
        }

        const players = await this.makeAPICall('/getuser', {
            method: "POST",
            data: { nick: searchTerm }
        });

        if (players && players.length > 0) {
            // Found exact or partial matches via getuser
            return {
                count: players.length,
                results: players.map(p => ({
                    nick: p.nick,
                    date: p.date
                }))
            };
        }
    }
    
    // Simple fuzzy matching for common typos
    fuzzyMatch(str1, str2) {
        // Check if strings are very similar (1-2 character difference)
        if (Math.abs(str1.length - str2.length) > 2) return false;
        
        let differences = 0;
        const longer = str1.length > str2.length ? str1 : str2;
        const shorter = str1.length > str2.length ? str2 : str1;
        
        for (let i = 0; i < shorter.length; i++) {
            if (longer[i] !== shorter[i]) differences++;
            if (differences > 2) return false;
        }
        
        return differences <= 2;
    }

    async getPlayerStats(playerName) {
        const config = await this.loadConfig();
        
        if (!config.use_api) {
            throw new Error('API is not enabled');
        }

        
        // Get user data first using proxy
        const users = await this.makeAPICall('/getuser', {
            method: "POST",
            data: { nick: playerName }
        });
        
        
        if (users && users.length > 0) {
            const user = users[0];

            // Get stats using proxy
            const stats = await this.makeAPICall('/stats', {
                method: 'POST',
                data: {
                    nick: user.nick,
                    date: user.date
                }
            });
            
            
            // Check if stats is empty object
            if (!stats || Object.keys(stats).length === 0) {
                throw new Error(`No statistics found for player "${user.nick}". They may not have any recorded combat data.`);
            }
            
            // Find most used aircraft from kills_by_module
            let mostUsedAircraft = 'N/A';
            // API returns killsByModule as array format
            if (stats.killsByModule && Array.isArray(stats.killsByModule)) {
                if (stats.killsByModule.length > 0) {
                    // Sort by kills and get the module with most kills
                    const sorted = [...stats.killsByModule].sort((a, b) => b.kills - a.kills);
                    mostUsedAircraft = sorted[0].module;
                }
            } else if (stats.killsbymodule) {
                // Fallback to object format if available
                const modules = Object.entries(stats.killsbymodule);
                if (modules.length > 0) {
                    modules.sort((a, b) => b[1] - a[1]);
                    mostUsedAircraft = modules[0][0];
                }
            }
            
            return {
                source: 'api-client',
                data: {
                    nick: user.nick,
                    kills: stats.aakills || 0,
                    deaths: stats.deaths || 0,
                    kd_ratio: stats.aakdr || 0,
                    kills_by_module: stats.killsByModule ? 
                        stats.killsByModule.reduce((acc, item) => {
                            acc[item.module] = item.kills;
                            return acc;
                        }, {}) : 
                        (stats.killsbymodule || {}),
                    last_session_kills: stats.lastSessionKills || 0,
                    last_session_deaths: stats.lastSessionDeaths || 0,
                    takeoffs: stats.takeoffs || 0,
                    landings: stats.landings || 0,
                    crashes: stats.crashes || 0,
                    ejections: stats.ejections || 0,
                    sorties: stats.sorties || 0,
                    carrier_traps: stats.carrier_traps || stats.carrierTraps || 0,
                    avgTrapScore: stats.avgTrapScore || stats.avg_trap_score || 0,
                    trapScores: stats.trapScores || [],
                    most_used_aircraft: mostUsedAircraft,
                    aircraftUsage: stats.aircraftUsage || []
                }
            };
        }
        
        // If no users found, throw more informative error
        if (!users || users.length === 0) {
            throw new Error(`No player found with name "${playerName}"`);
        }
        
        throw new Error('Failed to get player stats from API');
    }

    async getCredits() {
        const config = await this.loadConfig();
        
        if (!config.use_api) {
            throw new Error('API is not enabled');
        }

        // Use new /credits endpoint with POST via proxy
        const credits = await this.makeAPICall('/credits', {
            method: 'POST',
            data: {}
        });
        
        // Transform to expected format
        return Object.entries(credits).map(([name, points]) => ({
            name: name,
            credits: points
        })).sort((a, b) => b.credits - a.credits);
    }

    async getServers() {
        const config = await this.loadConfig();
        
        if (!config.use_api) {
            throw new Error('API is not enabled');
        }

        // Use new /servers endpoint
        const data = await this.makeAPICall('/servers');
        return {
            data: data,
            source: 'api-client',
            generated: new Date().toISOString()
        };
    }

    async getSquadronData(type) {
        // Squadron data is now available via API
        const endpoints = {
            'squadrons': '/get_squadrons.php',
            'squadron_members': '/get_squadron_members.php',
            'squadron_credits': '/get_squadron_credits.php'
        };
        
        const endpoint = endpoints[type];
        if (!endpoint) {
            throw new Error(`Unknown squadron data type: ${type}`);
        }
        
        return this.request(endpoint);
    }
}

// Create global instance
window.dcsAPI = new DCSStatsAPI();