# Use Case Diagram Documentation

This document explains the use case diagrams for the Backup Dashboard System.

## Overview

The Backup Dashboard System is a comprehensive backup management solution with the following key actors:

### Actors

1. **User** - Regular authenticated user who can manage their backups
2. **Admin** - System administrator with additional privileges (inherits all User capabilities)
3. **Backup Agent** - Python-based agent running on client machines
4. **System Scheduler** - Laravel task scheduler for automated operations
5. **Email System** - External email service for notifications

## Diagram Files

Two versions of the use case diagram are provided:

1. **`use-case-diagram.puml`** - Detailed diagram with all 57 use cases
2. **`use-case-diagram-simple.puml`** - Simplified overview diagram

Both diagrams feature:
- **Color-coded actors** for easy identification (User=Blue, Admin=Red, System=Green)
- **Color-coded use cases** to show access levels (Pink=Admin-only, Orange=Shared)
- **Explicit associations** instead of inheritance for clarity
- **Legend** explaining the color scheme
- **Notes** describing each actor's role and capabilities

## How to View the Diagrams

### Option 1: Online PlantUML Editor
1. Visit http://www.plantuml.com/plantuml/uml/
2. Copy the content from either `.puml` file
3. Paste it into the editor to see the rendered diagram

### Option 2: VS Code Extension
1. Install the "PlantUML" extension in VS Code
2. Open the `.puml` file
3. Press `Alt+D` to preview the diagram

### Option 3: Command Line (with PlantUML installed)
```bash
# Install PlantUML (requires Java)
# On Ubuntu/Debian:
sudo apt-get install plantuml

# Generate PNG image
plantuml docs/use-case-diagram.puml

# Generate SVG image
plantuml -tsvg docs/use-case-diagram.puml
```

### Option 4: Online Rendering
You can also view the diagram directly on GitHub if you upload it, or use services like:
- https://www.planttext.com/
- https://plantuml-editor.kkeisuke.com/

## Use Case Categories

### 1. Authentication & Security (UC1-UC9)
- User registration and login
- Email verification with OTP
- Password reset with OTP
- Two-factor authentication (2FA)
- Recovery codes management
- Session management
- Login logs (Admin only)

### 2. User Management (UC10-UC13)
- View all users (Admin)
- Update user roles (Admin)
- Delete users (Admin)
- Manage personal profile

### 3. Backup Configuration (UC14-UC19)
- Configure backup settings (type, compression, retention)
- Manage source directories
- Manage destination directories
- Test remote connections

### 4. Backup Operations (UC20-UC27)
- Start manual backups
- Create and manage schedules
- Execute scheduled backups
- Monitor backup status
- View and filter backup history
- Verify backup integrity
- Check file existence

### 5. Agent Management (UC28-UC35)
- Register backup agents
- Agent heartbeat monitoring
- View registered agents
- Delete agents
- Check agent status
- Task distribution and status updates
- Backup file uploads

### 6. Recovery & Restore (UC36-UC41)
- Browse backup snapshots
- Restore from backups
- Restore from encrypted files
- Generate Docker copy scripts
- Clean restore folders
- Clean local backups

### 7. Encryption Management (UC42-UC46)
- View encryption configuration
- Update encryption settings
- Generate encryption keys
- Activate encryption keys
- Check key rotation status

### 8. Dashboard & Monitoring (UC47-UC53)
- View dashboard statistics
- View backup charts and trends
- View system logs
- Export logs
- Clear logs
- View log details
- Check remote host status

### 9. Notifications (UC54-UC57)
- View notifications
- Mark notifications as read
- Mark all notifications as read
- Send email notifications

## Key Relationships

### Include Relationships
- Login can include Email Verification
- Login can include 2FA Verification
- Manual/Scheduled Backups include Status Monitoring
- Backups include File Upload via Agent
- Restore operations include Integrity Verification
- Enable 2FA includes Recovery Codes generation

### Extend Relationships
- 2FA extends Login (optional)
- Email Verification extends Login (when required)
- Scheduled Backup extends Schedule Creation

### Actor Relationships
- **Admin** has all User capabilities plus administrative functions
- Instead of inheritance (which can be confusing), both User and Admin are shown with explicit associations
- Color coding distinguishes actors and use cases:
  - **Blue**: User actor
  - **Red**: Admin actor  
  - **Green**: System actors
  - **Pink use cases**: Admin-only features
  - **Orange use cases**: Shared features (both User and Admin)

## System Architecture Notes

### Backup Agent
- Python-based application running on client machines
- Communicates with the dashboard via REST API
- Sends heartbeat signals every few minutes
- Executes backup tasks assigned by the dashboard
- Uploads completed backup files

### System Scheduler
- Laravel's built-in task scheduler
- Triggers scheduled backup jobs
- Runs maintenance tasks
- Sends periodic notifications

### Email System
- External SMTP service
- Sends OTP codes for verification
- Sends 2FA codes
- Sends backup completion/failure notifications
- Sends restore notifications

## Security Features

1. **Multi-Factor Authentication (MFA)**
   - TOTP-based 2FA using Google Authenticator
   - Recovery codes for account recovery
   - Optional email-based 2FA codes

2. **Email Verification**
   - OTP-based email verification
   - Time-limited verification codes

3. **Password Security**
   - OTP-based password reset
   - Secure password hashing
   - Password strength requirements

4. **Session Management**
   - View active sessions
   - Remote session termination
   - Session timeout

5. **Encryption**
   - Backup file encryption
   - Key rotation support
   - Secure key storage

## Typical User Workflows

### First-Time User Setup
1. Register Account (UC1)
2. Verify Email (UC3)
3. Login (UC2)
4. Configure Backup Settings (UC14)
5. Add Source Directories (UC15)
6. Add Destination Directories (UC17)
7. Register Backup Agent (UC28)

### Creating a Backup
1. Start Manual Backup (UC20) OR Create Schedule (UC21)
2. Agent Receives Task (UC33)
3. Agent Executes Backup (UC34)
4. Agent Uploads Backup (UC35)
5. Monitor Status (UC23)
6. Receive Notification (UC57)

### Restoring Data
1. Browse Backup Snapshots (UC36)
2. Select Backup to Restore (UC37)
3. Verify Integrity (UC26)
4. Restore Data (UC37/UC38)
5. Generate Docker Copy Script if needed (UC39)
6. Clean Up (UC40/UC41)

### Admin Tasks
1. View All Users (UC10)
2. Update User Roles (UC11)
3. View Login Logs (UC9)
4. Monitor System Logs (UC49)
5. Manage System Settings (UC42-UC46)

## Technology Stack

- **Backend**: Laravel (PHP)
- **Frontend**: Blade templates, Tailwind CSS
- **Database**: SQLite/MySQL
- **Authentication**: Laravel Sanctum
- **Agent**: Python
- **Notifications**: Email (SMTP)
- **Encryption**: OpenSSL

## Related Documentation

- [README.md](../README.md) - Main project documentation
- [DOCKER_README.md](../DOCKER_README.md) - Docker setup guide
- [OTP_PASSWORD_RESET.md](OTP_PASSWORD_RESET.md) - Password reset documentation

## Maintenance

This diagram should be updated when:
- New features are added
- User roles change
- New actors are introduced
- Major workflow changes occur

Last Updated: October 2025
