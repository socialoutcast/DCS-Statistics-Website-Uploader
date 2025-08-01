# DCS Statistics Website

A modern web-based statistics dashboard for DCS (Digital Combat Simulator) servers using DCSServerBot data, featuring a comprehensive Navy-themed admin panel.

## Features

### Public Website
- **Player Statistics**: Search and view detailed pilot statistics with combat performance metrics
- **Leaderboard**: Dynamic rankings by kills, K/D ratio, flight hours, and more
- **Pilot Credits**: Track pilot points and achievements
- **Squadron Management**: View squadron information, rosters, and statistics
- **Server Status**: Real-time server status and player counts
- **Clean URLs**: Professional URLs without `.php` extensions
- **Dark Theme**: Modern dark interface optimized for readability

### Admin Panel - "CAG Bridge"
A fully-featured Navy carrier-themed administration interface with role-based access control:

- **Dashboard**: Overview of site statistics and recent activity
- **Player Management**: Search, view, and manage player records
- **Server Monitoring**: Track server status and player activity
- **Settings Menu**:
  - **Site Features**: Enable/disable website sections and features
  - **API Settings**: Configure DCSServerBot REST API connection
  - **Theme Management**: Customize site colors and upload custom CSS
  - **Discord Link**: Set custom Discord invite URL
  - **Squadron Homepage**: Add custom squadron link to navigation
  - **Admin Management**: Add/remove administrators
- **Activity Logs**: Complete audit trail of all admin actions
- **Data Export**: Export player and statistics data

## Installation

1. **Clone or download** this repository to your web server
   ```bash
   git clone https://github.com/your-repo/dcs-stats.git
   cd dcs-stats
   ```

2. **Configure your web server** to serve the `dcs-stats` directory
   - Ensure Apache `mod_rewrite` is enabled
   - For nginx, configure rewrite rules for clean URLs

3. **Access the admin panel** at `/site-config`
   - The system will auto-configure itself on first visit
   - Configure your DCSServerBot REST API connection in the admin panel

4. **Access the admin panel** at `/site-config`
   - Default credentials: `admin` / `changeme123`
   - **⚠️ CRITICAL**: Change these immediately after first login!

## Admin Panel Guide

### First Login
1. Navigate to `/admin`
2. Login with default credentials
3. Immediately go to Settings → Admins
4. Update the admin password
5. Add additional administrators if needed

### Admin Roles (Navy Carrier Theme)
- **Air Boss** (Level 2): Full administrative control
  - Manages all settings and features
  - Can upload custom CSS themes
  - Controls API configuration
  - Manages other administrators
- **LSO - Landing Signal Officer** (Level 1): Limited administrative access
  - Can view statistics and logs
  - Can manage players
  - Can change theme colors (but not upload CSS)

### Configuring the Site

#### API Configuration
1. Go to Settings → API Settings (Air Boss only)
2. Enter your DCSServerBot REST API URL
3. Test the connection
4. Save changes

#### Site Features
1. Go to Settings → Site Features
2. Enable/disable navigation items
3. Control homepage sections
4. Configure leaderboard columns
5. Manage feature dependencies

#### Customization
1. **Themes**: Settings → Themes
   - Change site colors instantly
   - Upload custom CSS (Air Boss only)
   - Backup and restore themes
2. **Discord Link**: Settings → Discord Link
   - Set your community's Discord invite URL
3. **Squadron Homepage**: Settings → Squadron Homepage
   - Add a custom squadron website link

## Configuration

### API Integration

The site uses DCSServerBot REST API exclusively:
- Configure in admin panel: Settings → API Settings
- Auto-detects HTTP/HTTPS protocols
- Real-time data updates
- No file uploads required

### Clean URLs

The site implements clean URLs automatically:
- `/leaderboard` instead of `/leaderboard.php`
- `/admin/settings` instead of `/admin/settings.php`

**Apache**: Works out of the box with included `.htaccess`
**Nginx**: Add these rewrite rules:
```nginx
location / {
    try_files $uri $uri/ @rewrite;
}
location @rewrite {
    rewrite ^/(.*)$ /$1.php last;
}
```

### Security Features

- **Session Security**: HTTP-only cookies, strict session management
- **CSRF Protection**: All forms include CSRF tokens
- **Rate Limiting**: API endpoints are rate-limited
- **Input Validation**: All user inputs are sanitized
- **Access Control**: Role-based permissions system
- **Activity Logging**: Complete audit trail

## Requirements

- **PHP**: 7.4 or higher with the following extensions:
  - `curl` (for API requests)
  - `json`
  - `session`
  - `mbstring`
- **Web Server**: 
  - Apache 2.4+ with `mod_rewrite`
  - Or nginx with proper rewrite rules
- **DCSServerBot**: With REST API enabled (port 8080 by default)

## Troubleshooting

### Common Issues

**"Service temporarily unavailable"**
- Check if DCSServerBot REST API is running
- Verify API URL in admin settings
- Ensure firewall allows connection to API port

**Admin panel shows "Access Denied"**
- Clear browser cookies
- Check admin user status in database
- Verify session settings in PHP

**Search returns no results**
- Ensure player exists in DCSServerBot database
- Check API connection status
- Verify search endpoint is enabled

### Debug Mode

To enable debug logging:
1. Edit `admin/config.php`
2. Set `define('DEBUG_MODE', true);`
3. Check `admin/data/debug.log` for details

## File Structure

```
dcs-stats/
├── index.php                 # Homepage
├── leaderboard.php          # Kill rankings
├── pilot_statistics.php     # Player search & stats
├── pilot_credits.php        # Points leaderboard
├── squadrons.php            # Squadron listings
├── admin/                   # Admin panel
│   ├── index.php           # Dashboard
│   ├── settings.php        # Site features
│   ├── api_settings.php    # API configuration
│   ├── themes.php          # Theme management
│   └── data/               # Admin data storage
├── api_client.php          # DCSServerBot API client
├── api_client_enhanced.php # Enhanced API client with auto-detection
└── .htaccess               # URL rewriting rules
```

## Contributing

1. Fork the repository
2. Create a feature branch
3. Commit your changes
4. Push to the branch
5. Create a Pull Request

## Security Disclosure

Found a security issue? Please email security@your-domain.com instead of using the issue tracker.

## License

This project is open source and free to use for the DCS World community.

## Credits

- Built for the DCS World community
- Integrates with [DCSServerBot](https://github.com/Special-K-s-Flightsim-Bots/DCSServerBot)
- Navy carrier theme inspired by real carrier operations