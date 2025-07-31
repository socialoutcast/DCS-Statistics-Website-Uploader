# 🎖️ DCS Statistics Website Dashboard

**Transform your DCS server data into a stunning, interactive web dashboard with modern design and powerful features!**

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

### 🚀 **Enhanced Features**
- ⚡ **API-First Architecture** - Real-time data with JSON fallback for reliability
- 🎛️ **Smart Feature Management** - Granular control over what statistics display
- 🔍 **Bulletproof Search** - Multi-layered search with fuzzy matching and typo tolerance
- 📊 **Dynamic Statistics** - Only shows enabled features, hides disabled content

### 🎯 **User Experience Improvements**
- 🎪 **Consistent Pilot Cards** - Unified design for credits and statistics pages
- 🌈 **Green Theme Integration** - Professional military-inspired color scheme
- ⚙️ **Adaptive Charts** - Charts only display when data is available and features enabled
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
- ✅ [**DCSServerBot by Special K**](https://github.com/Special-K-s-Flightsim-Bots/DCSServerBot/releases) (with dbexporter module)
- ✅ **Python 3.13.3+** (included with DCSServerBot)
- ✅ **PHP 8.3+ web server** with FTP access
- ✅ **Web hosting** (shared hosting works perfectly!)

### 🚀 Installation

#### 1️⃣ Setup DCSServerBot Export
```bash
# Install the dbexporter module following the official guide
```
📖 [**DCSServerBot dbexporter Documentation**](https://github.com/Special-K-s-Flightsim-Bots/DCSServerBot/blob/master/plugins/dbexporter/README.md)

#### 2️⃣ Deploy Website Files
1. **Download** the latest release and extract
2. **Upload** the `dcs-stats/` folder to your web server
3. **Verify** you can access `https://yourdomain.com/dcs-stats/`

#### 3️⃣ Configure Auto-Uploader (Secure Method)
```bash
# Use environment variables for security
cp Stats-Uploader/.env.example Stats-Uploader/.env

# Edit .env with your settings
FTP_HOST=your.ftp.server.com
FTP_USER=your_ftp_username  
FTP_PASSWORD=your_ftp_password
FTP_SECURE=true
LOCAL_FOLDER=/path/to/DCSServerBot/export
REMOTE_FOLDER=/data
```

#### 4️⃣ Install & Run
```bash
# Install dependencies
pip install -r Stats-Uploader/requirements.txt

# Start the uploader service
python Stats-Uploader/uploader.py
```

**🎉 Live in minutes!** Your dashboard updates automatically with fresh combat data.

## 🌟 Modern API Integration

### Real-Time Data Pipeline

Enable **instant data updates** with DCSServerBot's REST API:

```json
{
    "api_base_url": "http://localhost:8080/api",
    "use_api": true,
    "fallback_to_json": true,
    "enabled_endpoints": [
        "get_leaderboard.php",
        "get_player_stats.php", 
        "search_players.php",
        "get_credits.php",
        "get_servers.php"
    ]
}
```

### 🔥 API Advantages
- ⚡ **Real-time updates** - No waiting for file uploads
- 🔍 **Advanced search** - Find pilots with partial names and typo tolerance
- 🛡️ **Automatic fallback** - Seamlessly switches to JSON if API unavailable
- 📊 **Live statistics** - Combat data updates instantly
- 💰 **Credits integration** - Real-time points and rankings

## 🎨 Professional Design System

### 🖼️ **Modern Header**
- Epic DCS combat scene background with professional overlay
- Gradient text effects with glowing shadows
- Live status indicator with pulsing animation
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

// Squadron Features (disabled by default)
'squadrons_enabled' => false,     // Squadron system
'squadron_management' => false,   // Squadron admin tools
'squadron_statistics' => false,   // Squadron stats
```

### 🎯 **Benefits**
- **Clean Interface**: Only enabled features display
- **No Null Errors**: Missing elements handled gracefully  
- **Performance**: Disabled features don't load resources
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
- Graceful API failures with JSON fallback
- User-friendly error messages with suggestions
- Debug logging for troubleshooting
- Consistent experience across all pages

## 📊 Data Architecture

### Supported Data Sources
The system processes **28+ data types** from DCSServerBot:

| Core Files | Purpose | API Support |
|------------|---------|-------------|
| `players.json` | Player database | ✅ Real-time |
| `missionstats.json` | Combat events | ✅ Real-time |
| `credits.json` | Points system | ✅ Real-time |
| `instances.json` | Server info | ✅ Real-time |
| `squadrons.json` | Squadron data | 📁 JSON only |
| `missions.json` | Mission tracking | 📁 JSON only |

### Hybrid Mode (Recommended)
- **API First**: Real-time data for combat stats, search, leaderboards
- **JSON Fallback**: Squadron data, advanced features, offline resilience
- **Smart Routing**: Automatically chooses best data source per request

## 🔒 Enterprise Security

### Multi-Layer Protection
✅ **XSS Prevention** - All inputs sanitized and escaped  
✅ **CSRF Protection** - Request validation and tokens
✅ **Rate Limiting** - API abuse prevention with throttling
✅ **Input Validation** - Comprehensive data filtering and sanitization
✅ **Security Headers** - CSP, XSS protection, clickjacking prevention
✅ **Access Controls** - Direct file access blocked via .htaccess
✅ **FTPS Encryption** - Secure file transfers by default
✅ **Safe Element Updates** - Null reference protection

### Dynamic CSP Headers
```php
// Automatically configures CSP based on API settings
header("Content-Security-Policy: default-src 'self'; 
       connect-src 'self' {$dynamicApiUrl}; 
       script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net;");
```

## 🚀 Performance Features

### Optimized Loading
- **Lazy Chart Loading**: Charts only render when features enabled
- **Dynamic DOM Updates**: Only update elements that exist
- **Efficient CSS**: Unified stylesheets reduce HTTP requests
- **Image Optimization**: Compressed backgrounds and logos
- **Responsive Images**: Adaptive sizing for different screens

### Caching Strategy
- **Browser Caching**: Optimized cache headers for static assets
- **API Response Caching**: Smart caching with invalidation
- **CSS/JS Minification**: Reduced payload sizes
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

### Feature Toggle System
```php
// Disable features you don't need
return [
    'nav_squadrons' => false,        // Hide squadron nav
    'pilot_aircraft_chart' => false, // Disable aircraft charts
    'credits_enabled' => true,       // Enable credits system
    'pilot_session_stats' => false,  // Hide session stats
];
```

### Custom Branding
- **Header Background**: Replace `dcs-header-image.jpg` with your image
- **Site Title**: Edit header.php for custom branding
- **Discord Integration**: Update nav.php with your server link
- **Color Scheme**: Modify CSS variables for custom themes

## 🔧 Troubleshooting Guide

### 🔍 **Search Issues**
```bash
# Check API connectivity
curl -X POST http://localhost:8080/api/getuser -d '{"nick":"testuser"}'

# Verify players.json exists
ls -la dcs-stats/data/players.json

# Check browser console for JavaScript errors
F12 → Console Tab
```

### 📊 **Missing Statistics**
1. **Check feature toggles** in `site_features.php`
2. **Verify API endpoints** in `api_config.json`
3. **Confirm data files** uploaded to `/data` folder
4. **Review error logs** in browser console

### 🎨 **Styling Issues**
```bash
# Clear browser cache
Ctrl+F5 (Windows) or Cmd+Shift+R (Mac)

# Check CSS loading
Browser DevTools → Network Tab → Reload Page

# Verify file permissions
chmod 644 dcs-stats/styles.css
```

### 🔗 **API Connection Issues**
1. **Test API directly**: Visit `http://localhost:8080/api/ping`
2. **Check CORS settings** in DCSServerBot config
3. **Verify network connectivity** between web server and API
4. **Review proxy settings** in `api_proxy.php`

## 📁 Modern File Structure

```
DCS-Statistics-Website-Uploader/
├── 📁 Stats-Uploader/              # Python uploader service
│   ├── 🐍 uploader.py             # Main upload script
│   ├── ⚙️ config.ini              # Configuration file
│   ├── 🔒 .env.example            # Secure credentials template
│   └── 📦 requirements.txt        # Python dependencies
├── 📁 dcs-stats/                  # Modern web dashboard
│   ├── 🏠 index.php              # Homepage with server stats
│   ├── 🏆 leaderboard.php        # Top 10 combat rankings  
│   ├── 💰 pilot_credits.php      # Credits system with unified cards
│   ├── 👨‍✈️ pilot_statistics.php   # Individual pilot lookup
│   ├── 🛡️ squadrons.php          # Squadron management (optional)
│   ├── 🖥️ servers.php            # Live server status
│   ├── 🎨 styles.css             # Unified design system
│   ├── 🔧 api_proxy.php          # API integration proxy
│   ├── 🧠 js/api-client.js       # Frontend API client
│   ├── ⚙️ site_features.php      # Feature management system
│   ├── 🔒 security_functions.php # Security utilities
│   ├── 📁 data/                  # JSON files directory
│   └── 🖼️ dcs-header-image.jpg   # Epic header background
├── 📚 FIXES.md                   # Security & troubleshooting guide
└── 📋 README.md                  # This comprehensive guide
```

## 🌐 Integration & Embedding

### 📱 **Website Integration**
```html
<!-- Responsive iframe embedding -->
<iframe src="https://yourdomain.com/dcs-stats" 
        width="100%" 
        height="800px" 
        frameborder="0"
        style="border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.3);">
</iframe>
```

### 🔌 **API Endpoints**
```javascript
// Direct API access for developers
const leaderboard = await fetch('/dcs-stats/get_leaderboard.php');
const playerStats = await fetch('/dcs-stats/get_player_stats.php?name=Pilot');
const credits = await fetch('/dcs-stats/get_credits.php');
const servers = await fetch('/dcs-stats/get_servers.php');
```

## 🎯 Roadmap & Future Features

### Coming Soon
- 🔄 **Real-time WebSocket** updates for live combat tracking
- 📊 **Advanced Analytics** with combat trend analysis  
- 🎮 **Mission Integration** with detailed mission breakdowns
- 🏆 **Tournament Mode** with bracket management
- 📱 **Mobile App** companion for iOS/Android
- 🤖 **Discord Bot** integration for live stats in chat

### Community Requests
- 🎨 **Theme Marketplace** - Community-created themes
- 📈 **Historical Data** - Long-term trend analysis
- 🎯 **Achievement System** - Automated badge awards
- 🔗 **Multi-Server** support for large communities

## 🤝 Contributing

We welcome contributions from the DCS community!

### Development Setup
```bash
# Clone the repository
git clone https://github.com/socialoutcast/DCS-Statistics-Website-Uploader.git

# Create feature branch
git checkout -b feature/amazing-new-feature

# Make your changes
# Test thoroughly
# Submit pull request
```

### Contribution Guidelines
- ✅ Follow existing code style and patterns
- ✅ Test on multiple screen sizes (mobile, tablet, desktop)
- ✅ Ensure security best practices
- ✅ Update documentation for new features
- ✅ Include screenshots for UI changes

## 📄 License & Credits

### License
This project is licensed under the **MIT License** - see the [LICENSE](LICENSE) file for details.

### 🙏 Acknowledgments
- **DCSServerBot** by [Special K](https://github.com/Special-K-s-Flightsim-Bots/DCSServerBot) - The foundation that powers this system
- **Sky Pirates Squadron** - Original development, testing, and feedback
- **DCS Community** - Feature requests, bug reports, and continuous improvement ideas
- **Eagle Dynamics** - For creating the amazing DCS World platform

### 🎖️ Community Recognition
Special thanks to server administrators and communities worldwide who use this system to enhance their DCS experience!

---

## 🚀 Get Started Today

**⭐ Star this repository** if it helps your community!  
**🐛 Report issues** to help us improve the platform  
**💬 Share with other DCS server administrators**  
**🎮 Join the community** and showcase your statistics dashboard

### Support Links
- 📖 [**Documentation**](https://github.com/socialoutcast/DCS-Statistics-Website-Uploader/wiki)
- 🐛 [**Issue Tracker**](https://github.com/socialoutcast/DCS-Statistics-Website-Uploader/issues)
- 💬 [**Community Discord**](https://discord.gg/your-community-link)
- 🌐 [**Live Demo**](http://skypirates.uk/DCS-Stats-Demo/dcs-stats/)

**Transform your DCS server into a professional gaming platform today!** 🎖️