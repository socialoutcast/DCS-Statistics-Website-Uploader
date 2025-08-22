# INTERNAL SCRIPT - DO NOT RUN DIRECTLY
# This script is automatically called by _internal_docker_ops.ps1
# Running this script directly may cause unexpected behavior

Write-Host ""
Write-Host "======================================="
Write-Host "Proxy Configuration" -ForegroundColor Cyan
Write-Host "======================================="
Write-Host ""
Write-Host "Select your proxy setup:"
Write-Host ""
Write-Host "  1) Nginx Proxy Manager (Recommended)" -ForegroundColor Green
Write-Host "     - Full reverse proxy with web UI"
Write-Host "     - SSL certificate management"
Write-Host "     - Access admin panel on port 81"
Write-Host ""
Write-Host "  2) Simple Nginx" -ForegroundColor Yellow
Write-Host "     - Basic web server"
Write-Host "     - No proxy management features"
Write-Host "     - Good for local development"
Write-Host ""
Write-Host "  3) No Proxy" -ForegroundColor Magenta
Write-Host "     - Skip proxy installation"
Write-Host "     - For users with existing nginx/haproxy"
Write-Host "     - You'll need to configure proxy manually"
Write-Host ""

$proxyChoice = Read-Host "Select option [1-3] (default: 1)"

$PROXY_TYPE = switch ($proxyChoice) {
    "2" { "simple" }
    "3" { "none" }
    default { "nginx-proxy-manager" }
}

# Update .env file
$envContent = Get-Content "./docker/.env" -Raw
$envContent = $envContent -replace "PROXY_TYPE=.*", "PROXY_TYPE=$PROXY_TYPE"
Set-Content "./docker/.env" $envContent

Write-Host ""
Write-Host "[OK] Set proxy type to: $PROXY_TYPE" -ForegroundColor Green

# Update docker-compose symlink based on proxy type
Push-Location docker
if (Test-Path "docker-compose.yml") { Remove-Item "docker-compose.yml" -Force }

switch ($PROXY_TYPE) {
    "simple" {
        New-Item -ItemType SymbolicLink -Path "docker-compose.yml" -Target "docker-compose-no-proxy.yml" -Force | Out-Null
        Write-Host "[OK] Configured for simple nginx (no proxy manager)" -ForegroundColor Green
        Pop-Location
    }
    "none" {
        New-Item -ItemType SymbolicLink -Path "docker-compose.yml" -Target "docker-compose-no-proxy.yml" -Force | Out-Null
        Write-Host "[WARN] No proxy will be installed - configure your own reverse proxy" -ForegroundColor Yellow
        Pop-Location
    }
    default {
        New-Item -ItemType SymbolicLink -Path "docker-compose.yml" -Target "docker-compose-with-proxy.yml" -Force | Out-Null
        Write-Host "[OK] Configured for Nginx Proxy Manager" -ForegroundColor Green
        Pop-Location
        
        # Configure database for NPM
        Write-Host ""
        Write-Host "======================================="
        Write-Host "Database Configuration for Nginx Proxy Manager" -ForegroundColor Cyan
        Write-Host "======================================="
        Write-Host ""
        Write-Host "By default, NPM uses a built-in SQLite database (recommended)."
        Write-Host "You can optionally use an external MySQL/MariaDB database."
        Write-Host ""
        Write-Host "  1) Use built-in SQLite database (Recommended)" -ForegroundColor Green
        Write-Host "  2) Use external MySQL/MariaDB database" -ForegroundColor Yellow
        Write-Host ""
        
        $dbChoice = Read-Host "Select database option [1-2] (default: 1)"
        
        if ($dbChoice -eq "2") {
            Write-Host ""
            Write-Host "[INFO] Configure MySQL/MariaDB connection:" -ForegroundColor Blue
            Write-Host ""
            
            # Database Host
            $dbHost = Read-Host "Database Host (e.g., localhost or IP)"
            if ([string]::IsNullOrEmpty($dbHost)) {
                $dbHost = "localhost"
            }
            
            # Database Port
            $dbPort = Read-Host "Database Port (default: 3306)"
            if ([string]::IsNullOrEmpty($dbPort)) {
                $dbPort = "3306"
            }
            
            # Database Name
            $dbName = Read-Host "Database Name (default: npm)"
            if ([string]::IsNullOrEmpty($dbName)) {
                $dbName = "npm"
            }
            
            # Database User
            $dbUser = Read-Host "Database User (default: npm)"
            if ([string]::IsNullOrEmpty($dbUser)) {
                $dbUser = "npm"
            }
            
            # Database Password
            $dbPass = Read-Host "Database Password" -AsSecureString
            $dbPassText = [Runtime.InteropServices.Marshal]::PtrToStringAuto([Runtime.InteropServices.Marshal]::SecureStringToBSTR($dbPass))
            if ([string]::IsNullOrEmpty($dbPassText)) {
                Write-Host "[WARN] No password set - this is insecure!" -ForegroundColor Yellow
            }
            
            # Append to .env
            Add-Content "./docker/.env" "NPM_DB_MYSQL_HOST=$dbHost"
            Add-Content "./docker/.env" "NPM_DB_MYSQL_PORT=$dbPort"
            Add-Content "./docker/.env" "NPM_DB_MYSQL_NAME=$dbName"
            Add-Content "./docker/.env" "NPM_DB_MYSQL_USER=$dbUser"
            Add-Content "./docker/.env" "NPM_DB_MYSQL_PASSWORD=$dbPassText"
            
            Write-Host "[OK] MySQL/MariaDB configuration saved" -ForegroundColor Green
        }
        else {
            # Use SQLite (default) - ensure env vars are empty
            Add-Content "./docker/.env" "NPM_DB_MYSQL_HOST="
            Add-Content "./docker/.env" "NPM_DB_MYSQL_PORT=3306"
            Add-Content "./docker/.env" "NPM_DB_MYSQL_NAME="
            Add-Content "./docker/.env" "NPM_DB_MYSQL_USER="
            Add-Content "./docker/.env" "NPM_DB_MYSQL_PASSWORD="
            
            Write-Host "[OK] Using built-in SQLite database" -ForegroundColor Green
        }
        
        # Ask about IPv6
        Write-Host ""
        $ipv6Choice = Read-Host "Disable IPv6 support? (y/N)"
        if ($ipv6Choice -match '^[Yy]$') {
            Add-Content "./docker/.env" "NPM_DISABLE_IPV6=true"
            Write-Host "[OK] IPv6 disabled" -ForegroundColor Green
        }
        else {
            Add-Content "./docker/.env" "NPM_DISABLE_IPV6="
            Write-Host "[OK] IPv6 enabled" -ForegroundColor Green
        }
        
        Write-Host ""
        Write-Host "[INFO] Nginx Proxy Manager will be available at:" -ForegroundColor Blue
        Write-Host "  Admin Panel: http://localhost:81" -ForegroundColor Cyan
        Write-Host "  Default login: admin@example.com / changeme" -ForegroundColor Yellow
    }
}

Write-Host ""
Write-Host "[OK] Proxy configuration complete!" -ForegroundColor Green