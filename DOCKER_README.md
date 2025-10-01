# Docker Deployment Guide

## Overview

This application is containerized using Docker and can be deployed with the C: drive mounted as read-only for backup sources. This setup allows you to:

- Run the application in an isolated container environment
- Access Windows C: drive files for backup operations
- Maintain security by mounting the C: drive as read-only
- Develop and test without affecting the host system

## Prerequisites

- Docker Desktop for Windows (with WSL2 backend recommended)
- Windows 10/11 with WSL2 enabled
- **Tailscale installed on Windows host** (for remote server connectivity)
- At least 4GB of available RAM for Docker

## Networking and Tailscale

### Tailscale Configuration

The Docker containers use **host networking mode** to access Tailscale:

```yaml
network_mode: host  # Allows containers to use host's network stack
```

### Prerequisites for Remote Backups

1. **Install Tailscale on Windows Host**:
   - Download from [tailscale.com](https://tailscale.com)
   - Sign up and connect to your Tailscale network
   - Ensure your remote server is also connected to the same Tailscale network

2. **Verify Tailscale Connection**:
   ```bash
   # Check if Tailscale is running
   tailscale status

   # Test connection to your remote server
   ping 100.81.196.91  # Your configured remote server IP
   ```

3. **Container Network Access**:
   - Containers automatically inherit host's network configuration
   - Remote server should be reachable from containers at `100.81.196.91`
   - No additional Tailscale installation needed in containers

### Network Troubleshooting

If remote backups don't work:

1. **Check Tailscale Status**:
   ```bash
   tailscale status
   ```

2. **Test from Host**:
   ```bash
   ping 100.81.196.91
   ssh laravel_user@100.81.196.91
   ```

3. **Test from Container**:
   ```bash
   docker-compose exec app ping 100.81.196.91
   ```

4. **Check Container Networking**:
   ```bash
   docker-compose exec app ip route show
   docker-compose exec app cat /etc/resolv.conf
   ```

### Alternative: Bridge Networking

If host networking causes issues, you can use bridge networking with explicit Tailscale configuration:

```yaml
# In docker-compose.yaml, change to:
network_mode: bridge
# Then add explicit network configuration if needed
```

However, host networking is recommended for Tailscale connectivity as it provides the most direct access to the host's network stack.

### 1. Environment Setup

Copy the Docker-specific environment file:

```bash
cp .env.docker .env
```

### 2. Build and Start Containers

```bash
# Build and start all services
docker-compose up --build

# Or run in detached mode
docker-compose up --build -d
```

### 3. Access the Application

- **Web Application**: http://localhost:8000
- **Vite Development Server**: http://localhost:5173 (for asset hot reloading)

## Configuration

### C: Drive Mount

The C: drive is mounted as read-only at `/c` inside the containers:

```yaml
volumes:
  - /c:/c:ro  # Mount Windows C: drive as read-only
```

This allows the application to:
- Read files from any location on the C: drive
- Perform backup operations on Windows files
- Maintain security by preventing write access to the host

### Common Backup Paths

With the C: drive mounted, you can backup from common Windows locations:

- **Documents**: `/c/Users/YourName/Documents`
- **Desktop**: `/c/Users/YourName/Desktop`
- **Downloads**: `/c/Users/YourName/Downloads`
- **Custom Folders**: `/c/path/to/your/folder`

## Development Workflow

### File Changes

The application directory is mounted as a volume, so changes are reflected immediately:

```bash
# Edit files in your IDE
# Changes will be reflected in the container automatically
```

### Database

The application uses SQLite by default in Docker:

```bash
# Database file location inside container
/var/www/database/database.sqlite
```

### Logs

View application logs:

```bash
# All container logs
docker-compose logs

# Follow logs in real-time
docker-compose logs -f

# Specific service logs
docker-compose logs app
```

## Troubleshooting

### C: Drive Access Issues

If the C: drive mount doesn't work:

1. **Check Docker Desktop Settings**:
   - Ensure "Expose daemon on tcp://localhost:2376" is disabled
   - Enable "Use WSL2 based engine"

2. **Alternative Mount Path**:
   ```yaml
   volumes:
     - /host_mnt/c:/c:ro  # Alternative mount point
   ```

3. **Verify Mount**:
   ```bash
   docker-compose exec app ls /c/Users
   ```

### Permission Issues

If you encounter permission errors:

1. **Check File Permissions**:
   ```bash
   docker-compose exec app ls -la /c/path/to/your/files
   ```

2. **Run as Different User** (if needed):
   ```bash
   docker-compose exec --user root app ls /c/path/to/your/files
   ```

### Port Conflicts

If ports 8000 or 5173 are in use:

```yaml
ports:
  - "8001:80"    # Change web port
  - "5174:5173"  # Change Vite port
```

## Production Deployment

For production deployment:

1. **Use Production Docker Compose**:
   ```bash
   docker-compose -f docker-compose.prod.yml up -d
   ```

2. **Environment Variables**:
   - Set secure encryption keys
   - Configure production database
   - Set proper APP_URL

3. **Security Considerations**:
   - Remove debug mode
   - Use strong passwords
   - Configure HTTPS
   - Set up proper firewall rules

## Backup and Restore in Docker

### Creating Backups

```bash
# Access the container
docker-compose exec app php artisan backup:create

# Or run backup command directly
docker-compose exec app /usr/local/bin/php /var/www/artisan backup:create
```

### Restoring Backups

1. **Restore inside container**:
   ```bash
   docker-compose exec app php artisan backup:restore
   ```

2. **Copy files to host** (see Docker section in web interface):
   ```bash
   # Commands are generated automatically in the web interface
   mkdir -p /home/user/restored-backup
   docker cp safeguardx:/tmp/restore_out/. /home/user/restored-backup/
   ```

## Useful Commands

```bash
# Stop all containers
docker-compose down

# Rebuild containers
docker-compose up --build --force-recreate

# Clean up unused images
docker image prune -f

# Access container shell
docker-compose exec app sh

# View container resource usage
docker stats

# Clean restart
docker-compose down && docker-compose up --build -d
```

## File Structure in Docker

```
/var/www/                 # Application directory
/c/                       # Windows C: drive (read-only)
/var/www/database/        # SQLite database
/var/www/storage/         # File storage
/var/www/bootstrap/cache/ # Cache files
```

## Support

If you encounter issues:

1. Check the logs: `docker-compose logs`
2. Verify C: drive mount: `docker-compose exec app ls /c`
3. Test file access: `docker-compose exec app ls /c/Users`
4. Check container status: `docker-compose ps`
