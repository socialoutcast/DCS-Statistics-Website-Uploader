# DCS Statistics Admin Panel - Architecture Plan

## Overview
The admin panel will provide a secure interface for managing the DCS Statistics website, including player management, server statistics oversight, and data export capabilities.

## Architecture Design

### Directory Structure
```
dcs-stats/
├── admin/
│   ├── index.php              # Admin dashboard
│   ├── login.php              # Admin login page
│   ├── logout.php             # Logout handler
│   ├── auth.php               # Authentication functions
│   ├── admin_functions.php    # Admin-specific functions
│   ├── admins.php             # Admin user management
│   ├── export.php             # Data export interface
│   ├── logs.php               # Activity logs viewer
│   ├── nav.php                # Navigation template
│   ├── install.php            # Installation script
│   ├── api/                   # Admin API endpoints
│   │   └── export_data.php
│   └── css/
│       └── admin.css          # Admin-specific styles
```

### Security Architecture

#### 1. Authentication System
- **Session-based authentication** with secure session handling
- **Password hashing** using PHP's password_hash() with bcrypt
- **Login throttling** to prevent brute force attacks
- **Remember me** functionality with secure tokens
- **Two-factor authentication** (optional, phase 2)

#### 2. Authorization Levels
```php
// Admin roles
const ADMIN_ROLES = [
    'super_admin' => 3,  // Full access
    'admin' => 2,        // Most features
    'moderator' => 1     // Limited access
];
```

#### 3. Security Measures
- CSRF token protection on all forms
- XSS prevention (already implemented)
- SQL injection prevention (using prepared statements)
- Secure session configuration
- HTTPS enforcement for admin panel
- IP-based access restrictions (optional)

### Features

#### 1. Dashboard (index.php)
- **Overview widgets:**
  - Total players
  - Active players (last 24h/7d/30d)
  - Server status summary
  - Recent admin activities
- **Quick actions:**
  - Search players
  - View recent logs
  - Export data

#### 2. Player Management (players.php)
- **Features:**
  - Search/filter players
  - View detailed player statistics
  - Edit player information
  - Ban/unban players
  - Reset player statistics
  - Add notes to player profiles
- **Bulk operations:**
  - Export selected players
  - Bulk ban/unban

#### 3. Server Management (servers.php)
- **Features:**
  - View all server instances
  - Server uptime statistics
  - Player distribution across servers
  - Server performance metrics

#### 4. Data Export (export.php)
- **Export formats:**
  - CSV
  - JSON
  - Excel (using PhpSpreadsheet)
- **Export options:**
  - All data
  - Date range
  - Specific players
  - Custom queries

#### 5. Activity Logs (logs.php)
- **Log types:**
  - Admin logins
  - Player modifications
  - Data exports
  - System events
- **Features:**
  - Filterable by date, admin, action type
  - Searchable
  - Exportable

#### 6. Settings (settings.php)
- **Admin management:**
  - Add/remove admins
  - Change roles
  - Reset passwords
- **System settings:**
  - API configuration
  - Export settings
  - Security settings

### Database Schema

```sql
-- Admin users table
CREATE TABLE IF NOT EXISTS admin_users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role TINYINT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE,
    remember_token VARCHAR(100) NULL,
    ip_whitelist JSON NULL
);

-- Admin activity logs
CREATE TABLE IF NOT EXISTS admin_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    admin_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    target_type VARCHAR(50) NULL,
    target_id VARCHAR(100) NULL,
    details JSON NULL,
    ip_address VARCHAR(45) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES admin_users(id)
);

-- Player bans
CREATE TABLE IF NOT EXISTS player_bans (
    id INT PRIMARY KEY AUTO_INCREMENT,
    player_ucid VARCHAR(100) NOT NULL,
    admin_id INT NOT NULL,
    reason TEXT NULL,
    banned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (admin_id) REFERENCES admin_users(id)
);

-- Admin sessions (for remember me)
CREATE TABLE IF NOT EXISTS admin_sessions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    admin_id INT NOT NULL,
    token_hash VARCHAR(255) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES admin_users(id)
);
```

### API Integration

The admin panel will integrate with both:
1. **Existing DCSServerBot REST API** (where available)
2. **Local JSON files** (fallback and additional features)

### Implementation Phases

#### Phase 1: Core Foundation
1. Authentication system
2. Basic dashboard
3. Activity logging
4. Admin management

#### Phase 2: Player Management
1. Player search and view
2. Player editing
3. Ban system
4. Bulk operations

#### Phase 3: Advanced Features
1. Server management
2. Data export
3. Advanced statistics
4. API integration

#### Phase 4: Enhancements
1. Two-factor authentication
2. Email notifications
3. Advanced reporting
4. Performance optimization

### Technology Stack
- **Backend:** PHP 7.4+
- **Database:** MySQL/MariaDB (optional, can work with JSON)
- **Frontend:** HTML5, CSS3, Vanilla JavaScript
- **Libraries:**
  - Chart.js (for dashboard graphs)
  - DataTables (for data grids)
  - PhpSpreadsheet (for Excel export, optional)

### Security Checklist
- [ ] Implement secure session handling
- [ ] Add CSRF protection
- [ ] Enforce HTTPS for admin panel
- [ ] Implement login throttling
- [ ] Add activity logging
- [ ] Create secure password reset
- [ ] Add IP whitelisting (optional)
- [ ] Implement role-based access control
- [ ] Add data validation on all inputs
- [ ] Create security headers

### Development Guidelines
1. Follow existing code style
2. Use prepared statements for any database queries
3. Implement comprehensive error handling
4. Add inline documentation
5. Create unit tests for critical functions
6. Follow principle of least privilege
7. Log all administrative actions
8. Validate and sanitize all inputs
9. Use existing security functions where possible
10. Keep admin panel isolated from public pages