#!/bin/bash
# SafeGuardX Docker Deployment Script
# This script sets up and starts the Docker environment

set -e

echo "🚀 SafeGuardX Docker Deployment"
echo "================================="

# Check if Docker is running
if ! docker info > /dev/null 2>&1; then
    echo "❌ Docker is not running. Please start Docker Desktop and try again."
    exit 1
fi

# Check if docker-compose is available
if ! command -v docker-compose > /dev/null 2>&1; then
    echo "❌ docker-compose is not installed. Please install Docker Compose and try again."
    exit 1
fi

echo "✅ Docker is running"

# Create .env file if it doesn't exist
if [ ! -f .env ]; then
    echo "📝 Creating .env file from template..."
    cp .env.docker .env
    echo "✅ .env file created. Please edit it with your configuration."
fi

# Build and start containers
echo "🏗️  Building and starting containers..."
docker-compose up --build -d

# Wait for containers to be healthy
echo "🔍 Testing Tailscale connection..."
if ! tailscale status > /dev/null 2>&1; then
    echo "⚠️  Tailscale not detected on host. Remote backups may not work."
    echo "   Install Tailscale from https://tailscale.com and connect to your network."
else
    echo "✅ Tailscale is running on host"
    echo "🔍 Testing remote server connectivity..."
    if ping -c 1 100.81.196.91 > /dev/null 2>&1; then
        echo "✅ Remote server is reachable"
    else
        echo "❌ Remote server not reachable. Check Tailscale connection."
        echo "   Run: tailscale status"
        echo "   And ensure your remote server is connected to Tailscale."
    fi
fi

# Check container status
echo "📊 Container Status:"
docker-compose ps

# Show access information
echo ""
echo "🎉 Deployment Complete!"
echo "======================="
echo "🌐 Web Application: http://localhost:8000"
echo "⚡ Vite Dev Server:  http://localhost:5173"
echo ""
echo "📁 C: Drive is mounted at /c inside containers (read-only)"
echo "🔍 Test access: docker-compose exec app ls /c/Users"
echo ""
echo "📝 Useful commands:"
echo "  docker-compose logs -f     # View logs"
echo "  docker-compose down        # Stop containers"
echo "  docker-compose exec app sh # Access container shell"
echo ""
echo "📚 For more information, see DOCKER_README.md"
