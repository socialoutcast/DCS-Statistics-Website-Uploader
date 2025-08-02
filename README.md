# 🎖️ DCS Statistics Website Dashboard

**Transform your DCS server data into a stunning, interactive web dashboard with real-time API integration!**

[![Live Analytics](https://img.shields.io/badge/🌐_Live_Analytics-Real_Time_Data-blue?style=for-the-badge)](http://skypirates.uk/DCS-Stats-Demo/dcs-stats/)
[![DCSServerBot](https://img.shields.io/badge/🤖_Requires-DCSServerBot-green?style=for-the-badge)](https://github.com/Special-K-s-Flightsim-Bots/DCSServerBot)
[![Security](https://img.shields.io/badge/🔒_Security-Enterprise_Grade-red?style=for-the-badge)](#-security-features)
[![Responsive](https://img.shields.io/badge/📱_Design-Fully_Responsive-purple?style=for-the-badge)](#-responsive-design)

## 🎯 What's New in 2025

### ✨ **Modern Professional Interface**
- 🖼️ **Cinematic Header** - Epic DCS combat scene background with professional overlay
- 🎨 **Unified Design System** - Consistent cards, buttons, and styling across all pages
- 📱 **Dynamic Responsive Layout** - Adapts fluidly to any screen size (98% mobile width to 1400px desktop)
- 🔍 **Unified Search Experience** - Consistent search bars with advanced functionality

### 🚀 **Real-Time API Integration**
- ⚡ **API-Only Architecture** - Direct integration with DCSServerBot REST API
- 🎛️ **Admin Panel Configuration** - Easy API endpoint setup through web interface
- 🔍 **Advanced Search** - Real-time pilot lookup with fuzzy matching
- 📊 **Live Statistics** - Instant updates without file transfers

### 🎯 **User Experience Improvements**
- 🎪 **Consistent Pilot Cards** - Unified design for credits and statistics pages
- 🌈 **Green Theme Integration** - Professional military-inspired color scheme
- ⚙️ **Adaptive Charts** - Charts only display when data is available
- 🔄 **Smart Loading States** - Proper error handling and user feedback

## 📸 Modern Dashboard Preview

Experience a professional-grade statistics platform featuring:
- 🏆 **Top 10 Leaderboards** with trophy displays and combat rankings
- 💰 **Credits System** with unified pilot card interface
- 👨‍✈️ **Individual Pilot Profiles** with dynamic statistics and combat charts
- 🛡️ **Squadron Management** with member tracking (optional)
- 🖥️ **Live Server Status** with mission info and mod displays
- 🎯 **Unified Search** - Find pilots instantly across all pages

## ⚡ Quick Start

### 🔧 Prerequisites
- ✅ [**DCSServerBot by Special K**](https://github.com/Special-K-s-Flightsim-Bots/DCSServerBot/releases) with REST API enabled
- ✅ **PHP 8.3+ web server** OR **Docker**
- ✅ **Web hosting** (shared hosting works perfectly!)

### 🚀 Installation Options

#### Option 1: Traditional Web Hosting

1. **Download** the latest release and extract
2. **Upload** the `dcs-stats/` folder to your web server
3. **Access** `https://yourdomain.com/dcs-stats/`
4. **Configure** your DCSServerBot API endpoint in the admin panel

#### Option 2: Docker Deployment

See the [Docker Setup](#-docker-deployment) section below for containerized deployment.

### ⚙️ Configuration

1. **Access the admin panel** at `/dcs-stats/admin`
2. **Enter your DCSServerBot REST API URL**
   - Example: `http://localhost:8080/api`
   - For Docker: `http://host.docker.internal:8080/api` (Windows/Mac)
3. **Save configuration** - The system automatically creates `api_config.json`
4. **Verify connection** - Check the status indicator turns green

**🎉 That's it!** Your dashboard now displays real-time data from DCSServerBot.

## 🌟 Real-Time API Architecture

### Direct API Integration

The website connects directly to DCSServerBot's REST API for all data:

```json
{
    "api_base_url": "http://localhost:8080/api",
    "use_api": true,
    "enabled_endpoints": [
        "get_leaderboard.php",
        "get_player_stats.php", 
        "search_players.php",
        "get_credits.php",
        "get_servers.php",
        "get_squadrons.php"
    ]
}
```

### 🔥 API Features
- ⚡ **Real-time updates** - Data refreshes instantly
- 🔍 **Advanced search** - Find pilots with partial names and typo tolerance
- 📊 **Live statistics** - Combat data updates in real-time
- 💰 **Credits integration** - Real-time points and rankings
- 🖥️ **Server status** - Live mission and player information

## 🎨 Professional Design System

### 🖼️ **Modern Header**
- Epic DCS combat scene background with professional overlay
- Gradient text effects with glowing shadows
- Live API status indicator with pulsing animation
- Sticky header that follows users while scrolling

### 📊 **Unified Interface**
| Component | Design | Features |
|-----------|--------|----------|
| **Pilot Cards** | Consistent dark theme with green accents | Dynamic stat tiles, responsive grids |
| **Search Bars** | Centered, professional styling | Unified across all pages, perfect alignment |
| **Tables** | Modern gradients with hover effects | Responsive design, consistent spacing |
| **Charts** | Dark theme with green highlights | Only display when data available |

### 📱 **Responsive Excellence**
- **Extra Large (1400px+)**: 80% width, maximum features
- **Large (1200px-1399px)**: 90% width, full functionality  
- **Medium (769px-1199px)**: 92% width, optimized layout
- **Small (481px-768px)**: 95% width, stacked search
- **Mobile (≤480px)**: 98% width, minimal padding

## 🎛️ Smart Feature Management

### Granular Control System
Configure exactly what your community sees:

```php
// Combat Statistics
'pilot_combat_stats' => true,     // Kills, deaths, K/D ratio
'pilot_flight_stats' => true,     // Takeoffs, landings, crashes
'pilot_session_stats' => true,    // Last session data
'pilot_aircraft_chart' => true,   // Aircraft usage charts

// Credits System  
'credits_enabled' => true,        // Enable credits system
'credits_leaderboard' => true,    // Credits rankings

// Squadron Features
'squadrons_enabled' => true,      // Squadron system
'squadron_management' => true,    // Squadron admin tools
'squadron_statistics' => true,    // Squadron stats
```

### 🎯 **Benefits**
- **Clean Interface**: Only enabled features display
- **No Null Errors**: Missing elements handled gracefully  
- **Performance**: Disabled features don't make API calls
- **Customization**: Tailor the platform to your community

## 🔍 Advanced Search System

### Bulletproof Search Features
- **Direct Lookup**: Instant exact name matching via `/getuser` API
- **Fuzzy Matching**: Handles typos and partial names intelligently
- **Multi-Endpoint**: Falls back to `/topkills` and `/topkdr` for comprehensive coverage
- **Smart Results**: Multiple matches display selection interface

### Search Flow
```
User Input → Direct API Lookup → Fuzzy Search → Multi-Endpoint Fallback → Results
```

### Error Handling
- Graceful API failures with user-friendly messages
- Automatic retry logic for transient failures
- Debug logging for troubleshooting
- Consistent experience across all pages

## 🐳 Docker Deployment

### Prerequisites

#### Install Docker

**Windows:**
1. Download [Docker Desktop for Windows](https://www.docker.com/products/docker-desktop/)
2. Run the installer (Docker Desktop Installer.exe)
3. Follow the installation wizard
4. Restart your computer
5. Start Docker Desktop from the Start menu

**Linux (Ubuntu/Debian):**
```bash
# Update package index
sudo apt update

# Install prerequisites
sudo apt install apt-transport-https ca-certificates curl software-properties-common

# Add Docker's official GPG key
curl -fsSL https://download.docker.com/linux/ubuntu/gpg | sudo gpg --dearmor -o /usr/share/keyrings/docker-archive-keyring.gpg

# Add Docker repository
echo "deb [arch=$(dpkg --print-architecture) signed-by=/usr/share/keyrings/docker-archive-keyring.gpg] https://download.docker.com/linux/ubuntu $(lsb_release -cs) stable" | sudo tee /etc/apt/sources.list.d/docker.list > /dev/null

# Install Docker
sudo apt update
sudo apt install docker-ce docker-ce-cli containerd.io docker-compose-plugin

# Add your user to docker group (logout/login required)
sudo usermod -aG docker $USER
```

**macOS:**
1. Download [Docker Desktop for Mac](https://www.docker.com/products/docker-desktop/)
2. Open the .dmg file
3. Drag Docker to Applications
4. Start Docker from Applications

**Verify Installation:**
```bash
docker --version
docker compose version
```

### Complete Docker Setup Process

1. **Clone the repository**
   ```bash
   git clone https://github.com/Penfold-88/DCS-Statistics-Website-Uploader.git
   cd DCS-Statistics-Website-Uploader
   ```

2. **Create environment configuration**
   ```bash
   # Copy the example environment file
   cp .env.docker.example .env
   
   # The default configuration works for most users
   # Port 8080 is used by default
   ```

3. **Build and start the container**
   ```bash
   # Build the Docker image and start the container
   docker compose up -d --build
   
   # This will:
   # - Download the PHP/Apache base image
   # - Install required dependencies
   # - Copy the website files
   # - Start the web server
   ```

4. **Verify the deployment**
   ```bash
   # Check if container is running
   docker compose ps
   
   # You should see:
   # NAME                STATUS              PORTS
   # dcs-stats-website   Up (healthy)        0.0.0.0:8080->80/tcp
   ```

5. **Access the website**
   - Open your browser to http://localhost:8080
   - You should see the DCS Statistics Dashboard
   - Navigate to the admin panel to configure your API

### Troubleshooting Docker Setup

**Port Already in Use:**
If you see "bind: address already in use", port 8080 is taken:
```bash
# Option 1: Stop the conflicting service
# Option 2: Use a different port
nano .env  # or your preferred editor
# Change WEB_PORT=8080 to WEB_PORT=8090
docker compose up -d
```

**Permission Denied (Linux):**
```bash
# If you get permission errors, ensure you're in the docker group
sudo usermod -aG docker $USER
# Then logout and login again
```

**Container Not Starting:**
```bash
# Check logs for errors
docker compose logs dcs-stats-web

# Rebuild from scratch
docker compose down
docker compose build --no-cache
docker compose up -d
```

### Docker Configuration Options

**Environment Variables (.env):**
```bash
# Web server port (default: 8080)
WEB_PORT=8080

# PHP Configuration
PHP_MEMORY_LIMIT=256M
PHP_MAX_UPLOAD=50M
PHP_MAX_FILE_UPLOADS=20

# Timezone
TZ=UTC
```

**Useful Docker Commands:**
```bash
# Start services
docker compose up -d

# Stop services
docker compose down

# View logs
docker compose logs -f

# Restart service
docker compose restart

# Rebuild after code changes
docker compose up -d --build

# Enter container shell
docker compose exec dcs-stats-web bash

# Check resource usage
docker stats
```

### Development Mode

For active development with live code updates:

1. Edit `docker-compose.yml`
2. Uncomment the volume mount:
   ```yaml
   # volumes:
   #   # For development: mount source code (uncomment for development)
   #   - ./dcs-stats:/var/www/html:rw
   ```
   Change to:
   ```yaml
   volumes:
     # For development: mount source code (uncomment for development)
     - ./dcs-stats:/var/www/html:rw
   ```

3. Restart the container:
   ```bash
   docker compose restart
   ```

Now changes to files in `dcs-stats/` will be reflected immediately.

## 🔒 Enterprise Security

### Multi-Layer Protection
✅ **XSS Prevention** - All inputs sanitized and escaped  
✅ **CSRF Protection** - Request validation and tokens
✅ **Rate Limiting** - API abuse prevention with throttling
✅ **Input Validation** - Comprehensive data filtering
✅ **Security Headers** - CSP, XSS protection, clickjacking prevention
✅ **Access Controls** - Admin panel protected
✅ **API Security** - Secure proxy for API requests
✅ **Safe DOM Updates** - Null reference protection

### Dynamic CSP Headers
```php
// Automatically configures CSP based on API settings
header("Content-Security-Policy: default-src 'self'; 
       connect-src 'self' {$apiUrl}; 
       script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net;");
```

## 🚀 Performance Features

### Optimized Loading
- **Lazy Loading**: Resources load only when needed
- **API Caching**: Smart caching reduces API calls
- **Efficient Updates**: Only changed data refreshes
- **Minified Assets**: Reduced payload sizes
- **CDN Integration**: Fast loading of external resources

### Real-Time Updates
- **Live Data**: Statistics update without page refresh
- **WebSocket Ready**: Architecture supports future WebSocket integration
- **Efficient Polling**: Smart intervals prevent API overload
- **Progressive Enhancement**: Core functionality loads first

## 🎯 Advanced Customization

### Theme System
```css
/* Easy color customization */
:root {
    --primary-color: #4CAF50;    /* Green accent */
    --background-color: #121212;  /* Dark background */
    --text-color: #ffffff;        /* White text */
    --card-color: #2c2c2c;       /* Card backgrounds */
}
```

### Custom Branding
- **Header Background**: Replace `dcs-header-image.jpg` with your image
- **Site Title**: Edit header.php for custom branding
- **Discord Integration**: Update nav.php with your server link
- **Color Scheme**: Modify CSS variables for custom themes

## 🔧 Troubleshooting Guide

### 🔍 **API Connection Issues**
```bash
# Test API directly
curl http://localhost:8080/api/ping

# Check browser console
F12 → Console Tab → Look for errors

# Verify CORS settings in DCSServerBot
# Ensure your domain is allowed
```

### 📊 **Missing Statistics**
1. **Check API status** in the header (should be green)
2. **Verify feature toggles** in `site_features.php`
3. **Test API endpoints** directly in browser
4. **Review browser console** for JavaScript errors

### 🎨 **Styling Issues**
```bash
# Clear browser cache
Ctrl+F5 (Windows) or Cmd+Shift+R (Mac)

# Check CSS loading
Browser DevTools → Network Tab → Reload Page

# Verify file permissions
chmod 644 dcs-stats/styles.css
```

## 📁 Modern File Structure

```
DCS-Statistics-Website-Uploader/
├── 📁 dcs-stats/                  # Web dashboard
│   ├── 🏠 index.php              # Homepage with server stats
│   ├── 🏆 leaderboard.php        # Top 10 combat rankings  
│   ├── 💰 pilot_credits.php      # Credits system
│   ├── 👨‍✈️ pilot_statistics.php   # Individual pilot lookup
│   ├── 🛡️ squadrons.php          # Squadron management
│   ├── 🖥️ servers.php            # Live server status
│   ├── 🎛️ admin/                 # Admin panel for API config
│   ├── 🎨 styles.css             # Unified design system
│   ├── 🔧 api_proxy.php          # Secure API proxy
│   ├── 🧠 js/api-client.js       # Frontend API client
│   ├── ⚙️ site_features.php      # Feature toggles
│   ├── 🔒 security_functions.php # Security utilities
│   └── 🖼️ dcs-header-image.jpg   # Header background
├── 🐳 Dockerfile                 # Docker container setup
├── 🐳 docker-compose.yml         # Docker orchestration
├── 📋 .env.docker.example        # Docker config template
└── 📚 README.md                  # This guide
```

## 🌐 API Endpoints

### Available Endpoints
```javascript
// Direct API access for developers
GET  /dcs-stats/get_leaderboard.php     // Top pilots
GET  /dcs-stats/get_player_stats.php    // Individual stats
POST /dcs-stats/search_players.php      // Search pilots
GET  /dcs-stats/get_credits.php         // Credits leaderboard
GET  /dcs-stats/get_servers.php         // Server status
GET  /dcs-stats/get_squadrons.php       // Squadron data
```

### Integration Example
```javascript
// Fetch player statistics
async function getPlayerStats(playerName) {
    const response = await fetch(`/dcs-stats/get_player_stats.php?name=${playerName}`);
    const data = await response.json();
    return data;
}
```

## 🎯 Roadmap & Future Features

### Coming Soon
- 🔄 **WebSocket Support** - Real-time combat updates
- 📊 **Advanced Analytics** - Trend analysis and insights
- 🎮 **Mission Integration** - Detailed mission breakdowns
- 🏆 **Tournament Mode** - Competition management
- 📱 **Mobile App** - Native iOS/Android companion
- 🤖 **Discord Bot** - Live stats in Discord

### Community Requests
- 🎨 **Theme Marketplace** - Share custom themes
- 📈 **Historical Data** - Long-term statistics
- 🎯 **Achievement System** - Automated awards
- 🔗 **Multi-Server** - Federation support

## 🤝 Contributing

We welcome contributions from the DCS community!

### Development Setup
```bash
# Clone repository
git clone https://github.com/Penfold-88/DCS-Statistics-Website-Uploader.git

# Create feature branch
git checkout -b feature/amazing-feature

# Make changes and test
# Submit pull request
```

### Contribution Guidelines
- ✅ Follow existing code patterns
- ✅ Test responsive design
- ✅ Ensure security best practices
- ✅ Update documentation
- ✅ Include screenshots for UI changes

## 📄 License & Credits

### License
This project is licensed under the **MIT License** - see [LICENSE](LICENSE) file.

### 🙏 Acknowledgments
- **DCSServerBot** by [Special K](https://github.com/Special-K-s-Flightsim-Bots/DCSServerBot) - The foundation of this system
- **Sky Pirates Squadron** - Original development and testing
- **DCS Community** - Continuous feedback and improvements
- **Eagle Dynamics** - For creating DCS World

### 🎖️ Community Recognition
Special thanks to server administrators worldwide using this system!

---

## 🚀 Get Started Today

**⭐ Star this repository** if it helps your community!  
**🐛 Report issues** to help improve the platform  
**💬 Share with other** DCS server administrators  
**🎮 Join the community** and showcase your dashboard

### Support Links
- 💬 [**Discord Support**](https://discord.gg/uTk8uQ2hxC) - Get help and chat with the community
- 📖 [**Documentation**](https://github.com/Penfold-88/DCS-Statistics-Website-Uploader/wiki)
- 🐛 [**Issue Tracker**](https://github.com/Penfold-88/DCS-Statistics-Website-Uploader/issues)
- 🌐 [**Live Demo**](http://skypirates.uk/DCS-Stats-Demo/dcs-stats/)

**Transform your DCS server into a professional gaming platform today!** 🎖️