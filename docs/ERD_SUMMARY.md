# Database ERD Summary

## Quick Reference

### Database Structure
- **Total Tables:** 19
- **Core Entities:** 2 (users, agents)
- **Backup Entities:** 7
- **Auth/Security Entities:** 4
- **System Tables:** 6

### Entity Breakdown

#### Core (2 tables)
1. **users** - User accounts and authentication
2. **agents** - Backup agent registrations

#### Backup Management (7 tables)
3. **backup_configurations** - Global backup settings
4. **backup_source_directories** - Source paths to backup
5. **backup_destination_directories** - Destination paths
6. **backup_histories** - Completed backup records
7. **backup_jobs** - Active/queued backup tasks
8. **backup_schedules** - Automated backup schedules

#### Authentication & Security (4 tables)
9. **login_logs** - Login attempt audit trail
10. **password_reset_tokens** - Password reset OTPs
11. **personal_access_tokens** - API tokens (Sanctum)
12. **sessions** - Active user sessions

#### System (6 tables)
13. **notifications** - User notifications
14. **jobs** - Queue jobs
15. **failed_jobs** - Failed queue jobs
16. **cache** - Application cache
17. **cache_locks** - Cache locks
18. **migrations** - Migration tracking

## Key Relationships

```
users (1) ----< (N) agents
users (1) ----< (N) backup_source_directories
users (1) ----< (N) backup_destination_directories
users (1) ----< (N) backup_histories
users (1) ----< (N) backup_jobs
users (1) ----< (N) login_logs

agents (1) ----< (N) backup_jobs
```

## Foreign Key Actions

### CASCADE (Delete children when parent deleted)
- users → agents
- users → backup_source_directories
- users → backup_destination_directories
- users → backup_histories

### SET NULL (Keep children, nullify FK)
- agents → backup_jobs
- users → backup_jobs
- users → login_logs

## Normalization Status

✅ **3NF Compliant**
- All tables in Third Normal Form
- No redundant data
- Proper dependency management
- JSON columns used appropriately for complex types

## Files Generated

1. **database-erd.puml** - Detailed ERD with all columns
2. **database-erd-simple.puml** - Simplified relationship diagram
3. **DATABASE_ERD.md** - Complete documentation
4. **ERD_SUMMARY.md** - This quick reference

## Viewing the Diagrams

### Quick Online View
1. Go to https://www.planttext.com/
2. Copy content from `.puml` file
3. Click "Refresh"

### VS Code
1. Install PlantUML extension
2. Open `.puml` file
3. Press `Alt+D`

### Generate Images
```bash
cd docs
plantuml -tsvg database-erd.puml
plantuml -tpng database-erd-simple.puml
```

## Database Statistics (from SQL dump)

- **Users:** 8 records
- **Agents:** 33 records (17 soft-deleted)
- **Backup Histories:** 67 records
- **Backup Jobs:** 1,157 records
- **Login Logs:** 100 records
- **Backup Schedules:** 11 records

## Important Notes

1. **backup_schedules.user_id** is NOT a foreign key (design decision)
2. **Soft deletes** enabled on: agents, backup_jobs
3. **JSON columns** used for: capabilities, options, source_directories
4. **Polymorphic relationships** on: personal_access_tokens, notifications
5. **Unique constraints** on: email, token, paths

## Security Features

- ✅ Password hashing (bcrypt)
- ✅ Token hashing (SHA-256)
- ✅ Two-factor authentication support
- ✅ Email verification with OTP
- ✅ Session management
- ✅ Login audit trail
- ✅ API token authentication

## Performance Optimizations

- Strategic indexes on foreign keys
- Indexes on frequently queried columns (status, email, token)
- Composite indexes for polymorphic relationships
- InnoDB engine for transaction support
- utf8mb4 for full Unicode support

## Next Steps

1. Review the detailed ERD: `database-erd.puml`
2. Read full documentation: `DATABASE_ERD.md`
3. Generate visual diagrams for presentations
4. Update as schema evolves

---

**Generated:** October 2025  
**Database:** safeguard  
**DBMS:** MariaDB 11.8.3
