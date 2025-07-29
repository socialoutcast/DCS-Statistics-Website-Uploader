# API Requirements to Eliminate JSON Files Completely

## Required API Additions for 100% API-Only Operation

### 1. ✅ **Enhanced `/stats` Endpoint** (REQUIRED)
Add these fields to make pilot pages fully functional:

```json
{
    // Existing fields preserved...
    
    // Flight Activity (REQUIRED)
    "takeoffs": 234,
    "landings": 201,
    "crashes": 23,
    "ejections": 10,
    "flight_hours": 156.5,
    "sorties": 234,
    
    // Additional Stats (REQUIRED)
    "teamkills": 2,
    "most_used_aircraft": "F/A-18C",
    
    // Credits (REQUIRED)
    "credits": 15420
}
```

### 2. ✅ **Credits Leaderboard** (REQUIRED)
`GET /credits/top` or `GET /credits?limit=50`

```json
[
    {
        "player_name": "Maverick",
        "credits": 15420,
        "rank": 1
    },
    // ... more players
]
```

### 3. ✅ **Squadron Endpoints** (REQUIRED)

#### List all squadrons
`GET /squadrons`
```json
[
    {
        "id": 1,
        "name": "503rd Joint Task Wing",
        "tag": "503rd",
        "member_count": 45,
        "total_kills": 5234,
        "total_credits": 234000
    }
]
```

#### Get squadron details with members
`GET /squadrons/{id}`
```json
{
    "id": 1,
    "name": "503rd Joint Task Wing",
    "tag": "503rd",
    "members": [
        {
            "player_name": "Maverick",
            "role": "member",
            "joined": "2024-01-15",
            "kills": 123,
            "credits": 15420
        }
    ]
}
```

#### Get player's squadron
`GET /players/{name}/squadron`
```json
{
    "squadron_id": 1,
    "squadron_name": "503rd Joint Task Wing",
    "squadron_tag": "503rd"
}
```

### 4. ✅ **Server Status** (REQUIRED)
`GET /servers`

```json
[
    {
        "instance_id": "main",
        "server_name": "503rd Training Server",
        "status": "online",
        "current_mission": "Operation Desert Storm",
        "current_map": "Persian Gulf",
        "players_online": 12,
        "max_players": 50,
        "modules": ["F/A-18C", "F-16C", "A-10C II"]
    }
]
```

### 5. ✅ **Mission Statistics** (REQUIRED for homepage)
`GET /stats/summary` or enhance `/servers` with totals

```json
{
    "total_players": 1523,
    "total_kills": 45234,
    "total_deaths": 23421,
    "total_flight_hours": 15234.5,
    "total_sorties": 98234,
    "active_players_24h": 45,
    "active_players_7d": 234
}
```

### 6. ✅ **Full Player List** (REQUIRED for complete search)
`GET /players?page=1&limit=100`

```json
{
    "total": 1523,
    "page": 1,
    "data": [
        {
            "name": "Maverick",
            "last_seen": "2025-07-29T10:30:00Z"
        }
    ]
}
```

## Implementation Priority

### Phase 1: Core Functionality (Minimal for Pilot Pages)
1. Enhance `/stats` with flight data and credits
2. Add `/credits/top` for credits leaderboard

### Phase 2: Complete Feature Parity
3. Add squadron endpoints
4. Add server status endpoint
5. Add summary statistics

### Phase 3: Enhanced Features
6. Full player list with pagination
7. Historical data queries
8. Real-time WebSocket updates

## What This Enables

With these additions, you can:
- ❌ **Delete all JSON file handling code**
- ❌ **Remove the uploader script completely**
- ❌ **Eliminate FTP configuration**
- ✅ **Get real-time data updates**
- ✅ **Reduce server load**
- ✅ **Simplify deployment**

## Minimal Viable API

If you need to prioritize, the absolute minimum for a functional site without JSON:

1. Enhanced `/stats` (with flight data + credits)
2. `/credits/top` endpoint
3. `/squadrons` endpoints
4. `/servers` endpoint

Everything else can be added incrementally.