# Database ERD Documentation

## Overview

This document describes the Entity-Relationship Diagram (ERD) for the Backup Dashboard system database (`safeguard`).

## Database Information

- **Database Name:** safeguard
- **DBMS:** MariaDB 11.8.3
- **Storage Engine:** InnoDB
- **Character Set:** utf8mb4
- **Collation:** utf8mb4_unicode_ci
- **Normalization Level:** Third Normal Form (3NF)

## ERD Files

Two versions of the ERD are provided:

1. **`database-erd.puml`** - Detailed ERD with all columns and constraints
2. **`database-erd-simple.puml`** - Simplified ERD showing key relationships

## How to View

Use the same methods as the use case diagrams:
- Online: http://www.plantuml.com/plantuml/uml/ or https://www.planttext.com/
- VS Code: Install PlantUML extension and press `Alt+D`
- Command line: `plantuml database-erd.puml`

See `VIEWING_DIAGRAMS.md` for detailed instructions.

## Entity Categories

### Core Entities (Blue)

#### users
**Purpose:** Central entity for user management and authentication

**Key Fields:**
- `id` - Primary key
- `email` - Unique identifier for login
- `role` - User role (user/admin)
- `two_factor_secret` - Encrypted 2FA secret
- `email_verification_code` - OTP for email verification

**Relationships:**
- One-to-Many with `agents`
- One-to-Many with `backup_source_directories`
- One-to-Many with `backup_destination_directories`
- One-to-Many with `backup_histories`
- One-to-Many with `backup_jobs`
- One-to-Many with `login_logs`

**Constraints:**
- `email` must be unique
- Cascade deletes to: agents, backup directories, backup histories

#### agents
**Purpose:** Represents backup agents (Python clients) registered by users

**Key Fields:**
- `id` - Primary key
- `user_id` - Foreign key to users (CASCADE on delete)
- `token` - Unique authentication token (SHA-256)
- `status` - Current status (online/offline)
- `capabilities` - JSON array of agent capabilities
- `last_seen_at` - Last heartbeat timestamp

**Relationships:**
- Many-to-One with `users`
- One-to-Many with `backup_jobs`

**Constraints:**
- `token` must be unique
- Soft deletes enabled (`deleted_at`)
- CASCADE delete when user is deleted

### Backup Entities (Green)

#### backup_configurations
**Purpose:** Global system-wide backup configuration settings

**Key Fields:**
- `id` - Primary key
- `storage_location` - Default storage location
- `backup_type` - Default backup type (full/incremental)
- `compression_level` - Default compression
- `retention_period` - Default retention in days

**Relationships:** None (singleton table)

**Notes:** This is a configuration table with typically one row

#### backup_source_directories
**Purpose:** User-defined directories to be backed up

**Key Fields:**
- `id` - Primary key
- `user_id` - Foreign key to users (CASCADE on delete)
- `path` - Directory path (unique)

**Relationships:**
- Many-to-One with `users`

**Constraints:**
- `path` must be unique across all users
- CASCADE delete when user is deleted

#### backup_destination_directories
**Purpose:** User-defined backup destination directories

**Key Fields:**
- `id` - Primary key
- `user_id` - Foreign key to users (CASCADE on delete)
- `path` - Directory path (unique)

**Relationships:**
- Many-to-One with `users`

**Constraints:**
- `path` must be unique across all users
- CASCADE delete when user is deleted

#### backup_histories
**Purpose:** Immutable record of completed backups

**Key Fields:**
- `id` - Primary key
- `user_id` - Foreign key to users (CASCADE on delete)
- `source_directory` - Source path
- `destination_directory` - Destination path
- `destination_type` - local or remote
- `filename` - Backup file name
- `size` - Backup file size in bytes
- `status` - Backup status (pending/completed/failed)
- `backup_type` - full or incremental
- `integrity_hash` - SHA-256 hash for verification
- `key_version` - Encryption key version used

**Relationships:**
- Many-to-One with `users`

**Constraints:**
- CASCADE delete when user is deleted
- Indexed on `destination_type` and `user_id`

#### backup_jobs
**Purpose:** Tracks backup job execution and progress

**Key Fields:**
- `id` - Primary key
- `agent_id` - Foreign key to agents (SET NULL on delete)
- `user_id` - Foreign key to users (SET NULL on delete)
- `source_path` - Source directory
- `destination_path` - Destination directory
- `backup_type` - ENUM('full', 'incremental')
- `status` - Job status (pending/running/completed/failed)
- `options` - JSON configuration options
- `files_processed` - Number of files processed
- `size_processed` - Total bytes processed

**Relationships:**
- Many-to-One with `agents`
- Many-to-One with `users`

**Constraints:**
- SET NULL on agent delete (job remains for history)
- SET NULL on user delete (job remains for history)
- Soft deletes enabled (`deleted_at`)
- Indexed on `agent_id`, `user_id`, and `status`

#### backup_schedules
**Purpose:** Defines automated backup schedules

**Key Fields:**
- `id` - Primary key
- `user_id` - User who created the schedule (INT, not FK)
- `frequency` - Schedule frequency (daily/weekly/monthly)
- `time` - Time of day to run
- `days_of_week` - For weekly schedules
- `source_directories` - JSON array of source paths
- `destination_directory` - Destination path
- `enabled` - Whether schedule is active

**Relationships:** None (user_id is not a foreign key)

**Notes:** 
- No foreign key constraint on `user_id` (design decision)
- Schedules persist even if user is deleted

### Authentication & Security Entities (Coral)

#### login_logs
**Purpose:** Audit trail of login attempts and sessions

**Key Fields:**
- `id` - Primary key
- `user_id` - Foreign key to users (SET NULL on delete)
- `email` - Email used for login attempt
- `status` - success or failed
- `type` - login or logout
- `ip_address` - Client IP address

**Relationships:**
- Many-to-One with `users`

**Constraints:**
- SET NULL on user delete (preserve audit trail)

#### password_reset_tokens
**Purpose:** Temporary tokens for password reset

**Key Fields:**
- `email` - Primary key
- `token` - Reset token
- `otp` - One-time password (6 digits)
- `otp_expires_at` - OTP expiration timestamp

**Relationships:** None

**Notes:** Email is the primary key (one active reset per email)

#### personal_access_tokens
**Purpose:** API authentication tokens (Laravel Sanctum)

**Key Fields:**
- `id` - Primary key
- `tokenable_type` - Polymorphic type (User or Agent)
- `tokenable_id` - Polymorphic ID
- `token` - Hashed token (unique)
- `name` - Token name/description
- `abilities` - JSON array of permissions

**Relationships:** Polymorphic (can belong to users or agents)

**Constraints:**
- `token` must be unique
- Composite index on `tokenable_type` and `tokenable_id`

#### sessions
**Purpose:** Active user sessions

**Key Fields:**
- `id` - Session ID (primary key)
- `user_id` - Foreign key to users
- `payload` - Serialized session data
- `last_activity` - Unix timestamp of last activity

**Relationships:**
- Many-to-One with `users` (not enforced by FK)

**Constraints:**
- Indexed on `user_id` and `last_activity`

### System Tables (Yellow)

#### notifications
**Purpose:** User notifications (Laravel notifications)

**Key Fields:**
- `id` - UUID (primary key)
- `type` - Notification class name
- `notifiable_type` - Polymorphic type (usually User)
- `notifiable_id` - Polymorphic ID
- `data` - JSON notification data
- `read_at` - Timestamp when read

**Relationships:** Polymorphic

#### jobs
**Purpose:** Queued jobs (Laravel queue)

**Key Fields:**
- `id` - Primary key
- `queue` - Queue name
- `payload` - Serialized job data
- `attempts` - Number of attempts
- `available_at` - When job becomes available

**Relationships:** None

#### failed_jobs
**Purpose:** Failed queue jobs

**Key Fields:**
- `id` - Primary key
- `uuid` - Unique job identifier
- `connection` - Queue connection
- `queue` - Queue name
- `payload` - Serialized job data
- `exception` - Exception details

**Relationships:** None

#### cache & cache_locks
**Purpose:** Application cache storage

**Relationships:** None

#### migrations
**Purpose:** Database migration tracking

**Relationships:** None

## Foreign Key Constraints

### CASCADE Deletes
When the parent record is deleted, child records are automatically deleted:

- `users` → `agents`
- `users` → `backup_source_directories`
- `users` → `backup_destination_directories`
- `users` → `backup_histories`

### SET NULL Deletes
When the parent record is deleted, the foreign key is set to NULL:

- `agents` → `backup_jobs` (agent_id)
- `users` → `backup_jobs` (user_id)
- `users` → `login_logs` (user_id)

## Indexes

### Unique Indexes
- `users.email`
- `agents.token`
- `backup_source_directories.path`
- `backup_destination_directories.path`
- `personal_access_tokens.token`
- `failed_jobs.uuid`

### Performance Indexes
- `agents.user_id`
- `backup_histories.user_id`
- `backup_histories.destination_type`
- `backup_jobs.agent_id`
- `backup_jobs.user_id`
- `backup_jobs.status`
- `login_logs.user_id`
- `sessions.user_id`
- `sessions.last_activity`
- `jobs.queue`
- `notifications.notifiable_type`

## Normalization Analysis

### First Normal Form (1NF)
✅ All tables have:
- Atomic values (no repeating groups)
- Primary keys
- No duplicate rows

### Second Normal Form (2NF)
✅ All tables satisfy 1NF and:
- All non-key attributes depend on the entire primary key
- No partial dependencies

### Third Normal Form (3NF)
✅ All tables satisfy 2NF and:
- No transitive dependencies
- All non-key attributes depend only on the primary key

**Note:** JSON columns (`capabilities`, `options`, `source_directories`) are acceptable in 3NF as they represent complex data types that would otherwise require multiple tables.

## Data Types

### Common Patterns
- **IDs:** `BIGINT(20) UNSIGNED` with AUTO_INCREMENT
- **Strings:** `VARCHAR(191)` (191 for utf8mb4 index compatibility)
- **Long Text:** `TEXT` or `LONGTEXT`
- **Timestamps:** `TIMESTAMP NULL DEFAULT NULL`
- **Booleans:** `TINYINT(1)`
- **JSON:** `LONGTEXT` with `CHECK (json_valid())`

## Soft Deletes

Tables with soft delete support (`deleted_at` column):
- `agents`
- `backup_jobs`

These records are not physically deleted but marked as deleted.

## Security Considerations

1. **Password Storage:** Hashed using bcrypt (`$2y$12$...`)
2. **Tokens:** SHA-256 hashed (64 characters)
3. **Two-Factor Secrets:** Encrypted at application level
4. **Sensitive Data:** Encrypted before storage (2FA secrets, recovery codes)

## Performance Considerations

1. **Indexes:** Strategic indexes on foreign keys and frequently queried columns
2. **JSON Columns:** Used sparingly for flexible data structures
3. **Soft Deletes:** Allows data recovery but requires filtering in queries
4. **Cascade Rules:** Automatic cleanup reduces orphaned records

## Migration History

The database was created through Laravel migrations (batch 1):
- User authentication and authorization
- Backup management system
- Agent registration and tracking
- Logging and auditing
- Queue and cache systems

## Related Documentation

- [Use Case Diagram](USE_CASE_DIAGRAM.md) - System functionality
- [README.md](../README.md) - Project overview
- [Models](../app/Models/) - Laravel Eloquent models

## Maintenance

### Regular Tasks
1. Clean old `sessions` records
2. Archive old `login_logs`
3. Clean `cache` and `cache_locks`
4. Monitor `failed_jobs` for issues
5. Verify `backup_histories` integrity hashes

### Backup Strategy
1. Daily full database backups
2. Point-in-time recovery enabled
3. Backup retention: 30 days minimum
4. Test restore procedures monthly

Last Updated: October 2025
