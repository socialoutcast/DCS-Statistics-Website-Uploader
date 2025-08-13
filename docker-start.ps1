# DCS Statistics Docker Startup Script for Windows PowerShell
# This script handles port availability checking and automatic port selection

param(
    [Parameter(Position=0)]
    [ValidateSet("start", "stop", "restart", "status", "logs")]
    [string]$Action = "start"
)

# Default configuration
$DefaultPort = 8080
$ContainerName = "dcs-statistics"
$EnvFile = ".env"

# Color functions
function Write-Info { Write-Host "ℹ $($args[0])" -ForegroundColor Blue }
function Write-Success { Write-Host "✓ $($args[0])" -ForegroundColor Green }
function Write-Warning { Write-Host "⚠ $($args[0])" -ForegroundColor Yellow }
function Write-Error { Write-Host "✗ $($args[0])" -ForegroundColor Red }

# Function to check if a port is available
function Test-PortAvailable {
    param([int]$Port)
    
    $tcpListener = $null
    try {
        $tcpListener = New-Object System.Net.Sockets.TcpListener([System.Net.IPAddress]::Any, $Port)
        $tcpListener.Start()
        return $true
    }
    catch {
        return $false
    }
    finally {
        if ($tcpListener) {
            $tcpListener.Stop()
        }
    }
}

# Function to find an available port
function Find-AvailablePort {
    param(
        [int]$StartPort = 8080,
        [int]$MaxAttempts = 100
    )
    
    for ($i = 0; $i -lt $MaxAttempts; $i++) {
        $port = $StartPort + $i
        if (Test-PortAvailable -Port $port) {
            return $port
        }
    }
    return $null
}

# Function to get current port from .env file
function Get-CurrentPort {
    if (Test-Path $EnvFile) {
        $content = Get-Content $EnvFile | Where-Object { $_ -match "^WEB_PORT=" }
        if ($content) {
            return [int]($content -replace "WEB_PORT=", "")
        }
    }
    return $DefaultPort
}

# Function to update .env file with new port
function Update-EnvPort {
    param([int]$Port)
    
    $envContent = @()
    $portSet = $false
    
    if (Test-Path $EnvFile) {
        $envContent = Get-Content $EnvFile | ForEach-Object {
            if ($_ -match "^WEB_PORT=") {
                $portSet = $true
                "WEB_PORT=$Port"
            } else {
                $_
            }
        }
    }
    
    if (-not $portSet) {
        $envContent += "WEB_PORT=$Port"
    }
    
    $envContent | Set-Content $EnvFile
}

# Function to check Docker installation
function Test-DockerInstalled {
    try {
        $null = docker --version 2>&1
        if ($LASTEXITCODE -ne 0) {
            throw "Docker not found"
        }
        
        $null = docker info 2>&1
        if ($LASTEXITCODE -ne 0) {
            throw "Docker daemon not running"
        }
        
        # Check for docker-compose or docker compose
        $null = docker-compose version 2>&1
        if ($LASTEXITCODE -eq 0) {
            $global:ComposeCmd = "docker-compose"
        } else {
            $null = docker compose version 2>&1
            if ($LASTEXITCODE -eq 0) {
                $global:ComposeCmd = "docker compose"
            } else {
                throw "Docker Compose not found"
            }
        }
        
        return $true
    }
    catch {
        Write-Error $_.Exception.Message
        return $false
    }
}

# Function to get local IP addresses
function Get-LocalIPAddresses {
    $addresses = @()
    $adapters = Get-NetIPAddress -AddressFamily IPv4 | Where-Object {
        $_.IPAddress -ne "127.0.0.1" -and 
        $_.PrefixOrigin -ne "WellKnown" -and
        $_.InterfaceAlias -notmatch "Loopback"
    }
    
    foreach ($adapter in $adapters) {
        $addresses += $adapter.IPAddress
    }
    
    return $addresses
}

# Function to get external IP
function Get-ExternalIP {
    try {
        $ip = Invoke-RestMethod -Uri "https://api.ipify.org" -TimeoutSec 5
        return $ip
    }
    catch {
        return $null
    }
}

# Function to display access information
function Show-AccessInfo {
    param([int]$Port)
    
    Write-Host ""
    Write-Host "========================================" -ForegroundColor Green
    Write-Host "DCS Statistics Website is running!" -ForegroundColor Green
    Write-Host "========================================" -ForegroundColor Green
    Write-Host ""
    Write-Host "Access your site at:"
    Write-Host "  Local:      " -NoNewline
    Write-Host "http://localhost:$Port" -ForegroundColor Cyan
    
    # Get local network IPs
    $localIPs = Get-LocalIPAddresses
    if ($localIPs.Count -gt 0) {
        Write-Host "  Network:"
        foreach ($ip in $localIPs) {
            Write-Host "              http://${ip}:$Port" -ForegroundColor Cyan
        }
    }
    
    # Get external IP
    $externalIP = Get-ExternalIP
    if ($externalIP) {
        Write-Host "  External:   " -NoNewline
        Write-Host "http://${externalIP}:$Port" -ForegroundColor Cyan
        Write-Host ""
        Write-Warning "Note: External access requires port forwarding on your router"
    }
    
    Write-Host ""
    Write-Host "Admin Panel: " -NoNewline
    Write-Host "http://localhost:$Port/site-config/install.php" -ForegroundColor Yellow
    Write-Host ""
    Write-Host "To stop the server, run:"
    Write-Host "  .\docker-start.ps1 stop" -ForegroundColor Gray
    Write-Host "  or"
    Write-Host "  $ComposeCmd down" -ForegroundColor Gray
    Write-Host ""
    
    if ($externalIP) {
        Write-Host "========================================" -ForegroundColor Yellow
        Write-Host "Port Forwarding Instructions:" -ForegroundColor Yellow
        Write-Host "========================================" -ForegroundColor Yellow
        Write-Host "If you want external access, configure your router to:"
        Write-Host "  1. Forward external port $Port to internal port $Port"
        Write-Host "  2. Point to this machine's IP address"
        Write-Host ""
        Write-Host "Common router interfaces:"
        Write-Host "  - http://192.168.1.1"
        Write-Host "  - http://192.168.0.1"
        Write-Host "  - http://10.0.0.1"
        Write-Host "========================================"
    }
}

# Main execution
function Start-DCSStatistics {
    Write-Host "========================================"
    Write-Host "DCS Statistics Docker Launcher"
    Write-Host "========================================"
    Write-Host ""
    
    # Check if running as administrator (informational only)
    $isAdmin = ([Security.Principal.WindowsPrincipal] [Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole] "Administrator")
    if ($isAdmin) {
        Write-Info "Running with administrator privileges (not required)"
    }
    
    # Check Docker installation
    Write-Info "Checking Docker installation..."
    if (-not (Test-DockerInstalled)) {
        Write-Error "Please install Docker Desktop for Windows"
        return
    }
    Write-Success "Docker is installed and running"
    
    # Stop existing container if running
    $existingContainer = docker ps -a --format "{{.Names}}" | Where-Object { $_ -eq $ContainerName }
    if ($existingContainer) {
        Write-Info "Stopping existing container..."
        & $ComposeCmd down 2>&1 | Out-Null
    }
    
    # Get desired port
    $desiredPort = Get-CurrentPort
    
    # Check if port is available
    Write-Info "Checking port $desiredPort availability..."
    
    if (Test-PortAvailable -Port $desiredPort) {
        Write-Success "Port $desiredPort is available"
        $selectedPort = $desiredPort
    } else {
        Write-Warning "Port $desiredPort is in use"
        Write-Info "Finding available port..."
        
        $selectedPort = Find-AvailablePort -StartPort $desiredPort
        if (-not $selectedPort) {
            Write-Error "No available ports found in range $desiredPort-$($desiredPort + 100)"
            return
        }
        
        Write-Success "Using port $selectedPort instead"
    }
    
    # Update .env file with selected port
    Update-EnvPort -Port $selectedPort
    
    # Build and start container
    Write-Info "Building Docker image (this may take a few minutes on first run)..."
    
    & $ComposeCmd build --no-cache 2>&1 | Out-Null
    if ($LASTEXITCODE -ne 0) {
        Write-Error "Failed to build Docker image"
        Write-Host "Run '$ComposeCmd build --no-cache' to see detailed error"
        return
    }
    Write-Success "Docker image built successfully"
    
    Write-Info "Starting container..."
    
    & $ComposeCmd up -d 2>&1 | Out-Null
    if ($LASTEXITCODE -ne 0) {
        Write-Error "Failed to start container"
        Write-Host "Run '$ComposeCmd up' to see detailed error"
        return
    }
    Write-Success "Container started successfully"
    
    # Wait for service to be ready
    Write-Info "Waiting for service to be ready..."
    Start-Sleep -Seconds 3
    
    # Check if service is responding
    try {
        $response = Invoke-WebRequest -Uri "http://localhost:$selectedPort/health-check.php" -UseBasicParsing -TimeoutSec 5
        if ($response.StatusCode -eq 200) {
            Write-Success "Service is ready!"
        }
    }
    catch {
        Write-Warning "Service may still be starting up..."
        Write-Host "Check the logs with: .\docker-start.ps1 logs"
    }
    
    Show-AccessInfo -Port $selectedPort
}

# Handle script actions
switch ($Action) {
    "start" {
        Start-DCSStatistics
    }
    "stop" {
        Write-Info "Stopping DCS Statistics..."
        & $ComposeCmd down
        Write-Success "Stopped"
    }
    "restart" {
        Write-Info "Restarting DCS Statistics..."
        & $ComposeCmd down
        Start-DCSStatistics
    }
    "status" {
        $running = docker ps --format "{{.Names}}" | Where-Object { $_ -eq $ContainerName }
        if ($running) {
            $port = Get-CurrentPort
            Write-Success "DCS Statistics is running on port $port"
            Write-Host "Access at: http://localhost:$port"
        } else {
            Write-Info "DCS Statistics is not running"
        }
    }
    "logs" {
        docker logs -f $ContainerName
    }
}