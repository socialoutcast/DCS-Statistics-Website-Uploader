@echo off
:: Windows Docker Issues Fix - Batch Wrapper
:: This file automatically handles PowerShell execution policy
:: Users can double-click this file or run it from command prompt

echo ========================================
echo Windows Docker Issues Fix Script
echo ========================================
echo.
echo This script will:
echo - Fix line endings for Docker files
echo - Check Docker Desktop status
echo - Create required directories
echo - Set up .env file if missing
echo - Clean up Docker networks
echo.

:: Check if PowerShell is available
where powershell >nul 2>&1
if %errorlevel% neq 0 (
    echo ERROR: PowerShell is not installed or not in PATH
    echo Please install PowerShell to continue
    pause
    exit /b 1
)

:: Run the PowerShell script with bypassed execution policy
echo Running diagnostics and fixes...
echo.

powershell.exe -ExecutionPolicy Bypass -NoProfile -File "%~dp0fix-windows-issues.ps1" %*

:: Check if the script ran successfully
if %errorlevel% neq 0 (
    echo.
    echo ERROR: Fix script encountered an error. See messages above.
    pause
    exit /b %errorlevel%
)

echo.
echo Ready to start Docker? Run docker-start.bat
echo.
pause