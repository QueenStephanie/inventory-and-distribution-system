-- ========================================
-- PERISHABLE GOODS & WASTAGE TRACKING
-- Database Rollback Script
-- Version: 001
-- Created: February 22, 2026
-- ========================================
-- 
-- Purpose: Rollback the perishable goods enhancement migration
-- 
-- WARNING: This will delete all data in the new tables!
-- Only use this if you need to undo the migration completely.
-- 
-- Usage: mysql -u root -p inventory_system < 001_rollback.sql
-- 
-- ========================================

USE inventory_system;

-- Disable foreign key checks temporarily
SET FOREIGN_KEY_CHECKS = 0;

-- ========================================
-- PART 1: DROP TRIGGERS
-- ========================================

DROP TRIGGER IF EXISTS trg_batch_after_update;
DROP TRIGGER IF EXISTS trg_batch_after_insert;
DROP TRIGGER IF EXISTS trg_batch_check_depleted;

-- ========================================
-- PART 2: DROP STORED PROCEDURES & FUNCTIONS
-- ========================================

DROP FUNCTION IF EXISTS get_oldest_batch;
DROP PROCEDURE IF EXISTS update_inventory_batch_summary;
DROP PROCEDURE IF EXISTS auto_expire_batches;

-- ========================================
-- PART 3: DROP VIEWS
-- ========================================

DROP VIEW IF EXISTS v_expiring_inventory;
DROP VIEW IF EXISTS v_wastage_summary;
DROP VIEW IF EXISTS v_batch_movements;
DROP VIEW IF EXISTS v_fifo_batch_availability;
DROP VIEW IF EXISTS v_variance_category_summary;

-- ========================================
-- PART 4: REVERT TABLE MODIFICATIONS
-- ========================================

-- Remove columns added to stock_movements
ALTER TABLE stock_movements
DROP FOREIGN KEY IF EXISTS fk_movement_batch,
DROP FOREIGN KEY IF EXISTS fk_movement_wastage,
DROP INDEX IF EXISTS idx_movement_batch,
DROP INDEX IF EXISTS idx_movement_wastage,
DROP COLUMN IF EXISTS batch_id,
DROP COLUMN IF EXISTS wastage_id;

-- Remove columns added to requisition_items
ALTER TABLE requisition_items
DROP FOREIGN KEY IF EXISTS fk_requisition_batch,
DROP INDEX IF EXISTS idx_batch_id,
DROP COLUMN IF EXISTS batch_id,
DROP COLUMN IF EXISTS batch_number,
DROP COLUMN IF EXISTS expiry_date;

-- Remove columns added to inventory
ALTER TABLE inventory
DROP INDEX IF EXISTS idx_oldest_expiry,
DROP COLUMN IF EXISTS total_batches,
DROP COLUMN IF EXISTS oldest_expiry_date,
DROP COLUMN IF EXISTS near_expiry_count;

-- ========================================
-- PART 5: DROP NEW TABLES (in reverse dependency order)
-- ========================================

-- Drop tables that depend on others first
DROP TABLE IF EXISTS notification_preferences;
DROP TABLE IF EXISTS variance_reasons;
DROP TABLE IF EXISTS wastage_records;
DROP TABLE IF EXISTS inventory_batches;
DROP TABLE IF EXISTS procurement_order_items;
DROP TABLE IF EXISTS procurement_orders;
DROP TABLE IF EXISTS suppliers;

-- ========================================
-- PART 6: REMOVE MIGRATION RECORD
-- ========================================

DELETE FROM schema_migrations WHERE version = '001';

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- ========================================
-- ROLLBACK COMPLETE
-- ========================================

SELECT 'Rollback completed successfully!' as status,
       'All perishable goods tables and modifications have been removed.' as message,
       NOW() as completed_at;

-- Verify cleanup
SELECT 
    'Verification:' as info,
    'Run the following to ensure tables were removed:' as instructions;

SELECT 
    'SELECT table_name FROM information_schema.tables' as query,
    'WHERE table_schema = ''inventory_system''' as condition,
    'AND table_name IN (''suppliers'', ''procurement_orders'', etc.)' as filter;

-- ========================================
-- POST-ROLLBACK NOTES
-- ========================================
-- 
-- 1. Verify rollback:
--    SHOW TABLES; (verify new tables are gone)
--    DESCRIBE inventory; (verify added columns removed)
--    DESCRIBE requisition_items; (verify added columns removed)
--    DESCRIBE stock_movements; (verify added columns removed)
-- 
-- 2. Data loss:
--    If you had already created data in the new tables,
--    it is now permanently deleted.
-- 
-- 3. Re-applying migration:
--    If you want to re-apply the migration after rollback:
--    mysql -u root -p inventory_system < 001_perishable_goods_schema.sql
-- 
-- 4. Partial rollback:
--    If you only want to remove specific tables/features,
--    manually edit this script before running.
-- 
-- ========================================
