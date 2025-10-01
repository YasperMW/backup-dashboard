# backup-dashboard

A Backup system built with Laravel.

## Table of Contents

- [Overview](#overview)
- [Features](#features)
- [Quick Start](#quick-start)
  - [Local Setup](#local-setup)
  - [Docker Setup](#docker-setup)
- [Usage Guide](#usage-guide)
  - [Signup & Login](#signup--login)
  - [Dashboard](#dashboard)
  - [Backup Management](#backup-management)
  - [Recovery](#recovery)
  - [Security Settings](#security-settings)
- [Common Tasks](#common-tasks)
- [Recovery Tips](#recovery-tips)
- [Restore from File (.enc)](#restore-from-file-enc)
- [Restore to host (docker cp)](#restore-to-host-docker-cp)
- [Clean up](#clean-up)
- [Troubleshooting](#troubleshooting)
- [FAQ](#faq)
- [Support](#support)

---

## Features

- Secure, ransomware-proof backup system
- Multi-cloud and local backup support
- Manual and scheduled backups
- Backup history and statistics dashboard
- Multi-factor authentication (MFA)
- Password and session management
- **Recovery page for restoring backups**

---

## Overview

`backup-dashboard` helps you protect your files from loss, corruption, and ransomware by making it simple to back up, schedule, and recover your data. It works with local storage and popular cloud providers, and gives you an easy-to-understand dashboard with history and quick actions.

Who is it for?

- Individuals and small teams who want reliable backups without complex setup.
- Anyone who wants a simple way to recover files if something goes wrong.
- Users who prefer clear status and guidance over deep technical configurations.

---

## Quick Start

Choose one of the options below to get up and running.

### Local Setup

1. Install prerequisites: PHP 8.2+, Composer, Node.js & npm, and a database (SQLite is fine).
2. Clone the repo and install dependencies (see Setup Instructions below).
3. Copy `.env.example` to `.env` and set your app name and database.
4. Create the database and run migrations.
5. Build assets and start the app.

You’ll be able to visit the app at http://localhost:8000.

### Docker Setup

If you prefer Docker, a `docker-compose.yaml` is included with C: drive mounting for backup sources.

#### Quick Start

1. **Automated Deployment**:
   ```bash
   # Linux/macOS
   ./deploy.sh

   # Windows PowerShell
   .\deploy.bat
   ```

2. **Manual Setup**:
   ```bash
   # Copy Docker environment file
   cp .env.docker .env

   # Build and start containers
   docker-compose up --build -d
   ```

3. **Access the Application**:
   - **Web Application**: http://localhost:8000
   - **Vite Development Server**: http://localhost:5173 (for asset hot reloading)

#### C: Drive Integration

The Docker setup includes **read-only mounting of your Windows C: drive** at `/c` inside containers:

```yaml
volumes:
  - /c:/c:ro  # Mount Windows C: drive as read-only
```

This allows you to:
- ✅ **Backup any files** from your Windows system
- ✅ **Access Windows paths** like `/c/Users/YourName/Documents`
- ✅ **Maintain security** with read-only access
- ✅ **Restore files** with automatic Docker commands

#### Backup from Windows Directories

With C: drive mounted, you can backup from common Windows locations:

- **Documents**: `/c/Users/YourName/Documents`
- **Desktop**: `/c/Users/YourName/Desktop`
- **Downloads**: `/c/Users/YourName/Downloads`
- **Any folder**: `/c/path/to/your/important/files`

#### Docker Commands

```bash
# View logs
docker-compose logs -f

# Access container shell
docker-compose exec app sh

# Stop containers
docker-compose down

# Rebuild and restart
docker-compose up --build --force-recreate
```

#### Troubleshooting

- **C: drive not accessible**: Ensure Docker Desktop is configured for WSL2
- **Permission issues**: Files are mounted read-only for security
- **Port conflicts**: Change ports in `docker-compose.yaml` if needed

For detailed Docker instructions, see `DOCKER_README.md`.

---

## Setup Instructions

### 1. Prerequisites

- PHP 8.2 or higher
- Composer
- Node.js & npm
- A supported database (e.g., MySQL, SQLite)
- Git

### 2. Clone the Repository

```bash
git clone <https://github.com/YasperMW/backup-dashboard.git>
cd backup-dashboard
```

### 3. Install PHP Dependencies

```bash
composer install
```

### 4. Install Node.js Dependencies

```bash
npm install
```

### 5. Environment Setup

- Copy the example environment file and configure your settings:

```bash
cp .env.example .env
```

- On Windows (PowerShell):

```powershell
Copy-Item .env.example .env
```

- What is `.env`? It’s a simple text file where the app reads your settings (like app name, URL, database, and email). Open `.env` in any editor and adjust the values.

- Local setup (SQLite example):

```env
APP_NAME="Backup Dashboard"
APP_ENV=local
APP_URL=http://localhost:8000

# Use a lightweight local database
DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite

# Email (use your SMTP for testing)
MAIL_MAILER=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_ENCRYPTION=tls
```

Tip: If using SQLite, create the file `database/database.sqlite` (an empty file) so the app can connect.

- Docker setup (matches `docker-compose.yaml`):

```env
APP_NAME="Backup Dashboard"
APP_ENV=local
APP_URL=http://localhost:8000

# Docker uses the app folder inside the container
DB_CONNECTION=sqlite
DB_DATABASE=/var/www/database/database.sqlite

# Email (set your SMTP provider details)
MAIL_MAILER=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_ENCRYPTION=tls
```

After saving `.env`, continue with the next steps to generate the app key and run migrations.

### 6. Generate Application Key

```bash
php artisan key:generate
```

### 7. Run Migrations

```bash
php artisan migrate
```

### 8. Build Frontend Assets

```bash
npm run build
```
or for development:
```bash
npm run dev
```

### 9. Start the Application

```bash
php artisan serve
```
OR
```bash
composer run dev
```

Visit [http://localhost:8000](http://localhost:8000) in your browser.

---

## Usage Guide

### Signup & Login

- On the welcome page, click **Sign up** to create a new account.
- Fill in your details and verify your email if required.
- After registration, log in with your credentials.
- You can enable Multi-Factor Authentication (MFA) for extra security in your profile or security settings.

### Dashboard

- After login, you are redirected to the **Dashboard**.
- The dashboard displays:
  - Total backups, successful/failed backups, and storage used.
  - Visual charts for backup history, storage usage, backup size trends, and backup type/status distribution.
  - Quick links to create a new backup, view all backups, and access settings.

### Backup Management

Accessible from the navigation menu or quick links.

- **Backup Configuration**: View and edit storage location, backup type, compression, and retention.
- **Backup Schedule**: Create, view, and manage automated backup schedules (daily, weekly, monthly).
- **Manual Backup**: Start a backup immediately, choosing source and destination directories, backup type, and compression.
- **Source/Destination Directories**: Add or remove directories to be backed up or used as backup destinations.
- **Backup History**: View a table of all past backups, including status and details.

### Recovery

- The **Recovery** page allows you to restore data from your previous backups in case of data loss, corruption, or ransomware attacks.
- Access the Recovery page from the navigation menu or directly at `/recovery`.
- On the Recovery page, you can:
  - Browse available backup snapshots.
  - Select a backup to restore.
  - Initiate the restore process to recover your files or system to a previous state.
- The recovery process is designed to be simple and secure, ensuring your data is restored accurately and efficiently.

---

## Common Tasks

- Create a manual backup
  - Go to `Backup Management` > `Manual Backup` and choose what to back up and where to save it.
- Schedule automatic backups
  - In `Backup Schedule`, pick how often (daily/weekly/monthly) and let the app run them for you.
- Check backup history
  - View the latest results, sizes, and statuses in `Backup History` and on the `Dashboard` charts.
- Restore files
  - Open `Recovery`, pick a snapshot, and follow the prompts to bring files back.
- Update security settings
  - Turn on Multi-Factor Authentication (MFA) and review active sessions.

## Recovery Tips

- Pick the backup closest to when things were last OK.
- If you’re unsure, start with a smaller restore to confirm the files look right.
- Keep your destination drive connected and with enough free space.
- If you’re offline, remote backups won’t be available until you reconnect.

## Restore from File (.enc)

- Use this when you have an encrypted backup file on your computer and want to restore it.
- In the app, go to `Recovery` > `Restore From File`.
- Choose the `.enc` file and set a Restore Path.
  - If running in Docker, use a container path (for example, `/tmp/restore_out`).
  - If running directly on your PC, use a normal local path (for example, `C:\Restores`).
- Click `Restore From File`. The app will decrypt and extract the files to the Restore Path.

## Restore to host (docker cp)

- When the app runs in Docker, restored files live inside the container. You can copy them to your PC with `docker cp`.
- On the Recovery page (After Restore section):
  - Enter the container restore path (for example, `/tmp/restore_out`).
  - Enter a Windows destination (for example, `C:\\Users\\Public\\RestoreTarget`).
  - Click “Show docker cp command” or “Download PowerShell script”.
- Example command (PowerShell):

```powershell
docker cp safeguardx:/tmp/restore_out C:\Users\Public\RestoreTarget
```

Tip: You can change the container name via `DOCKER_CONTAINER_NAME` in `.env`. Default is `safeguardx`.

## Clean up

- Clean container restore folder
  - On the Recovery page, click “Clean restore folder” to remove the restore directory in the container (for example, `/tmp/restore_out`) after copying files out.
- Clean local encrypted backups
  - Click “Clean local .enc backups” to delete `.enc` files from the local backups directory (default `/var/www/storage/app/backups`).

## Troubleshooting

- The site won’t open on http://localhost:8000
  - Make sure the app is running. For Docker, check `docker compose ps` and that port 8000 isn’t already in use.
- Frontend looks broken or styles are missing
  - Rebuild assets: `npm run build` (local) or ensure the `vite` service is up (Docker).
- Can’t log in or forgot password
  - Use the “Forgot Password” option on the login page. For OTP-based reset details, see `docs/OTP_PASSWORD_RESET.md`.
- Email isn’t sending
  - Open `.env` and check mail settings. Use a known-good SMTP while testing.
- Remote backups show as not available
  - If you’re offline, remote storage won’t be reachable. Reconnect to see those backups.




### Security Settings

Accessible from the settings menu.

- **Multi-Factor Authentication**: Enable/disable MFA for your account.
- **Password Settings**: Change your password securely.
- **Session Management**: View active sessions and log out from other devices.

---

