# DCSServerBot REST API Integration

This document provides comprehensive information about the REST API integration for the DCS Statistics Website.

## 🚀 Quick Start

1. **Configure API Connection**
   ```bash
   cp dcs-stats/api_config.json.example dcs-stats/api_config.json
   ```
   
   Edit `api_config.json`:
   ```json
   {
       "api_base_url": "http://dcs1.skypirates.uk:9876/api",
       "api_key": null,
       "timeout": 30,
       "cache_ttl": 300,
       "fallback_to_json": true,
       "use_api": true,
       "enabled_endpoints": [
           "get_leaderboard.php",
           "get_player_stats.php",
           "search_players.php"
       ]
   }
   ```

2. **Test Connection**
   The system will automatically use the API when available and fall back to JSON files if the API is unreachable.

## 📊 Available API Endpoints

### Currently Integrated

| Endpoint | Method | Purpose | Frontend Usage |
|----------|--------|---------|----------------|
| `/topkills` | GET | Top 10 players by kills | Leaderboard page |
| `/topkdr` | GET | Top 10 players by K/D ratio | Leaderboard page (?sort=kdr) |
| `/getuser` | POST | Search players by name | Player search |
| `/stats` | POST | Get player statistics | Player profile pages |
| `/missilepk` | POST | Weapon effectiveness | Player profile pages |

### API Response Examples

#### `/topkills` and `/topkdr`
```json
[
    {
        "fullNickname": "PlayerName",
        "AAkills": 123,
        "deaths": 45,
        "AAKDR": 2.73
    }
]
```

#### `/stats`
```json
{
    "deaths": 45,
    "aakills": 123,
    "aakdr": 2.73,
    "lastSessionKills": 5,
    "lastSessionDeaths": 2,
    "killsbymodule": [
        {"module": "F/A-18C", "kills": 50}
    ],
    "kdrByModule": [
        {"module": "F/A-18C", "kdr": 2.5}
    ]
}
```

## 🔄 Features Working with API

### ✅ Fully Functional
- **Player Search** - Search for any player by name
- **Leaderboards** - Top 10 by kills or K/D ratio
- **Combat Statistics** - Kills, deaths, K/D ratios
- **Module Statistics** - Kills broken down by aircraft
- **Weapon Statistics** - Missile effectiveness data

### ❌ Still Requires JSON Files
- **Flight Activity** - Takeoffs, landings, flight hours, crashes
- **Credits System** - Player points and trophies
- **Squadron Features** - Squadron management and statistics
- **Server Status** - Active servers and missions
- **Carrier Operations** - Trap statistics
- **Player UCIDs** - Unique identifiers not provided by API

## 📁 Implementation Details

### File Structure
```
dcs-stats/
├── api_client.php               # API client class
├── api_config.json             # Configuration (create from .example)
├── get_leaderboard.php         # Router file
├── get_leaderboard_api.php     # API implementation
├── get_leaderboard_json.php    # JSON fallback
├── get_player_stats.php        # Router file
├── get_player_stats_api.php    # API implementation
├── get_player_stats_json.php   # JSON fallback
├── search_players.php          # Router file
├── search_players_api.php      # API implementation
└── search_players_json.php     # JSON fallback
```

### How Routing Works
1. Frontend calls standard endpoint (e.g., `get_leaderboard.php`)
2. Router checks `api_config.json` settings
3. If API is enabled for that endpoint, includes API version
4. If API fails or is disabled, includes JSON version
5. Response format is consistent regardless of source

### Response Format
All endpoints return data with metadata:
```json
{
    "source": "api",  // or "json"
    "timestamp": "2025-07-29T10:30:00Z",
    "data": { /* actual response data */ }
}
```

## 🔧 Configuration Options

### Enable/Disable API
- Set `"use_api": false` to disable API globally
- Remove endpoints from `"enabled_endpoints"` array to disable specific features

### Environment Variables
```bash
export DCSBOT_API_URL=http://dcs1.skypirates.uk:9876/api
export DCSBOT_API_KEY=your_api_key_here
```

## 🚨 Troubleshooting

### API Connection Issues
1. Check `api_config.json` settings
2. Verify DCSServerBot REST API is running
3. Check firewall/network settings
4. Review PHP error logs

### Missing Data
Some data is not available via API:
- Flight hours show as 0
- Most used aircraft shows as "N/A"
- Squadron features won't work

## 🔮 Future Enhancements Needed

For full feature parity, the DCSServerBot API needs:

1. **Enhanced `/stats` endpoint** with:
   - UCIDs for unique player identification
   - Flight activity (takeoffs, landings, crashes, flight hours)
   - Most used aircraft
   - Teamkills count

2. **New endpoints for**:
   - Credits/points system
   - Squadron management
   - Server status
   - Full player list/search

3. **Nice to have**:
   - WebSocket support for real-time updates
   - Bulk data export
   - Historical data queries