@echo off
:: ============================================
:: STOP DCS STATISTICS DASHBOARD
:: Double-click to stop the application
:: ============================================

title Stopping DCS Statistics Dashboard
color 0E

echo.
echo ============================================
echo    STOPPING DCS STATISTICS DASHBOARD
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
    echo You can start it again by running START-HERE.bat
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