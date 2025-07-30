# Admin Panel Requirements

## Server Requirements

### Minimum PHP Version
- PHP 7.4 or higher
- PHP 8.0+ recommended

### Required PHP Extensions
- **session** - For session management
- **json** - For JSON data handling
- **openssl** - For secure token generation
- **mbstring** - For proper string handling
- **hash** - For password hashing

### Optional PHP Extensions
- **mysqli/PDO** - If using MySQL database
- **curl** - For API integration
- **zip** - For export functionality

## Web Server Configuration

### Apache
- **mod_rewrite** enabled (for .htaccess rules)
- **mod_headers** enabled (for security headers)
- **AllowOverride All** for admin directory

### Nginx
- Requires manual configuration for security rules
- See nginx.conf.example for configuration

## Directory Permissions

```bash
# Writable directories (755 or 775)
admin/data/
admin/logs/
data/  # Main data directory

# Read-only directories (755)
admin/
admin/api/
admin/css/
```

## Security Requirements

### SSL/TLS Certificate
- **Strongly recommended** for production
- Required if ENFORCE_HTTPS is enabled

### PHP Configuration
```ini
; Recommended php.ini settings
session.cookie_httponly = 1
session.cookie_secure = 1  ; If using HTTPS
session.use_strict_mode = 1
session.cookie_samesite = "Strict"
```

## Database Requirements (Optional)

### MySQL/MariaDB
- Version 5.7+ / MariaDB 10.2+
- InnoDB storage engine
- utf8mb4 character set support

### Required Privileges
```sql
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, INDEX, ALTER 
ON dcs_stats_admin.* TO 'dcs_admin'@'localhost';
```

## Browser Requirements

### Minimum Supported Browsers
- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+

### JavaScript
- ES6 support required
- No framework dependencies

## Installation Checklist

- [ ] PHP version meets requirements
- [ ] Required PHP extensions installed
- [ ] Web server properly configured
- [ ] Directory permissions set correctly
- [ ] SSL certificate installed (production)
- [ ] Database created (if using MySQL)
- [ ] Admin data directory is writable
- [ ] .htaccess files are working
- [ ] Default admin password changed

## Performance Recommendations

### PHP OPcache
```ini
opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=4000
```

### Session Storage
- Consider Redis/Memcached for session storage in production
- File-based sessions work fine for small installations

## Security Hardening

### Additional Steps
1. Enable PHP disable_functions for dangerous functions
2. Set up fail2ban for brute force protection
3. Configure firewall rules
4. Regular security updates
5. Monitor admin activity logs

### File Permissions
```bash
# Secure file permissions
find admin/ -type f -exec chmod 644 {} \;
find admin/ -type d -exec chmod 755 {} \;
chmod 600 admin/config.php
chmod -R 700 admin/data/
```