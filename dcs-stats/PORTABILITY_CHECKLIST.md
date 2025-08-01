# DCS Statistics Website - Portability Checklist

This website has been designed to be 100% portable and work in any installation directory.

## ✅ Portability Features

1. **Dynamic Path Detection**
   - All paths are calculated relative to the installation directory
   - No hardcoded absolute paths
   - Works in root (/) or any subdirectory (/stats/, /dcs-stats/, etc.)

2. **API Configuration**
   - Automatic HTTP/HTTPS protocol detection
   - No hardcoded server addresses
   - Configuration stored in `api_config.json`

3. **No External Dependencies**
   - No CDN requirements (all assets local)
   - No database required for basic operation
   - Admin panel uses file-based storage

4. **Clean Production Code**
   - All debug code removed
   - Console.log statements removed
   - Error display suppressed
   - No test files included

## 📋 Installation Requirements

1. **Web Server**
   - Apache or Nginx
   - PHP 7.4 or higher
   - URL rewriting NOT required

2. **PHP Extensions**
   - curl (for API communication)
   - json
   - session

3. **Directory Permissions**
   - `site-config/data/` - writable by web server (for admin panel)
   - All other directories - read-only is fine

## 🚀 Installation Steps

1. **Upload Files**
   - Upload entire `dcs-stats` directory to your web server
   - Can be placed in any directory (root or subdirectory)

2. **Configure API**
   - Navigate to `/site-config/` in your browser
   - Login with default credentials (see admin panel docs)
   - Go to API Settings
   - Enter your DCSServerBot API URL (e.g., `dcs.example.com:8080`)
   - Save settings

3. **Verify Installation**
   - Check that all pages load correctly
   - Verify API connection in admin panel
   - Test data displays properly

## 🔒 Security Notes

- Change default admin credentials immediately
- Consider password-protecting the `/site-config/` directory
- Ensure `site-config/data/` is not publicly accessible
- Regular backups of `site-config/data/` recommended

## ⚙️ Configuration Files

- `api_config.json` - API settings (auto-created)
- `site-config/data/` - Admin panel data
- `custom_theme.css` - Optional custom styling

## 🌐 Multi-Environment Support

The system automatically adapts to:
- HTTP or HTTPS protocols
- Different domain names
- Subdirectory installations
- Various server configurations

No code changes required when moving between environments!