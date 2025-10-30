# dbdiagram.io Guide

## How to Use

### Option 1: Direct Import (Recommended)

1. Go to https://dbdiagram.io/d
2. Click **"Import"** in the top menu
3. Select **"From DBML"**
4. Copy the entire content from `database-schema.dbml`
5. Paste it into the import dialog
6. Click **"Import"**

### Option 2: Manual Copy

1. Go to https://dbdiagram.io/d
2. Clear the default content
3. Copy the entire content from `database-schema.dbml`
4. Paste it into the editor
5. The diagram will auto-generate

## Features Included

### ✅ All Tables (19 total)
- **Core:** users, agents
- **Backup:** 7 tables
- **Auth:** 4 tables  
- **System:** 6 tables

### ✅ All Foreign Keys
- `agents.user_id` → `users.id` (CASCADE)
- `backup_source_directories.user_id` → `users.id` (CASCADE)
- `backup_destination_directories.user_id` → `users.id` (CASCADE)
- `backup_histories.user_id` → `users.id` (CASCADE)
- `backup_jobs.agent_id` → `agents.id` (SET NULL)
- `backup_jobs.user_id` → `users.id` (SET NULL)
- `login_logs.user_id` → `users.id` (SET NULL)

### ✅ Additional Features
- **Table Groups** - Visual organization by category
- **Indexes** - All unique and performance indexes
- **Enums** - backup_type_enum (full/incremental)
- **Notes** - Detailed descriptions for each table
- **Constraints** - Primary keys, unique constraints, defaults
- **Delete Actions** - CASCADE and SET NULL documented

## Important Additions

### Foreign Keys Added
All foreign key relationships from the SQL schema are included with proper delete actions:

1. **CASCADE deletes** (child deleted when parent deleted):
   - users → agents
   - users → backup_source_directories
   - users → backup_destination_directories
   - users → backup_histories

2. **SET NULL deletes** (FK set to NULL when parent deleted):
   - agents → backup_jobs
   - users → backup_jobs
   - users → login_logs

### Intentionally NOT Foreign Keys
These are documented as NOT being foreign keys (by design):
- `backup_schedules.user_id` - Schedules persist even if user deleted
- `sessions.user_id` - Session management doesn't enforce FK
- Polymorphic relationships (personal_access_tokens, notifications)

## Using dbdiagram.io

### Navigation
- **Zoom:** Mouse wheel or zoom controls
- **Pan:** Click and drag
- **Select:** Click on table
- **Multi-select:** Ctrl/Cmd + Click

### Customization
- **Rearrange tables:** Drag tables to organize layout
- **Color tables:** Right-click table → Change color
- **Export:** File → Export to PNG/PDF/SQL

### Sharing
1. Click **"Save"** (requires free account)
2. Get shareable link
3. Share with team members

### Export Options
- **PNG/PDF:** For documentation
- **SQL:** Generate CREATE TABLE statements
- **DBML:** Export modified schema

## Table Groups

The schema includes 4 visual groups:

1. **core_entities** (Blue)
   - users
   - agents

2. **backup_management** (Green)
   - backup_configurations
   - backup_source_directories
   - backup_destination_directories
   - backup_histories
   - backup_jobs
   - backup_schedules

3. **authentication** (Coral)
   - login_logs
   - password_reset_tokens
   - personal_access_tokens
   - sessions

4. **system_tables** (Yellow)
   - notifications
   - jobs
   - failed_jobs
   - cache
   - cache_locks
   - migrations

## Tips for Best Visualization

1. **Auto-arrange:** Let dbdiagram.io auto-arrange first
2. **Group related tables:** Keep related tables close together
3. **Minimize crossing lines:** Rearrange to reduce relationship line crossings
4. **Use colors:** Color-code tables by category
5. **Hide system tables:** Collapse system_tables group if not needed

## Advanced Features

### Custom Notes
Each table has detailed notes explaining its purpose. Hover over the note icon to view.

### Relationship Labels
All relationships include:
- Delete action (CASCADE or SET NULL)
- Descriptive note

### Index Visualization
All indexes are documented:
- Unique indexes
- Foreign key indexes
- Performance indexes

## Comparison with PlantUML ERD

| Feature | dbdiagram.io | PlantUML |
|---------|-------------|----------|
| Interactive | ✅ Yes | ❌ No |
| Rearrangeable | ✅ Yes | ❌ No |
| Auto-layout | ✅ Yes | ⚠️ Limited |
| Export formats | PNG, PDF, SQL | PNG, SVG, PDF |
| Collaboration | ✅ Yes (cloud) | ❌ No |
| Version control | ⚠️ Cloud-based | ✅ Text files |
| Offline use | ❌ No | ✅ Yes |

## Recommended Workflow

1. **Development:** Use PlantUML (version controlled)
2. **Presentation:** Use dbdiagram.io (interactive, beautiful)
3. **Documentation:** Use both (different audiences)

## Troubleshooting

### Import Fails
- Ensure you copied the entire file
- Check for any special characters
- Try manual paste instead of import

### Relationships Not Showing
- Check that referenced tables exist
- Verify foreign key syntax
- Refresh the page

### Tables Overlapping
- Use auto-arrange feature
- Manually drag tables apart
- Collapse table groups

## Example Queries

Once you have the diagram, you can:

1. **Find all tables a user owns:**
   - Click on `users` table
   - See all outgoing relationships

2. **Trace backup flow:**
   - Follow: users → backup_jobs → agents → backup_histories

3. **Identify orphan tables:**
   - Look for tables with no relationships

## Exporting for Documentation

### For README
```bash
# Export as PNG
File → Export → PNG (High Resolution)
```

### For Presentations
```bash
# Export as PDF
File → Export → PDF (Vector Graphics)
```

### For Database Migration
```bash
# Export as SQL
File → Export → MySQL/PostgreSQL/SQL Server
```

## Keeping Schema Updated

When database changes:

1. Update `database-schema.dbml`
2. Re-import to dbdiagram.io
3. Save new version
4. Export updated diagrams
5. Commit DBML file to git

## Related Files

- `database-schema.dbml` - Source file for dbdiagram.io
- `database-erd.puml` - PlantUML version (detailed)
- `database-erd-simple.puml` - PlantUML version (simplified)
- `DATABASE_ERD.md` - Complete documentation

## Support

- **dbdiagram.io docs:** https://dbdiagram.io/docs
- **DBML syntax:** https://dbml.dbdiagram.io/docs/
- **Community:** https://github.com/holistics/dbml

---

**Pro Tip:** Save your dbdiagram.io project and keep the link in your project README for easy team access!
