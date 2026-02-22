# 🗄️ Database Migrations

This folder contains database migration scripts for the Inventory and Distribution System.

---

## 📋 Available Migrations

### Migration 001: Perishable Goods & Wastage Tracking

**Status:** ✅ Ready for Deployment  
**Created:** February 22, 2026

**Files:**

- `001_perishable_goods_schema.sql` - Main migration script
- `001_rollback.sql` - Rollback script
- `001_test_migration.sql` - Verification test script
- `MIGRATION_GUIDE.md` - Comprehensive deployment guide

**What it adds:**

- Supplier management (suppliers table)
- Procurement orders (procurement_orders, procurement_order_items tables)
- Batch tracking with FIFO (inventory_batches table)
- Wastage categorization (wastage_records table)
- Variance reason tracking (variance_reasons table)
- Enhanced inventory columns for batch summaries
- 5 database views for reporting
- 3 stored procedures/functions for automation
- 3 triggers for data integrity

---

## 🚀 Quick Start

### Before You Begin

1. **Backup your database:**

   ```bash
   cd "c:\xampp\htdocs\inventory and distribution system\database"
   mysqldump -u root -p inventory_system > backup_$(date +%Y%m%d).sql
   ```

2. **Review the migration guide:**
   - Read `MIGRATION_GUIDE.md` for detailed instructions

### Run Migration 001

**Option 1: Command Line (Recommended)**

```bash
cd "c:\xampp\htdocs\inventory and distribution system\database\migrations"
mysql -u root -p inventory_system < 001_perishable_goods_schema.sql
```

**Option 2: phpMyAdmin**

1. Open phpMyAdmin
2. Select `inventory_system` database
3. Click "SQL" tab
4. Copy contents of `001_perishable_goods_schema.sql`
5. Paste and click "Go"

### Verify Migration

```bash
mysql -u root -p inventory_system < 001_test_migration.sql
```

This will run a comprehensive test suite verifying:

- All tables created
- All columns added
- All views working
- All functions/procedures working
- All triggers active

### If Something Goes Wrong

**Rollback:**

```bash
mysql -u root -p inventory_system < 001_rollback.sql
```

**Or restore from backup:**

```bash
mysql -u root -p inventory_system < backup_20260222.sql
```

---

## 📊 Migration Status

| Version | Description                         | Status     | Applied Date |
| ------- | ----------------------------------- | ---------- | ------------ |
| 001     | Perishable Goods & Wastage Tracking | ⏳ Pending | -            |

---

## 🔧 After Migration

Once migration is successful:

1. **Create initial batches for existing inventory**
   - See `MIGRATION_GUIDE.md` section "Post-Migration: Data Migration"
   - Run the SQL script to create INITIAL batches

2. **Set expiry dates for perishable items**
   - Update expiry_date for chicken, meat, dairy products
   - Use reasonable defaults (e.g., 14 days for chicken)

3. **Update inventory summaries**

   ```sql
   CALL update_all_inventory_summaries();
   ```

4. **Test with sample data**
   - Insert a test supplier
   - Create a test procurement order
   - Verify FIFO function works

5. **Proceed to Phase 2**
   - Begin building supplier management interface
   - Start creating procurement order forms

---

## 📝 Best Practices

### Always:

✅ Backup before running migrations  
✅ Test on development/staging first  
✅ Run verification tests after migration  
✅ Review rollback script before migration  
✅ Document any custom changes

### Never:

❌ Run migrations on production without testing  
❌ Skip the backup step  
❌ Modify migration files after they're applied  
❌ Run migrations during business hours (if possible)

---

## 🆘 Troubleshooting

### Common Issues:

**Issue:** "Table already exists"

- **Solution:** Migration was partially run. Either rollback first or skip that table creation.

**Issue:** "Foreign key constraint fails"

- **Solution:** Ensure all referenced tables exist. Check that InnoDB engine is used.

**Issue:** "Access denied"

- **Solution:** Verify MySQL user has CREATE, ALTER, DROP privileges.

**Issue:** "Syntax error"

- **Solution:** Ensure DELIMITER is properly set for procedures/functions.

---

## 📞 Support

For issues or questions:

1. Check `MIGRATION_GUIDE.md` for detailed troubleshooting
2. Review MySQL error log: `C:\xampp\mysql\data\mysql_error.log`
3. Check application logs
4. Verify prerequisites (MySQL 5.7+, InnoDB engine)

---

## 📈 Future Migrations

When adding new migrations:

1. Increment version number (002, 003, etc.)
2. Create three files:
   - `00X_description.sql` (migration)
   - `00X_rollback.sql` (rollback)
   - `00X_test.sql` (verification)
3. Update this README
4. Create documentation
5. Test thoroughly before production

---

**Last Updated:** February 22, 2026  
**Next Migration:** TBD
