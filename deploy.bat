@echo off
REM SafeGuardX Docker Deployment Script for Windows
REM This script sets up and starts the Docker environment

echo 🚀 SafeGuardX Docker Deployment
echo =================================

REM Check if Docker is running
docker info >nul 2>&1
if %errorlevel% neq 0 (
    echo ❌ Docker is not running. Please start Docker Desktop and try again.
    pause
    exit /b 1
)

REM Check if docker-compose is available
docker-compose --version >nul 2>&1
if %errorlevel% neq 0 (
    echo ❌ docker-compose is not installed. Please install Docker Compose and try again.
    pause
    exit /b 1
)

echo ✅ Docker is running

REM Create .env file if it doesn't exist
if not exist .env (
    echo 📝 Creating .env file from template...
    copy .env.docker .env
    echo ✅ .env file created. Please edit it with your configuration.
)

REM Build and start containers
echo 🏗️  Building and starting containers...
docker-compose up --build -d

REM Wait for containers to be healthy
echo 🔍 Testing Tailscale connection...
tailscale status >nul 2>&1
if %errorlevel% neq 0 (
    echo ⚠️  Tailscale not detected on host. Remote backups may not work.
    echo    Install Tailscale from https://tailscale.com and connect to your network.
) else (
    echo ✅ Tailscale is running on host
    echo 🔍 Testing remote server connectivity...
    ping -n 1 100.81.196.91 >nul 2>&1
    if %errorlevel% equ 0 (
        echo ✅ Remote server is reachable
    ) else (
        echo ❌ Remote server not reachable. Check Tailscale connection.
        echo    Run: tailscale status
        echo    And ensure your remote server is connected to Tailscale.
    )
)

REM Check container status
echo 📊 Container Status:
docker-compose ps

echo.
echo 🎉 Deployment Complete!
echo =======================
echo 🌐 Web Application: http://localhost:8000
echo ⚡ Vite Dev Server:  http://localhost:5173
echo.
echo 📁 C: Drive is mounted at /c inside containers (read-only)
echo 🔍 Test access: docker-compose exec app ls /c/Users
echo.
echo 📝 Useful commands:
echo   docker-compose logs -f     # View logs
echo   docker-compose down        # Stop containers
echo   docker-compose exec app sh # Access container shell
echo.
echo 📚 For more information, see DOCKER_README.md
pause
