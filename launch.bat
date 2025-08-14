@echo off
:: ============================================
:: DCS Statistics Dashboard - Launch Script
:: Professional deployment automation for Windows
:: ============================================

title DCS Statistics Dashboard - Launch
color 0A

echo.
echo ============================================
echo    DCS Statistics Dashboard v1.0.0
echo       Professional Deployment System
echo ============================================
echo.

:: Check for Docker Desktop
echo [1/4] Checking for Docker Desktop...
docker --version >nul 2>&1
if %errorlevel% neq 0 (
    color 0C
    echo.
    echo ============================================
    echo    DOCKER DESKTOP NOT FOUND!
    echo ============================================
    echo.
    echo You need Docker Desktop to run this application.
    echo.
    echo Please:
    echo 1. Download Docker Desktop from:
    echo    https://www.docker.com/products/docker-desktop/
    echo.
    echo 2. Install it with default settings
    echo.
    echo 3. Start Docker Desktop
    echo.
    echo 4. Run this file again
    echo.
    echo Press any key to open the Docker download page...
    pause >nul
    start https://www.docker.com/products/docker-desktop/
    exit /b 1
)

:: Check if Docker is running
docker info >nul 2>&1
if %errorlevel% neq 0 (
    color 0E
    echo.
    echo Docker Desktop is installed but not running!
    echo.
    echo Starting Docker Desktop for you...
    
    :: Try to start Docker Desktop
    start "" "C:\Program Files\Docker\Docker\Docker Desktop.exe" 2>nul
    if %errorlevel% neq 0 (
        :: Try alternative path
        start "" "%LOCALAPPDATA%\Docker\Docker Desktop.exe" 2>nul
    )
    
    echo.
    echo Waiting for Docker to start (this takes about 30 seconds)...
    echo.
    
    :: Wait up to 60 seconds for Docker to start
    set count=0
    :wait_docker
    set /a count+=1
    if %count% gtr 12 (
        color 0C
        echo.
        echo Docker Desktop is taking too long to start.
        echo Please make sure Docker Desktop is running and try again.
        echo.
        pause
        exit /b 1
    )
    
    timeout /t 5 /nobreak >nul
    docker info >nul 2>&1
    if %errorlevel% neq 0 (
        echo Still waiting for Docker... (%count%/12)
        goto wait_docker
    )
    
    color 0A
    echo Docker Desktop is now running!
)

echo [OK] Docker Desktop is ready!
echo.

:: Run fixes first (silently if no issues)
echo [2/4] Checking system configuration...
powershell.exe -ExecutionPolicy Bypass -NoProfile -Command "& { $ProgressPreference = 'SilentlyContinue'; & '%~dp0fix-windows-issues.ps1' -Force }" >nul 2>&1
echo [OK] System configured!
echo.

:: Check if .env exists, create if not
echo [3/4] Checking environment settings...
if not exist "%~dp0.env" (
    if exist "%~dp0.env.example" (
        copy "%~dp0.env.example" "%~dp0.env" >nul
        echo [OK] Created configuration file!
    ) else (
        :: Create a basic .env file
        echo WEB_PORT=8080 > "%~dp0.env"
        echo [OK] Created default configuration!
    )
) else (
    echo [OK] Configuration found!
)
echo.

:: Start the application
echo [4/4] Starting DCS Statistics Dashboard...
echo.

:: Run docker-start with automatic port selection
powershell.exe -ExecutionPolicy Bypass -NoProfile -File "%~dp0docker-start.ps1" start

if %errorlevel% equ 0 (
    echo.
    color 0A
    echo ============================================
    echo    SUCCESS! APPLICATION IS RUNNING
    echo ============================================
    echo.
    echo Your DCS Statistics Dashboard is ready!
    echo.
    echo Opening in your browser now...
    timeout /t 3 /nobreak >nul
    
    :: Get the port from .env file
    for /f "tokens=2 delims==" %%a in ('findstr "WEB_PORT" "%~dp0.env"') do set PORT=%%a
    if not defined PORT set PORT=8080
    
    :: Open the browser
    start http://localhost:%PORT%
    
    echo.
    echo Dashboard URL: http://localhost:%PORT%
    echo.
    echo To stop the application: Run shutdown.bat
    echo.
    echo ============================================
    echo.
    pause
) else (
    color 0C
    echo.
    echo ============================================
    echo    STARTUP FAILED
    echo ============================================
    echo.
    echo Something went wrong. Please check the error messages above.
    echo.
    echo Common solutions:
    echo - Make sure no other application is using port 8080
    echo - Try restarting Docker Desktop
    echo - Run as Administrator if permission errors occur
    echo.
    pause
    exit /b 1
)