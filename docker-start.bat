@echo off
setlocal enabledelayedexpansion

REM DCS Statistics Docker Startup Script for Windows
REM This script handles port availability checking and automatic port selection

REM Default configuration
set DEFAULT_PORT=8080
set CONTAINER_NAME=dcs-statistics
set ENV_FILE=.env

REM Color codes (Windows 10+ supports ANSI codes)
set RED=[31m
set GREEN=[32m
set YELLOW=[33m
set BLUE=[34m
set NC=[0m

echo ========================================
echo DCS Statistics Docker Launcher
echo ========================================
echo NOTE: Admin privileges are NOT required
echo ========================================
echo.

REM Check Docker installation
echo Checking Docker installation...
docker --version >nul 2>&1
if %errorlevel% neq 0 (
    echo %RED%Error: Docker is not installed or not in PATH%NC%
    echo Please install Docker Desktop for Windows
    pause
    exit /b 1
)

docker info >nul 2>&1
if %errorlevel% neq 0 (
    echo %RED%Error: Docker daemon is not running%NC%
    echo Please start Docker Desktop
    pause
    exit /b 1
)

echo %GREEN%Docker is installed and running%NC%

REM Check for docker-compose or docker compose
docker-compose version >nul 2>&1
if %errorlevel% equ 0 (
    set COMPOSE_CMD=docker-compose
) else (
    docker compose version >nul 2>&1
    if %errorlevel% equ 0 (
        set COMPOSE_CMD=docker compose
    ) else (
        echo %RED%Error: Docker Compose is not installed%NC%
        pause
        exit /b 1
    )
)

REM Stop existing container if running
docker ps -a --format "{{.Names}}" | findstr /B /E "%CONTAINER_NAME%" >nul 2>&1
if %errorlevel% equ 0 (
    echo Stopping existing container...
    %COMPOSE_CMD% down >nul 2>&1
)

REM Get desired port from .env file or use default
set DESIRED_PORT=%DEFAULT_PORT%
if exist "%ENV_FILE%" (
    for /f "tokens=2 delims==" %%a in ('findstr /B "WEB_PORT=" "%ENV_FILE%"') do (
        set DESIRED_PORT=%%a
    )
)

REM Check if port is available
echo Checking port %DESIRED_PORT% availability...
netstat -an | findstr /C:":%DESIRED_PORT% " | findstr "LISTENING" >nul 2>&1
if %errorlevel% neq 0 (
    echo %GREEN%Port %DESIRED_PORT% is available%NC%
    set SELECTED_PORT=%DESIRED_PORT%
) else (
    echo %YELLOW%Port %DESIRED_PORT% is in use%NC%
    echo Finding available port...
    
    REM Find alternative port
    set SELECTED_PORT=
    for /l %%i in (%DESIRED_PORT%,1,8180) do (
        if not defined SELECTED_PORT (
            netstat -an | findstr /C:":%%i " | findstr "LISTENING" >nul 2>&1
            if !errorlevel! neq 0 (
                set SELECTED_PORT=%%i
                echo %GREEN%Using port %%i instead%NC%
            )
        )
    )
    
    if not defined SELECTED_PORT (
        echo %RED%No available ports found%NC%
        pause
        exit /b 1
    )
)

REM Update .env file with selected port
echo WEB_PORT=%SELECTED_PORT% > "%ENV_FILE%.tmp"
if exist "%ENV_FILE%" (
    findstr /V /B "WEB_PORT=" "%ENV_FILE%" >> "%ENV_FILE%.tmp" 2>nul
)
move /Y "%ENV_FILE%.tmp" "%ENV_FILE%" >nul

REM Build and start container
echo Building Docker image (this may take a few minutes on first run)...
%COMPOSE_CMD% build --no-cache >nul 2>&1
if %errorlevel% neq 0 (
    echo %RED%Failed to build Docker image%NC%
    echo Run '%COMPOSE_CMD% build --no-cache' to see detailed error
    pause
    exit /b 1
)
echo %GREEN%Docker image built successfully%NC%

echo Starting container...
%COMPOSE_CMD% up -d >nul 2>&1
if %errorlevel% neq 0 (
    echo %RED%Failed to start container%NC%
    echo Run '%COMPOSE_CMD% up' to see detailed error
    pause
    exit /b 1
)
echo %GREEN%Container started successfully%NC%

REM Wait for service to be ready
echo Waiting for service to be ready...
timeout /t 3 /nobreak >nul

REM Check if service is responding
curl -s -f -o nul http://localhost:%SELECTED_PORT%/health-check.php 2>nul
if %errorlevel% equ 0 (
    echo %GREEN%Service is ready!%NC%
) else (
    echo %YELLOW%Service may still be starting up...%NC%
)

REM Display access information
echo.
echo ========================================
echo %GREEN%DCS Statistics Website is running!%NC%
echo ========================================
echo.
echo Access your site at:
echo   Local:      http://localhost:%SELECTED_PORT%
echo.

REM Get local IP address
for /f "tokens=2 delims=:" %%a in ('ipconfig ^| findstr /C:"IPv4 Address"') do (
    for /f "tokens=1" %%b in ("%%a") do (
        echo   Network:    http://%%b:%SELECTED_PORT%
    )
)

echo.
echo Admin Panel: http://localhost:%SELECTED_PORT%/site-config/install.php
echo.
echo To stop the server, run:
echo   %COMPOSE_CMD% down
echo.

REM Get external IP (optional)
echo Checking external IP...
for /f %%a in ('curl -s -4 ifconfig.me 2^>nul') do set EXTERNAL_IP=%%a
if defined EXTERNAL_IP (
    echo   External:   http://%EXTERNAL_IP%:%SELECTED_PORT%
    echo.
    echo %YELLOW%Note: External access requires port forwarding on your router%NC%
    echo.
    echo ========================================
    echo Port Forwarding Instructions:
    echo ========================================
    echo If you want external access, configure your router to:
    echo   1. Forward external port %SELECTED_PORT% to internal port %SELECTED_PORT%
    echo   2. Point to this machine's IP address
    echo.
    echo Common router interfaces:
    echo   - http://192.168.1.1
    echo   - http://192.168.0.1
    echo   - http://10.0.0.1
    echo ========================================
)

pause