@echo off
:: ============================================
:: DCS Statistics Dashboard - Shutdown Script
:: Professional service termination for Windows
:: ============================================

title DCS Statistics Dashboard - Shutdown
color 0E

echo.
echo ============================================
echo    DCS Statistics Dashboard - Shutdown
echo ============================================
echo.

echo Stopping the application...
echo.

:: Stop the containers
powershell.exe -ExecutionPolicy Bypass -NoProfile -File "%~dp0docker-start.ps1" stop

if %errorlevel% equ 0 (
    color 0A
    echo.
    echo ============================================
    echo    APPLICATION STOPPED SUCCESSFULLY
    echo ============================================
    echo.
    echo The DCS Statistics Dashboard has been stopped.
    echo You can start it again by running launch.bat
    echo.
) else (
    color 0C
    echo.
    echo ============================================
    echo    FAILED TO STOP APPLICATION
    echo ============================================
    echo.
    echo There was an issue stopping the application.
    echo It may not be running or Docker Desktop is not available.
    echo.
)

pause