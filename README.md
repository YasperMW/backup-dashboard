# backup-dashboard

A Backup system built with Laravel.

## Table of Contents

- [Features](#features)
- [Setup Instructions](#setup-instructions)
- [Usage Guide](#usage-guide)
  - [Signup & Login](#signup--login)
  - [Dashboard](#dashboard)
  - [Backup Management](#backup-management)
  - [Recovery](#recovery)
  - [Security Settings](#security-settings)
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

- Set your database credentials and mail settings in `.env`.

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

### Security Settings

Accessible from the settings menu.

- **Multi-Factor Authentication**: Enable/disable MFA for your account.
- **Password Settings**: Change your password securely.
- **Session Management**: View active sessions and log out from other devices.

---

