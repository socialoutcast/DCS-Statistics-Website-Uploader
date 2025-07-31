# DCS Statistics Admin Panel

A secure administrative interface for managing the DCS Statistics website.

## Features

### 🔐 Security
- Secure session-based authentication
- Role-based access control (Super Admin, Admin, Moderator)
- CSRF protection on all forms
- Login throttling to prevent brute force
- Activity logging for audit trails

### 🔧 Administration
- Add and remove admin users
- Reset admin passwords
- Activate/deactivate admin accounts
- Role-based permissions

### 📊 Dashboard
- Admin and player counts
- Recent admin activities
- Quick action shortcuts

### 📁 Data Export
- Export player data to CSV/JSON
- Export mission statistics
- Export admin activity logs
- Custom date ranges

### 📋 Activity Logs
- Complete audit trail of admin actions
- Filter by admin, action, or date
- Monitor system access

## Installation

### 1. Basic Setup (JSON-based)

```bash
# 1. Ensure admin directories exist and are writable
chmod 755 admin/
chmod 777 admin/data/

# 2. Access admin panel
https://yoursite.com/dcs-stats/site-config/

# 3. Login with default credentials
Username: admin
Password: changeme123

# 4. IMMEDIATELY change the default password!
```

### 2. Database Setup (Optional)

```bash
# 1. Create database
mysql -u root -p < admin/schema.sql

# 2. Update admin/config.php
define('USE_DATABASE', true);
define('DB_HOST', 'localhost');
define('DB_NAME', 'dcs_stats_admin');
define('DB_USER', 'your_user');
define('DB_PASS', 'your_password');

# 3. Run database migrations
php admin/install.php
```

## Configuration

Edit `admin/config.php` to customize:

```php
// Security settings
define('ENFORCE_HTTPS', true);  // Force HTTPS
define('LOGIN_THROTTLE_ATTEMPTS', 5);  // Max login attempts

// Features
define('LOG_ADMIN_ACTIONS', true);  // Enable activity logging
define('EXPORT_MAX_RECORDS', 10000);  // Export limit
```

## Usage

### First Time Setup

1. Navigate to `/dcs-stats/site-config/`
2. Login with default credentials
3. Go to Settings → Change Password
4. Create additional admin users as needed
5. Configure system settings

### Managing Players

1. Go to Players section
2. Search by name or UCID
3. Click on player to view details
4. Use action buttons to:
   - Ban/unban player
   - Add notes
   - View statistics

### Viewing Logs

1. Go to Activity Logs
2. Filter by:
   - Date range
   - Admin user
   - Action type
3. Export logs for audit purposes

## Security Best Practices

1. **Change default password immediately**
2. **Use strong passwords** (min 12 characters)
3. **Enable HTTPS** in production
4. **Regularly review activity logs**
5. **Limit admin access** to trusted users
6. **Keep PHP and dependencies updated**
7. **Backup admin data regularly**

## File Structure

```
admin/
├── index.php          # Dashboard
├── login.php          # Login page
├── auth.php           # Authentication core
├── config.php         # Configuration
├── api/              # API endpoints
├── css/              # Styles
├── data/             # Data storage (protected)
└── logs/             # Log files (protected)
```

## Troubleshooting

### Cannot Login
- Check if `admin/data/` is writable
- Verify PHP session support
- Check for login throttling

### 403 Forbidden Errors
- Ensure .htaccess is enabled
- Check directory permissions
- Verify Apache AllowOverride

### Session Expires Too Quickly
- Adjust `SESSION_LIFETIME` in config.php
- Check PHP session settings

## API Integration

The admin panel integrates with:
- DCSServerBot REST API (when available)
- Local JSON files (fallback)

## Support

For issues or questions:
1. Check the logs in `admin/data/logs/`
2. Enable debug mode in config.php
3. Submit issues to the repository

## License

Same as DCS Statistics Website Uploader project.