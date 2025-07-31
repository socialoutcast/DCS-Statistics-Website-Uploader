<?php
/**
 * Site Features Configuration
 * Controls which features/sections are enabled on the public site
 */

// Load settings from JSON file
function loadSiteFeatures() {
    $settingsFile = __DIR__ . '/site-config/data/site_settings.json';
    
    // Default features (all enabled)
    $defaults = [
        // Navigation Items
        'nav_home' => true,
        'nav_leaderboard' => true,
        'nav_pilot_credits' => true,
        'nav_pilot_statistics' => true,
        'nav_squadrons' => false,
        'nav_servers' => true,
        
        // Homepage Sections
        'home_server_stats' => true,
        'home_player_activity' => true,
        'home_mission_stats' => true,
        'home_top_pilots' => true,
        'home_recent_activity' => true,
        
        // Leaderboard Features
        'leaderboard_kills' => true,
        'leaderboard_deaths' => true,
        'leaderboard_kd_ratio' => true,
        'leaderboard_flight_hours' => true,
        'leaderboard_sorties' => true,
        'leaderboard_aircraft' => true,
        
        // Pilot Statistics Features
        'pilot_search' => true,
        'pilot_detailed_stats' => true,
        'pilot_mission_history' => true,
        'pilot_combat_stats' => true,
        'pilot_flight_stats' => true,
        'pilot_session_stats' => true,
        'pilot_aircraft_chart' => true,
        
        // Credits System
        'credits_enabled' => true,
        'credits_leaderboard' => true,
        
        // Squadron Features
        'squadrons_enabled' => false,
        'squadron_management' => false,
        'squadron_statistics' => false,
        
        // Server Features
        'servers_list' => true,
        'server_status' => true,
        'server_players' => true,
        
        // Global Features
        'show_discord_link' => true,
        'show_last_update' => true,
        
        // Custom Links
        'show_squadron_homepage' => false,
        'discord_link_url' => 'https://discord.gg/DNENf6pUNX',
        'squadron_homepage_url' => '',
        'squadron_homepage_text' => 'Squadron'
    ];
    
    // Load saved settings
    if (file_exists($settingsFile)) {
        $saved = json_decode(file_get_contents($settingsFile), true);
        if ($saved) {
            return array_merge($defaults, $saved);
        }
    }
    
    return $defaults;
}

// Save settings
function saveSiteFeatures($features) {
    $settingsFile = __DIR__ . '/site-config/data/site_settings.json';
    return file_put_contents($settingsFile, json_encode($features, JSON_PRETTY_PRINT)) !== false;
}

// Check if a feature is enabled
function isFeatureEnabled($feature) {
    static $features = null;
    if ($features === null) {
        $features = loadSiteFeatures();
    }
    return isset($features[$feature]) ? $features[$feature] : true;
}

// Get feature value (for URLs and text)
function getFeatureValue($feature, $default = '') {
    static $features = null;
    if ($features === null) {
        $features = loadSiteFeatures();
    }
    return isset($features[$feature]) ? $features[$feature] : $default;
}

// Feature groups for admin interface
function getFeatureGroups() {
    return [
        'Navigation' => [
            'nav_home' => 'Homepage',
            'nav_leaderboard' => 'Leaderboard',
            'nav_pilot_credits' => 'Pilot Credits',
            'nav_pilot_statistics' => 'Pilot Statistics',
            'nav_squadrons' => 'Squadrons',
            'nav_servers' => 'Servers'
        ],
        'Homepage Sections' => [
            'home_server_stats' => 'Server Statistics Box',
            'home_player_activity' => 'Player Activity Graph',
            'home_mission_stats' => 'Mission Statistics Graph',
            'home_top_pilots' => 'Top Pilots Table',
            'home_recent_activity' => 'Recent Activity Feed'
        ],
        'Leaderboard Columns' => [
            'leaderboard_kills' => 'Kills Column',
            'leaderboard_deaths' => 'Deaths Column',
            'leaderboard_kd_ratio' => 'K/D Ratio Column',
            'leaderboard_flight_hours' => 'Flight Hours Column',
            'leaderboard_sorties' => 'Sorties Column',
            'leaderboard_aircraft' => 'Most Used Aircraft Column'
        ],
        'Pilot Features' => [
            'pilot_search' => 'Pilot Search',
            'pilot_detailed_stats' => 'Detailed Statistics',
            'pilot_mission_history' => 'Mission History',
            'pilot_combat_stats' => 'Combat Statistics (Kills/Deaths)',
            'pilot_flight_stats' => 'Flight Statistics (Takeoffs/Landings)',
            'pilot_session_stats' => 'Last Session Statistics',
            'pilot_aircraft_chart' => 'Aircraft Usage Chart'
        ],
        'Credits System' => [
            'credits_enabled' => 'Enable Credits System',
            'credits_leaderboard' => 'Credits Leaderboard'
        ],
        'Squadron System' => [
            'squadrons_enabled' => 'Enable Squadrons',
            'squadron_management' => 'Squadron Management',
            'squadron_statistics' => 'Squadron Statistics'
        ],
        'Server Features' => [
            'servers_list' => 'Server List',
            'server_status' => 'Server Status Display',
            'server_players' => 'Online Players Display'
        ],
        'Global Settings' => [
            'show_discord_link' => 'Show Discord Link',
            'show_last_update' => 'Show Last Update Time'
        ]
    ];
}

// Get feature dependencies (features that should be disabled when parent is disabled)
function getFeatureDependencies() {
    return [
        'credits_enabled' => ['credits_leaderboard', 'nav_pilot_credits'],
        'squadrons_enabled' => ['squadron_management', 'squadron_statistics', 'nav_squadrons'],
        'pilot_search' => ['pilot_detailed_stats', 'pilot_mission_history'],
        'servers_list' => ['server_status', 'server_players', 'nav_servers']
    ];
}