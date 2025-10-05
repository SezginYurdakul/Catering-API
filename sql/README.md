# 🗄️ Database Management System - ✅ FULLY FUNCTIONAL

This directory contains organized database management commands for the Catering API. The system is now fully operational and tested!

## 📁 File Structure

```
sql/
├── 01_create_database.sql   # Database creation
├── 02_create_tables.sql     # Table creation  
├── 03_seed_tables.sql       # Sample data loading (25 locations, 25 facilities, 7 tags)
├── 04_clear_data.sql        # Data clearing (preserves structure)
├── 05_drop_tables.sql       # Table dropping
├── database_manager.php     # Main management tool ✅
├── catering_db.sql         # Legacy full dump (reference)
└── README.md               # This documentation
```

## 🚀 Usage

### Base Command:
```bash
docker exec catering_api_app php sql/database_manager.php [command]
```

## 📋 Available Commands

### **Individual Operations:**
```bash
# 1. Create database
php database_manager.php create-db

# 2. Create tables
php database_manager.php create-tables

# 3. Load sample data
php database_manager.php seed-data

# 4. Clear data (preserves table structure)
php database_manager.php clear-data

# 5. Drop tables (DANGEROUS!)
php database_manager.php drop-tables
```

### **Combined Operations:**
```bash
# Complete setup (1+2+3) - Fresh installation
php database_manager.php setup

# Data reset (4+3) - Clear and reload data
php database_manager.php reset
```

### **Information:**
```bash
# Database status and table counts
php database_manager.php status
```

## 💡 Usage Scenarios

### **🆕 Fresh Installation:**
Complete setup from scratch (even if database doesn't exist):
```bash
docker exec catering_api_app php sql/database_manager.php setup
```
**What it does:**
- Creates `catering_db` database
- Creates all tables (Locations, Facilities, Tags, Facility_Tags)
- Loads 25 sample locations, 25 facilities, 7 tags with relationships

### **🔄 Development Data Reset:**
Clear existing data and reload fresh samples:
```bash
docker exec catering_api_app php sql/database_manager.php reset
```
**What it does:**
- Preserves table structure
- Clears all existing data
- Reloads fresh sample data
- Resets AUTO_INCREMENT values

### **🧹 Data Cleanup Only:**
Remove all data but keep table structure:
```bash
docker exec catering_api_app php sql/database_manager.php clear-data
```

### **📊 Status Check:**
View current database state:
```bash
docker exec catering_api_app php sql/database_manager.php status
```
**Output example:**
```
📊 Database Status:
┌─────────────────────┬─────────┐
│ Table               │ Count   │
├─────────────────────┼─────────┤
│ Facilities          │      25 │
│ Locations           │      25 │
│ Tags                │       7 │
│ Facility_Tags       │      27 │
└─────────────────────┴─────────┘
```

### **🔥 Complete Reset (DANGEROUS!):**
Full database rebuild:
```bash
# Drop all tables
docker exec catering_api_app php sql/database_manager.php drop-tables
# Rebuild everything
docker exec catering_api_app php sql/database_manager.php setup
```

## 🎯 Sample Data Overview

The system loads comprehensive sample data for testing:

- **25 Locations**: Dutch cities with addresses, postal codes, phone numbers
- **25 Facilities**: Catering venues linked to locations
- **7 Tags**: Category tags (Indoor, Outdoor, Corporate, Wedding, etc.)
- **27 Relationships**: Facility-Tag associations for realistic data

## ⚙️ Technical Features

### **Error Handling:**
- Confirmation prompts for destructive operations
- Detailed error messages with debugging information
- Graceful handling of missing databases/tables

### **Connection Management:**
- Uses project's CustomDb service
- Proper PDO connection handling
- Buffered queries to prevent conflicts

### **SQL Execution:**
- Multi-line statement support
- Comment filtering and SQL cleaning
- Foreign key constraint handling

### **Debug Output:**
- Real-time execution feedback
- Abbreviated SQL statement display
- Clear success/error indicators

## ⚠️ Security & Safety

- **`drop-tables`** command requires confirmation and DELETES ALL TABLES
- **Always backup production data** before running commands
- **Designed for development and testing environments**
- Foreign key constraints are properly handled during operations

## 🧪 Testing Status

All commands have been thoroughly tested:

✅ **create-db**: Works with information_schema connection  
✅ **create-tables**: Proper table creation with constraints  
✅ **seed-data**: Complex multi-line INSERT statements working  
✅ **clear-data**: Data removal with AUTO_INCREMENT reset  
✅ **drop-tables**: Table removal with confirmation  
✅ **setup**: Complete fresh installation from scratch  
✅ **reset**: Data refresh without structure changes  
✅ **status**: Real-time database statistics  

## 🚀 Quick Start

For new developers:
```bash
# Complete setup
docker exec catering_api_app php sql/database_manager.php setup

# Check status
docker exec catering_api_app php sql/database_manager.php status

# During development - reset data
docker exec catering_api_app php sql/database_manager.php reset
```