# Quick Start: dbdiagram.io

## 3 Steps to Visualize Your Database

### Step 1: Open dbdiagram.io
Go to: **https://dbdiagram.io/d**

### Step 2: Import Schema
1. Click **"Import"** (top menu)
2. Select **"From DBML"**
3. Copy content from `docs/database-schema.dbml`
4. Paste and click **"Import"**

### Step 3: Enjoy!
Your interactive database diagram is ready! 🎉

## What You Get

✅ **19 Tables** - All database entities  
✅ **7 Foreign Keys** - With CASCADE/SET NULL rules  
✅ **4 Table Groups** - Color-coded by category  
✅ **Complete Indexes** - All unique and performance indexes  
✅ **Detailed Notes** - Hover over tables for descriptions  
✅ **Interactive** - Drag, zoom, rearrange as needed  

## Quick Actions

### Rearrange
- **Drag tables** to organize
- **Auto-arrange:** Right-click → Auto arrange

### Export
- **PNG:** File → Export → PNG
- **PDF:** File → Export → PDF
- **SQL:** File → Export → MySQL

### Share
1. Click **"Save"** (free account required)
2. Copy shareable link
3. Share with team

## Key Relationships

```
users (1) ──< (N) agents [CASCADE]
users (1) ──< (N) backup_histories [CASCADE]
users (1) ──< (N) backup_jobs [SET NULL]
agents (1) ──< (N) backup_jobs [SET NULL]
```

## Table Categories

🔵 **Core** - users, agents  
🟢 **Backup** - 7 tables for backup management  
🔴 **Auth** - 4 tables for security  
🟡 **System** - 6 Laravel framework tables  

## Pro Tips

💡 **Zoom:** Mouse wheel  
💡 **Pan:** Click and drag background  
💡 **Select multiple:** Ctrl/Cmd + Click  
💡 **Hide groups:** Click group name to collapse  

## Need Help?

📖 Full guide: `docs/DBDIAGRAM_GUIDE.md`  
📖 Documentation: `docs/DATABASE_ERD.md`  
📖 Summary: `docs/ERD_SUMMARY.md`  

---

**That's it!** Your database is now beautifully visualized and interactive. 🚀
