# 🗄️ Database Migration Guide - Version 001

**Migration:** Perishable Goods & Wastage Tracking Enhancement  
**Version:** 001  
**Date:** February 22, 2026  
**Status:** Ready for deployment

---

## 📋 Overview

This migration adds critical functionality for managing perishable goods in food businesses:

- Supplier management and procurement tracking
- Batch-level inventory with expiry dates
- FIFO (First-In, First-Out) enforcement
- Wastage/spoilage categorization
- Enhanced variance tracking with reasons

---

## ⚠️ Pre-Migration Checklist

### CRITICAL - Complete Before Running Migration:

- [ ] **Backup Database**

  ```bash
  mysqldump -u root -p inventory_system > backup_before_001_$(date +%Y%m%d).sql
  ```

- [ ] **Verify Database Access**

  ```bash
  mysql -u root -p inventory_system -e "SELECT VERSION();"
  ```

- [ ] **Check Current Schema**

  ```bash
  mysql -u root -p inventory_system -e "SHOW TABLES;"
  ```

- [ ] **Verify Disk Space** (at least 500MB free)

  ```bash
  df -h
  ```

- [ ] **Stop Application** (optional but recommended)
  - Stop Apache/web server to prevent conflicts
  - Notify users of maintenance window

- [ ] **Test Database Connection**
  ```bash
  php test_db.php
  ```

---

## 🚀 Migration Execution

### Step 1: Review Migration Script

```bash
cd "c:\xampp\htdocs\inventory and distribution system\database\migrations"
cat 001_perishable_goods_schema.sql
```

**Review checklist:**

- [ ] Understand what tables will be created
- [ ] Understand what columns will be added
- [ ] Review stored procedures and functions
- [ ] Check triggers

### Step 2: Execute Migration

**Option A: Using MySQL Command Line (Recommended)**

```bash
cd "c:\xampp\htdocs\inventory and distribution system\database\migrations"
mysql -u root -p inventory_system < 001_perishable_goods_schema.sql
```

**Option B: Using phpMyAdmin**

1. Open phpMyAdmin in browser
2. Select `inventory_system` database
3. Click "SQL" tab
4. Copy entire contents of `001_perishable_goods_schema.sql`
5. Paste and click "Go"

**Option C: Using MySQL Workbench**

1. Open MySQL Workbench
2. Connect to database
3. File → Open SQL Script
4. Select `001_perishable_goods_schema.sql`
5. Execute (Lightning bolt icon)

### Step 3: Verify Migration Success

Run verification queries:

```sql
-- Check if new tables exist
SHOW TABLES LIKE '%suppliers%';
SHOW TABLES LIKE '%procurement%';
SHOW TABLES LIKE '%wastage%';
SHOW TABLES LIKE '%inventory_batches%';

-- Verify new columns added
DESCRIBE inventory;
DESCRIBE requisition_items;
DESCRIBE stock_movements;

-- Check views created
SHOW FULL TABLES WHERE table_type = 'VIEW';

-- Verify stored procedures
SHOW PROCEDURE STATUS WHERE db = 'inventory_system';

-- Check functions
SHOW FUNCTION STATUS WHERE db = 'inventory_system';

-- Verify migration record
SELECT * FROM schema_migrations WHERE version = '001';
```

**Expected Results:**

- 6 new tables created
- 3 existing tables modified with new columns
- 5 views created
- 3 stored procedures/functions created
- 3 triggers created

### Step 4: Check for Errors

```sql
-- Check MySQL error log
SHOW VARIABLES LIKE 'log_error';

-- Check for any constraint issues
SELECT * FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
WHERE CONSTRAINT_SCHEMA = 'inventory_system'
AND TABLE_NAME IN ('suppliers', 'procurement_orders', 'inventory_batches', 'wastage_records');
```

---

## 📊 Post-Migration: Data Migration for Existing Inventory

**IMPORTANT:** Your existing inventory doesn't have batch information yet. You need to create initial batches.

### Create Initial Batches Script

Run this SQL to create "starting" batches for all existing inventory:

```sql
-- Create initial batches for existing commissary inventory
INSERT INTO inventory_batches (
    branch_id,
    material_id,
    batch_number,
    delivery_date,
    expiry_date,
    initial_quantity,
    current_quantity,
    status
)
SELECT
    i.branch_id,
    i.material_id,
    CONCAT('INITIAL-', DATE_FORMAT(NOW(), '%Y%m%d'), '-', LPAD(i.material_id, 4, '0')) as batch_number,
    CURDATE() as delivery_date,
    NULL as expiry_date, -- Set manually later for perishables
    i.current_quantity as initial_quantity,
    i.current_quantity as current_quantity,
    'active' as status
FROM inventory i
WHERE i.current_quantity > 0;

-- Verify batches created
SELECT
    b.branch_name,
    rm.material_name,
    ib.batch_number,
    ib.current_quantity
FROM inventory_batches ib
JOIN branches b ON ib.branch_id = b.branch_id
JOIN raw_materials rm ON ib.material_id = rm.material_id
ORDER BY b.branch_name, rm.material_name;
```

### Set Expiry Dates for Perishables

```sql
-- Update expiry dates for perishable items (MANUAL - adjust dates as needed)
-- Example: Set 14-day expiry for chicken
UPDATE inventory_batches ib
JOIN raw_materials rm ON ib.material_id = rm.material_id
SET ib.expiry_date = DATE_ADD(ib.delivery_date, INTERVAL 14 DAY)
WHERE rm.category = 'chicken'
  AND ib.batch_number LIKE 'INITIAL-%';

-- Example: Set 60-day expiry for oil
UPDATE inventory_batches ib
JOIN raw_materials rm ON ib.material_id = rm.material_id
SET ib.expiry_date = DATE_ADD(ib.delivery_date, INTERVAL 60 DAY)
WHERE rm.category = 'oil'
  AND ib.batch_number LIKE 'INITIAL-%';

-- Verify expiry dates set
SELECT
    rm.material_name,
    rm.category,
    ib.batch_number,
    ib.delivery_date,
    ib.expiry_date,
    DATEDIFF(ib.expiry_date, CURDATE()) as days_until_expiry
FROM inventory_batches ib
JOIN raw_materials rm ON ib.material_id = rm.material_id
WHERE ib.expiry_date IS NOT NULL
ORDER BY ib.expiry_date;
```

### Update Inventory Summary Fields

```sql
-- Update inventory summary for all materials
CALL update_inventory_batch_summary(1, 1); -- Repeat for each branch/material
-- Or create a loop:

DELIMITER $$
CREATE PROCEDURE update_all_inventory_summaries()
BEGIN
    DECLARE done INT DEFAULT 0;
    DECLARE v_branch_id INT;
    DECLARE v_material_id INT;
    DECLARE cur CURSOR FOR
        SELECT DISTINCT branch_id, material_id FROM inventory;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;

    OPEN cur;
    update_loop: LOOP
        FETCH cur INTO v_branch_id, v_material_id;
        IF done THEN
            LEAVE update_loop;
        END IF;
        CALL update_inventory_batch_summary(v_branch_id, v_material_id);
    END LOOP;
    CLOSE cur;
END$$
DELIMITER ;

-- Run the update
CALL update_all_inventory_summaries();

-- Verify summaries updated
SELECT
    b.branch_name,
    rm.material_name,
    i.current_quantity,
    i.total_batches,
    i.oldest_expiry_date,
    i.near_expiry_count
FROM inventory i
JOIN branches b ON i.branch_id = b.branch_id
JOIN raw_materials rm ON i.material_id = rm.material_id
ORDER BY i.near_expiry_count DESC;
```

---

## 🔧 Troubleshooting

### Common Issues and Solutions

#### Issue 1: Foreign Key Constraint Error

```
Error: Cannot add foreign key constraint
```

**Solution:**

```sql
-- Check if referenced tables exist
SHOW TABLES LIKE 'branches';
SHOW TABLES LIKE 'raw_materials';
SHOW TABLES LIKE 'users';

-- Verify InnoDB engine
SELECT table_name, engine
FROM information_schema.tables
WHERE table_schema = 'inventory_system';
```

#### Issue 2: Duplicate Column Error

```
Error: Duplicate column name 'batch_id'
```

**Solution:** Column already exists. Check if migration was partially run before.

```sql
-- Check existing columns
DESCRIBE inventory;
DESCRIBE requisition_items;

-- If columns exist, skip that ALTER TABLE statement
-- Or run rollback first, then re-run migration
```

#### Issue 3: View Already Exists

```
Error: View 'v_expiring_inventory' already exists
```

**Solution:** Use `CREATE OR REPLACE VIEW` (already in script) or drop manually:

```sql
DROP VIEW IF EXISTS v_expiring_inventory;
```

#### Issue 4: Stored Procedure Syntax Error

```
Error: You have an error in your SQL syntax
```

**Solution:** Ensure DELIMITER is properly set:

```sql
DELIMITER $$
-- procedure code
DELIMITER ;
```

---

## 🔄 Rollback Procedure

If something goes wrong and you need to undo the migration:

### Step 1: Restore from Backup (Safest)

```bash
# Stop application
mysql -u root -p -e "DROP DATABASE inventory_system;"
mysql -u root -p -e "CREATE DATABASE inventory_system;"
mysql -u root -p inventory_system < backup_before_001_20260222.sql
```

### Step 2: Or Use Rollback Script

```bash
cd "c:\xampp\htdocs\inventory and distribution system\database\migrations"
mysql -u root -p inventory_system < 001_rollback.sql
```

### Step 3: Verify Rollback

```sql
-- Verify new tables removed
SHOW TABLES;

-- Verify columns removed
DESCRIBE inventory;
DESCRIBE requisition_items;
DESCRIBE stock_movements;
```

---

## 📊 Performance Optimization

After migration, optimize for performance:

```sql
-- Analyze tables for query optimizer
ANALYZE TABLE suppliers;
ANALYZE TABLE procurement_orders;
ANALYZE TABLE procurement_order_items;
ANALYZE TABLE inventory_batches;
ANALYZE TABLE wastage_records;
ANALYZE TABLE variance_reasons;

-- Check index usage
EXPLAIN SELECT * FROM inventory_batches
WHERE branch_id = 1 AND material_id = 1
ORDER BY expiry_date ASC;

-- Should show "Using index" or "Using where"
```

---

## ✅ Post-Migration Testing

### Test 1: Insert Sample Supplier

```sql
INSERT INTO suppliers (supplier_code, supplier_name, contact_person, phone, email)
VALUES ('SUP001', 'ABC Meat Supplier', 'John Doe', '123-456-7890', 'john@abc.com');

SELECT * FROM suppliers;
```

### Test 2: Create Sample Procurement Order

```sql
-- Get admin user ID
SELECT user_id FROM users WHERE role = 'admin' LIMIT 1;

INSERT INTO procurement_orders (order_number, supplier_id, expected_delivery_date, ordered_by)
VALUES ('PO-2026-001', 1, DATE_ADD(CURDATE(), INTERVAL 3 DAY), 2);

SELECT * FROM procurement_orders;
```

### Test 3: Test FIFO Function

```sql
-- Get commissary branch ID and a material ID
SELECT get_oldest_batch(1, 1);
-- Should return batch_id or NULL if no batches
```

### Test 4: Test Expiring Inventory View

```sql
SELECT * FROM v_expiring_inventory LIMIT 10;
```

### Test 5: Test Wastage Insert

```sql
INSERT INTO wastage_records (
    branch_id, material_id, wastage_date, quantity,
    wastage_category, wastage_reason, recorded_by
)
VALUES (
    1, 1, CURDATE(), 2.5,
    'expired', 'Found chicken past expiry date', 2
);

SELECT * FROM wastage_records;
SELECT * FROM v_wastage_summary;
```

---

## 📈 Monitoring After Deployment

Monitor these metrics for first week:

1. **Query Performance**

   ```sql
   -- Check slow queries
   SHOW VARIABLES LIKE 'slow_query_log';
   SET GLOBAL slow_query_log = 'ON';
   SET GLOBAL long_query_time = 2;
   ```

2. **Table Size Growth**

   ```sql
   SELECT
       table_name,
       ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb
   FROM information_schema.tables
   WHERE table_schema = 'inventory_system'
   ORDER BY (data_length + index_length) DESC;
   ```

3. **Index Efficiency**
   ```sql
   SELECT
       table_name,
       index_name,
       cardinality
   FROM information_schema.statistics
   WHERE table_schema = 'inventory_system'
   AND table_name IN (
       'inventory_batches', 'wastage_records',
       'procurement_orders', 'variance_reasons'
   );
   ```

---

## 📞 Support

If you encounter issues:

1. Check MySQL error log: `C:\xampp\mysql\data\mysql_error.log`
2. Check application logs
3. Review this documentation
4. Check rollback script is ready

---

## ✅ Migration Completion Checklist

- [ ] Backup created and verified
- [ ] Migration script executed successfully
- [ ] All new tables created (6 tables)
- [ ] All columns added (inventory, requisition_items, stock_movements)
- [ ] All views created (5 views)
- [ ] All procedures/functions created (3)
- [ ] All triggers created (3)
- [ ] Initial batches created for existing inventory
- [ ] Expiry dates set for perishable items
- [ ] Inventory summaries updated
- [ ] Sample data tests passed
- [ ] Performance checks completed
- [ ] Application restarted and tested
- [ ] Users notified of new features

---

**Migration Status:** ✅ Complete  
**Next Phase:** Phase 2 - Supplier Module Development  
**Estimated Time for Phase 2:** 5 days

---

**Document Version:** 1.0  
**Last Updated:** February 22, 2026
