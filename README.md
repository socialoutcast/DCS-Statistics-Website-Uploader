# 🎖️ DCS Statistics Dashboard

**Transform your DCS server data into a stunning, interactive web dashboard with real-time API integration!**

[![Live Analytics](https://img.shields.io/badge/🌐_Live_Analytics-Real_Time_Data-blue?style=for-the-badge)](http://skypirates.uk/DCS-Stats-Demo/dcs-stats/)
[![DCSServerBot](https://img.shields.io/badge/🤖_Requires-DCSServerBot-green?style=for-the-badge)](https://github.com/Special-K-s-Flightsim-Bots/DCSServerBot)
[![Security](https://img.shields.io/badge/🔒_Security-Enterprise_Grade-red?style=for-the-badge)](#-security-features)
[![Responsive](https://img.shields.io/badge/📱_Design-Fully_Responsive-purple?style=for-the-badge)](#-responsive-design)

## 🎯 What's New in v1.0.0

### 🔄 **Latest Updates**
- 🌐 **Default Port Changed** - Now uses port 9080 (was 8080) to avoid conflicts
- 🛠️ **Unified Management Script** - Single `dcs-docker-manager.bat`/`.sh` for all Docker operations
- 🧹 **Two Cleanup Options** - `destroy` preserves data, `sanitize` removes everything
- 🚀 **Force Flags** - Add `-f` to skip confirmation prompts for automation
- 📜 **Improved Logs** - Shows last 100 lines without requiring Ctrl+C to exit
- 🔨 **Rebuild Command** - Force fresh Docker image builds when needed
- ✈️ **Pre-Flight Checks** - Automated Windows/Linux issue detection and resolution
- 🔄 **Nginx Proxy Manager** - Professional reverse proxy with web UI (optional)
- 🎛️ **Flexible Proxy Options** - Choose between Nginx Proxy Manager, simple nginx, or no proxy

### 🚀 **Advanced Admin Panel**
- 🎛️ **Role-Based Access Control** - Multi-tier permission system (Air Boss, Squadron Leader, Pilot)
- 🔐 **Secure Authentication** - Modern login system with session management
- 🔄 **Auto-Update System** - One-click updates from GitHub with version tracking
- 💾 **Backup & Restore** - Automatic backups before updates with version metadata
- 🎨 **Theme Manager** - Pre-built themes (Sky Pirates, Grim Reapers, Blue Angels, more!)

### ✨ **Modern Professional Interface**
- 🖼️ **Cinematic Header** - Epic DCS combat scene background with professional overlay
- 🎨 **Unified Design System** - Consistent cards, buttons, and styling across all pages
- 📱 **Dynamic Responsive Layout** - Adapts fluidly to any screen size (98% mobile width to 1400px desktop)
- 🔍 **Unified Search Experience** - Consistent search bars with advanced functionality

### 🛡️ **Advanced Features**
- 📊 **Feature Management** - Granular control over every dashboard element
- 🌐 **Enhanced API Client** - Bulletproof error handling and retry logic
- 🐳 **Zero-Config Docker** - Complete containerized deployment with auto-setup
- 📈 **Performance Monitoring** - Built-in API health checks and status indicators

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
- ✅ **Web hosting** (shared hosting works Requires Port Forwarding Not All Hosts allow)

### 🚀 Installation Methods

#### Method 1: Docker (Recommended - Fully Automated)

##### **Windows Users - Professional Deployment**

**Quick Start:**
1. **Install Docker Desktop** from [docker.com](https://www.docker.com/products/docker-desktop/)
2. **Extract the downloaded folder** to your preferred location
3. **Run `dcs-docker-manager.bat pre-flight`** - First-time setup (recommended)
4. **Run `dcs-docker-manager.bat start`** - Launch the application
5. **Access at `http://localhost:9080`** when ready
6. **Complete setup** at `http://localhost:9080/site-config/install.php`

**What happens automatically:**
- ✅ Docker Desktop detection and startup assistance
- ✅ Windows-specific issue resolution (line endings, permissions)
- ✅ Environment configuration (.env file creation)
- ✅ **Proxy Configuration** - Choose your web server setup:
  - **Nginx Proxy Manager** - Full reverse proxy with web UI (port 81)
  - **Simple Nginx** - Basic web server for local development
  - **No Proxy** - Skip if you have existing nginx/haproxy
- ✅ Port availability checking (auto-selects if 9080 is busy)
- ✅ Container build and initialization
- ✅ PHP, Redis, and nginx/proxy server configuration
- ✅ Health check verification
- ✅ Network IP discovery and display

**Managing the Application:**
- **Start:** Run `dcs-docker-manager.bat` or `dcs-docker-manager.bat start`
- **Stop:** Run `dcs-docker-manager.bat stop`
- **Restart:** Run `dcs-docker-manager.bat restart`
- **View Logs:** Run `dcs-docker-manager.bat logs` (shows last 100 lines)
- **Check Status:** Run `dcs-docker-manager.bat status`
- **Rebuild Image:** Run `dcs-docker-manager.bat rebuild` (forces fresh Docker build)
- **Pre-Flight Check:** Run `dcs-docker-manager.bat pre-flight` (recommended for first-time setup)
- **Partial Removal:** Run `dcs-docker-manager.bat destroy` (removes everything except your data)
- **Complete Wipe:** Run `dcs-docker-manager.bat sanitize` (removes EVERYTHING including all data)

##### **Linux Users**

```bash
# Navigate to the extracted folder
cd DCS-Statistics-Dashboard

# Make scripts executable (first time only)
chmod +x dcs-docker-manager.sh

# Run pre-flight checks (recommended for first time)
./dcs-docker-manager.sh pre-flight

# Start the application
./dcs-docker-manager.sh start

# Access at http://localhost:9080
# If using Nginx Proxy Manager, admin panel at http://localhost:81

# To stop:
./dcs-docker-manager.sh stop
```

#### Method 2: Traditional Web Hosting

1. **Download** the latest release and extract
2. **Upload** the `dcs-stats/` folder to your web server
3. **Access** `https://yourdomain.com/dcs-stats/`
4. **Follow the setup wizard** to create your admin account
5. **Configure** your DCSServerBot API endpoint


### ⚙️ First-Time Setup

1. **Access your dashboard** at `http://yourdomain.com/dcs-stats/`
2. **Click "Start Setup"** on the welcome screen
3. **Create your admin account** (you'll be the Air Boss!)
4. **Configure DCSServerBot API**:
   - Enter your API URL (e.g., `http://localhost:8080` for DCSServerBot)
   - Test the connection
   - Save configuration
5. **Customize your dashboard**:
   - Choose a theme
   - Enable/disable features
   - Set your Discord link

**🎉 That's it!** Your dashboard now displays real-time data from DCSServerBot.

#### Method 3: XAMPP (Local Development)

A Full Howto on this can be found in the wiki https://github.com/Penfold-88/DCS-Statistics-Dashboard/wiki

## 🎛️ Admin Panel Features

### 🔐 Secure Access
Access the admin panel at `/dcs-stats/site-config/` (NOT `/admin`!)

### 👥 Role-Based Permissions

| Role | Dashboard Access | API Config | Updates | User Management | Themes |
|------|-----------------|------------|---------|-----------------|---------|
| **Air Boss** | ✅ Full | ✅ | ✅ | ✅ | ✅ |
| **Squadron Leader** | ✅ View | ❌ | ❌ | ✅ Limited | ✅ |
| **Pilot** | ✅ View Only | ❌ | ❌ | ❌ | ❌ |

### 🚀 Auto-Update System

1. **Version Tracking** - Know exactly what version you're running
2. **Update Notifications** - Get alerts when updates are available
3. **One-Click Updates** - Update directly from the admin panel
4. **Automatic Backups** - Creates backup before every update
5. **Version History** - Track all updates and changes
6. **Branch Support** - Switch between stable and development branches

### 💾 Backup Management

- **Automatic Backups** - Before updates and on schedule
- **Manual Backups** - Create snapshots anytime
- **Version Metadata** - Each backup includes version and branch info
- **Easy Restore** - One-click restore to any backup
- **Auto-Cleanup** - Keeps only the 5 most recent backups
- **Download Backups** - Export for external storage

### 🎨 Theme System

Pre-built professional themes included:
- 🏴‍☠️ **Sky Pirates** - Dark theme with green accents
- 💀 **Grim Reapers** - High contrast red theme
- 🔵 **Blue Angels** - Navy blue professional theme
- 🌊 **Navy** - Classic military styling
- 🎖️ **Air Force** - Light blue aviation theme
- 🔥 **Danger Zone** - Bold orange accents
- 🌙 **Night Ops** - Ultra-dark stealth mode
- ❄️ **Arctic** - Cool blue winter theme

## 🐳 Docker Deployment Details

### Professional Docker Architecture

Our Docker deployment provides enterprise-grade containerization with intelligent automation:

**Key Features:**
- 🔧 **Zero-Configuration Deployment** - Works out of the box
- 🔄 **Automatic Port Management** - Intelligently selects available ports
- 🛡️ **Container Isolation** - Secure, isolated environment
- 📊 **Resource Optimization** - Minimal resource usage
- 🔐 **Security Best Practices** - Non-root containers, network isolation
- 🌐 **Professional Proxy Options** - Choose your preferred setup:
  - **Nginx Proxy Manager** - Full GUI for SSL, proxy hosts, redirects
  - **Simple Nginx** - Lightweight option for basic serving
  - **No Proxy** - Integrate with your existing infrastructure

### 🔄 Proxy Configuration Options

#### **Option 1: Nginx Proxy Manager (Recommended)**
- **Web-based administration panel** on port 81
- **SSL certificate management** with Let's Encrypt
- **Multiple proxy hosts** support
- **Access lists and authentication**
- **Custom locations and redirects**
- **WebSocket support**
- **Default credentials**: admin@example.com / changeme

#### **Option 2: Simple Nginx**
- **Lightweight web server**
- **No configuration UI**
- **Perfect for local development**
- **Direct access on port 9080**

#### **Option 3: No Proxy (BYO)**
- **Skip proxy installation**
- **Use your existing nginx/haproxy/Apache**
- **PHP-FPM available on port 9000**
- **Configure your proxy to forward to the PHP container**

### System Requirements

**Windows:**
- Docker Desktop for Windows (includes Docker Compose)
- Windows 10/11 Pro, Enterprise, or Education (64-bit)
- WSL2 backend enabled (recommended)
- 4GB RAM minimum
- Ports: 9080 (web), 81 (NPM admin if selected)

**Linux:**
- Docker Engine 20.10+
- Docker Compose 2.0+
- Any modern Linux distribution
- 2GB RAM minimum
- Ports: 9080 (web), 81 (NPM admin if selected)

### Docker Commands Reference

**Windows Users:**
```batch
# Run pre-flight checks (recommended for first time)
dcs-docker-manager.bat pre-flight
  - Checks Docker installation
  - Creates required directories
  - Configures proxy settings
  - Sets up environment

# Start the application
dcs-docker-manager.bat start
  - Runs pre-flight if first time
  - Starts all containers
  - Shows access URLs

# Stop the application
dcs-docker-manager.bat stop

# Restart the application
dcs-docker-manager.bat restart

# Check status
dcs-docker-manager.bat status

# View logs (last 100 lines)
dcs-docker-manager.bat logs

# Force rebuild Docker image
dcs-docker-manager.bat rebuild

# Remove Docker artifacts (preserves data)
dcs-docker-manager.bat destroy     # Prompts for confirmation
dcs-docker-manager.bat destroy -f  # Skip confirmation
  - Removes ALL Docker images (nginx, php, redis, npm)
  - Removes all volumes and networks
  - Deletes .env configuration
  - PRESERVES your data in ./dcs-stats

# Complete removal including ALL data
dcs-docker-manager.bat sanitize    # Prompts for confirmation
dcs-docker-manager.bat sanitize -f # Skip confirmation
  - Everything destroy does PLUS
  - DELETES all data directories
  - DELETES all backups
  - Complete fresh start
```

**Linux Users:**
```bash
# Run pre-flight checks (recommended for first time)
./dcs-docker-manager.sh pre-flight
  - Checks Docker installation
  - Creates required directories  
  - Configures proxy settings
  - Sets up environment

# Start application
./dcs-docker-manager.sh start
  - Runs pre-flight if first time
  - Starts all containers
  - Shows access URLs

# Stop application
./dcs-docker-manager.sh stop

# Restart application
./dcs-docker-manager.sh restart

# View status
./dcs-docker-manager.sh status

# View logs (last 100 lines)
./dcs-docker-manager.sh logs

# Force rebuild Docker image
./dcs-docker-manager.sh rebuild

# Remove Docker artifacts (preserves data)
./dcs-docker-manager.sh destroy     # Prompts for confirmation
./dcs-docker-manager.sh destroy -f  # Skip confirmation
  - Removes ALL Docker images (nginx, php, redis, npm)
  - Removes all volumes and networks
  - Deletes .env configuration
  - PRESERVES your data in ./dcs-stats

# Complete removal including ALL data
./dcs-docker-manager.sh sanitize    # Prompts for confirmation
./dcs-docker-manager.sh sanitize -f # Skip confirmation
  - Everything destroy does PLUS
  - DELETES all data directories
  - DELETES all backups
  - Complete fresh start
```

### Troubleshooting Docker Issues

**Windows Specific Issues:**

1. **"Docker Desktop is not running"**
   - Solution: `dcs-docker-manager.bat` will attempt to start it automatically
   - Manual: Start Docker Desktop from Start Menu

2. **"Port 9080 is already in use"**
   - Solution: The scripts automatically find an available port
   - Manual: Edit `.env` file and change `WEB_PORT=9080` to another port

3. **"Permission denied" errors**
   - Solution: Run `dcs-docker-manager.bat pre-flight` first
   - This fixes file permissions and line endings

4. **"WSL2 not installed"**
   - Solution: Enable WSL2 in Docker Desktop settings
   - Or run: `wsl --install` in PowerShell as Administrator

**All Platforms:**

1. **Container won't start:**
   ```bash
   # Remove old containers
   docker-compose down
   docker-compose up --build --no-cache
   ```

2. **View container logs:**
   ```bash
   docker-compose logs -f
   ```

3. **Reset everything:**
   ```bash
   docker-compose down -v
   docker-compose build --no-cache
   docker-compose up
   ```

**Windows PowerShell:**
```powershell
# Navigate to the extracted folder
cd DCS-Statistics-Dashboard

# Run pre-flight checks first (recommended for new installs)
dcs-docker-manager.bat pre-flight

# Start the containers
dcs-docker-manager.bat

# Other commands
dcs-docker-manager.bat stop      # Stop the container
dcs-docker-manager.bat restart   # Restart the container
dcs-docker-manager.bat status    # Check if running
dcs-docker-manager.bat logs      # View live logs
dcs-docker-manager.bat destroy   # Remove everything (with confirmation)
```

**Windows Command Prompt (Batch):**
```batch
REM Navigate to the extracted folder
cd DCS-Statistics-Dashboard

REM Run the launcher (double-click or run in cmd)
dcs-docker-manager.bat
```

**Linux (Bash):**
```bash
# Navigate to the extracted folder
cd DCS-Statistics-Dashboard

# Make script executable (first time only)
chmod +x dcs-docker-manager.sh

# Run the launcher
./dcs-docker-manager.sh

# Other commands
./dcs-docker-manager.sh stop      # Stop the container
./dcs-docker-manager.sh restart   # Restart the container  
./dcs-docker-manager.sh status    # Check if running
./dcs-docker-manager.sh logs      # View live logs
```

#### Option 2: Manual Docker Commands

```bash
# Navigate to the extracted folder
cd DCS-Statistics-Dashboard

# Build and start (always use no-cache for consistent builds)
docker compose build --no-cache
docker compose up -d

# Access at http://localhost:9080
```

### 🗑️ Cleanup Commands

#### **Destroy Command (Preserves Data)**

```batch
# Windows
dcs-docker-manager.bat destroy     # With confirmation prompt
dcs-docker-manager.bat destroy -f  # Skip confirmation

# Linux
./dcs-docker-manager.sh destroy     # With confirmation prompt
./dcs-docker-manager.sh destroy -f  # Skip confirmation
```

The `destroy` command will:
- Stop and remove the DCS Statistics container
- Delete the Docker image
- Remove all Docker volumes
- Clean up Docker networks
- Delete your .env configuration file
- **✅ PRESERVE your data in ./dcs-stats directory**

After destroy, run `pre-flight` to start fresh with your data intact.

#### **Sanitize Command (Complete Wipe)**

```batch
# Windows
dcs-docker-manager.bat sanitize     # With confirmation prompt
dcs-docker-manager.bat sanitize -f  # Skip confirmation

# Linux
./dcs-docker-manager.sh sanitize     # With confirmation prompt
./dcs-docker-manager.sh sanitize -f  # Skip confirmation
```

The `sanitize` command will:
- Everything that `destroy` does, PLUS:
- **❌ DELETE ./dcs-stats/data directory**
- **❌ DELETE ./dcs-stats/site-config/data directory**
- **❌ DELETE ./dcs-stats/backups directory**
- **⚠️ THIS CANNOT BE UNDONE!**

Use `sanitize` when you need a complete fresh start with no data.

### 🎨 What the Launcher Scripts Do

1. **Check Docker Installation** - Verify Docker and Docker Compose are available
2. **Port Availability** - Check if port 9080 is free, find alternative if not
3. **Container Management** - Stop any existing containers before starting
4. **Build Fresh** - Always build with `--no-cache` for consistency
5. **Network Discovery** - Display all available access URLs:
   - Local: `http://localhost:PORT`
   - Network: `http://YOUR_NETWORK_IP:PORT`
   - External: `http://YOUR_PUBLIC_IP:PORT` (requires port forwarding)
6. **Port Forwarding Guide** - Show router configuration instructions if needed

### 📊 Launcher Features Comparison

| Feature | PowerShell (.ps1) | Batch (.bat) | Bash (.sh) |
|---------|------------------|--------------|------------|
| Port Auto-Selection | ✅ | ✅ | ✅ |
| Network IP Discovery | ✅ | ✅ | ✅ |
| External IP Lookup | ✅ | ✅ | ✅ |
| Colored Output | ✅ | ✅* | ✅ |
| Command Arguments | ✅ | ❌ | ✅ |
| Health Check | ✅ | ✅ | ✅ |

*Windows 10+ with ANSI support

### 🛠️ Manual Docker Commands

```bash
# View logs
docker compose logs -f

# Stop the container
docker compose down

# Update to latest version
git pull
docker compose build --no-cache
docker compose up -d

# Access container shell
docker compose exec dcs-stats-web bash

# Check container status
docker ps -a | grep dcs-statistics
```

### 🔧 Environment Configuration

The Docker setup uses a `.env` file for configuration. This is created automatically during pre-flight.

**Configuration Options:**
```bash
# docker/.env file
WEB_PORT=9080                        # Web server port
SITE_URL=http://localhost:9080       # Your site URL
PROXY_TYPE=nginx-proxy-manager       # Options: nginx-proxy-manager, simple, none

# NPM Database (if using MySQL instead of SQLite)
NPM_DB_MYSQL_HOST=                   # MySQL host (empty for SQLite)
NPM_DB_MYSQL_PORT=3306               # MySQL port
NPM_DB_MYSQL_USER=                   # MySQL username
NPM_DB_MYSQL_PASSWORD=               # MySQL password
NPM_DB_MYSQL_NAME=                   # MySQL database name
NPM_DISABLE_IPV6=                    # Set to 'true' to disable IPv6
```

**Custom Port Configuration:**
```bash
# Edit docker/.env file
WEB_PORT=8090

# Run launcher - it will use 8090 or find next available
./dcs-docker-manager.sh start  # or dcs-docker-manager.bat start on Windows
```

### 🔍 Troubleshooting Docker Deployment

#### Windows-Specific Issues

**"Invalid pool request" Error:**
- Fixed in this version - network configuration simplified
- Run `dcs-docker-manager.bat pre-flight` to ensure proper setup

**Line Ending Issues (CRLF vs LF):**
```powershell
# Auto-fix all line endings
dcs-docker-manager.bat pre-flight
```

**Permission/Volume Issues:**
- Container automatically detects Windows and adjusts permissions
- No manual intervention needed

**Docker Desktop Not Running:**
```powershell
# The fix script will detect and prompt to start Docker Desktop
dcs-docker-manager.bat pre-flight
```

#### Port Already in Use
The launcher scripts automatically find an available port. If running manually:
```bash
# Check what's using port 9080
# Linux
lsof -i :9080
# Windows PowerShell
Get-NetTCPConnection -LocalPort 9080

# Use a different port
WEB_PORT=8090 docker compose up -d
```

#### Docker Not Found
- **Windows**: Install [Docker Desktop for Windows](https://www.docker.com/products/docker-desktop/) and enable WSL2 backend
- **Linux**: Install Docker Engine and Docker Compose

#### Permission Denied (Linux)
```bash
# Add user to docker group
sudo usermod -aG docker $USER
# Log out and back in, or run
newgrp docker
```

#### Container Won't Start
```bash
# Check logs for errors
docker compose logs

# Windows: Fix common issues first
dcs-docker-manager.bat pre-flight

# Rebuild from scratch
docker compose down
docker compose build --no-cache
docker compose up -d
```

## 🔒 Security Features

### Multi-Layer Protection
✅ **Authentication System** - Secure login with bcrypt password hashing  
✅ **Session Management** - Secure session handling with CSRF tokens  
✅ **Role-Based Access** - Granular permissions for every feature  
✅ **XSS Prevention** - All inputs sanitized and escaped  
✅ **Rate Limiting** - API abuse prevention with throttling  
✅ **Security Headers** - CSP, XSS protection, clickjacking prevention  
✅ **Input Validation** - Comprehensive data filtering  
✅ **Secure File Access** - Protected directories and files  

### Admin Security
- Password strength requirements
- Failed login tracking
- Session timeout
- Activity logging
- IP-based restrictions (optional)

## 🎯 Feature Management

### Granular Control System

Control exactly what your community sees:

```php
// Homepage Features
'home_server_stats' => true,      // Server statistics cards
'home_top_pilots' => true,        // Top 5 pilots chart
'home_top_squadrons' => true,     // Top 3 squadrons chart
'home_mission_stats' => true,     // Combat statistics
'home_player_activity' => true,   // Activity overview

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

## 🔧 Troubleshooting Guide

### 🔍 **Admin Panel Access**
- The admin panel is at `/dcs-stats/site-config/` (NOT `/admin`)
- First user to register becomes the Air Boss
- Default permissions are set during first setup

### 📊 **API Connection Issues**
```bash
# Test API directly
curl http://localhost:8080/ping  # DCSServerBot API endpoint

# Check admin panel
Dashboard → API Configuration → Test Connection

# For Docker users with different proxy setups:
# Nginx Proxy Manager:
  Use http://host.docker.internal:8080 on Windows
  Use http://172.17.0.1:8080 on Linux

# No Proxy (using your own):
  Configure your proxy to forward to dcs-php-secure:9000
  Use FastCGI pass for PHP-FPM
```

### 🎨 **Theme Not Applying**
1. Clear browser cache (Ctrl+F5)
2. Check theme selection saved in admin panel
3. Verify CSS file permissions
4. Check browser console for errors

### 🔄 **Update Failures**
1. Check file permissions on web server
2. Ensure backup directory is writable
3. ```extension=zip``` Enabled in your ```php.ini``` example of a disabled extention is ```;extension=zip```
4. Verify GitHub connectivity
5. Check PHP error logs
6. Manual update via Docker: `docker compose pull && docker compose up -d`

## 📁 Project Structure

```
DCS-Statistics-Dashboard/
├── 📁 dcs-stats/                  # Main web application
│   ├── 📁 site-config/            # Admin panel (NEW!)
│   │   ├── 🔐 index.php          # Admin dashboard
│   │   ├── 🎨 themes.php         # Theme manager
│   │   ├── 🔄 update.php         # Update system
│   │   ├── 💾 backups.php        # Backup management
│   │   ├── 👥 users.php          # User management
│   │   └── 📁 api/               # Admin API endpoints
│   ├── 🏠 index.php              # Homepage 
│   ├── 🏆 leaderboard.php        # Combat rankings
│   ├── 💰 pilot_credits.php      # Credits leaderboard
│   ├── 👨‍✈️ pilot_statistics.php   # Pilot profiles
│   ├── 🛡️ squadrons.php          # Squadron system
│   ├── 🖥️ servers.php            # Server status
│   └── 🎨 themes/                # Theme files
├── 🐳 Dockerfile                 # Production container
├── 🐳 docker-compose.yml         # Docker orchestration
└── 📚 README.md                  # This guide
```

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
