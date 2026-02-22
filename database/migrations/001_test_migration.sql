-- ========================================
-- MIGRATION VERIFICATION TEST SCRIPT
-- Version: 001
-- Created: February 22, 2026
-- ========================================
-- 
-- Purpose: Verify that migration 001 was successful
-- Run this after executing 001_perishable_goods_schema.sql
-- 
-- Usage: mysql -u root -p inventory_system < 001_test_migration.sql
-- 
-- ========================================

USE inventory_system;

-- ========================================
-- TEST SUITE: SCHEMA VERIFICATION
-- ========================================

SELECT '========================================' as '';
SELECT 'MIGRATION 001 VERIFICATION TEST SUITE' as '';
SELECT '========================================' as '';
SELECT '' as '';

-- Test 1: Check if new tables exist
SELECT '✓ TEST 1: Verify New Tables Created' as TEST;
SELECT 
    CASE 
        WHEN COUNT(*) = 6 THEN '✓ PASS: All 6 new tables exist'
        ELSE CONCAT('✗ FAIL: Expected 6 tables, found ', COUNT(*))
    END as result
FROM information_schema.tables
WHERE table_schema = 'inventory_system'
AND table_name IN (
    'suppliers', 
    'procurement_orders', 
    'procurement_order_items',
    'inventory_batches',
    'wastage_records',
    'variance_reasons'
);

-- List actual tables found
SELECT table_name, 
       create_time,
       table_rows
FROM information_schema.tables
WHERE table_schema = 'inventory_system'
AND table_name IN (
    'suppliers', 
    'procurement_orders', 
    'procurement_order_items',
    'inventory_batches',
    'wastage_records',
    'variance_reasons'
)
ORDER BY table_name;

SELECT '' as '';

-- Test 2: Check if columns were added to existing tables
SELECT '✓ TEST 2: Verify Column Modifications' as TEST;

-- Check inventory table
SELECT 
    CASE 
        WHEN SUM(
            column_name IN ('total_batches', 'oldest_expiry_date', 'near_expiry_count')
        ) = 3 THEN '✓ PASS: inventory table - 3 columns added'
        ELSE '✗ FAIL: inventory table - missing columns'
    END as result
FROM information_schema.columns
WHERE table_schema = 'inventory_system'
AND table_name = 'inventory';

-- Check requisition_items table
SELECT 
    CASE 
        WHEN SUM(
            column_name IN ('batch_id', 'batch_number', 'expiry_date')
        ) = 3 THEN '✓ PASS: requisition_items table - 3 columns added'
        ELSE '✗ FAIL: requisition_items table - missing columns'
    END as result
FROM information_schema.columns
WHERE table_schema = 'inventory_system'
AND table_name = 'requisition_items';

-- Check stock_movements table
SELECT 
    CASE 
        WHEN SUM(column_name IN ('batch_id', 'wastage_id')) = 2 
        THEN '✓ PASS: stock_movements table - 2 columns added'
        ELSE '✗ FAIL: stock_movements table - missing columns'
    END as result
FROM information_schema.columns
WHERE table_schema = 'inventory_system'
AND table_name = 'stock_movements';

SELECT '' as '';

-- Test 3: Check if views were created
SELECT '✓ TEST 3: Verify Database Views Created' as TEST;
SELECT 
    CASE 
        WHEN COUNT(*) >= 5 THEN '✓ PASS: All views created'
        ELSE CONCAT('✗ FAIL: Expected 5 views, found ', COUNT(*))
    END as result
FROM information_schema.views
WHERE table_schema = 'inventory_system'
AND table_name IN (
    'v_expiring_inventory',
    'v_wastage_summary',
    'v_batch_movements',
    'v_fifo_batch_availability',
    'v_variance_category_summary'
);

-- List views
SELECT table_name as view_name
FROM information_schema.views
WHERE table_schema = 'inventory_system'
AND table_name LIKE 'v_%'
ORDER BY table_name;

SELECT '' as '';

-- Test 4: Check if stored procedures and functions exist
SELECT '✓ TEST 4: Verify Stored Procedures/Functions' as TEST;

-- Check function
SELECT 
    CASE 
        WHEN COUNT(*) = 1 THEN '✓ PASS: get_oldest_batch function exists'
        ELSE '✗ FAIL: get_oldest_batch function not found'
    END as result
FROM information_schema.routines
WHERE routine_schema = 'inventory_system'
AND routine_name = 'get_oldest_batch'
AND routine_type = 'FUNCTION';

-- Check procedures
SELECT 
    CASE 
        WHEN COUNT(*) = 2 THEN '✓ PASS: All stored procedures exist'
        ELSE CONCAT('✗ FAIL: Expected 2 procedures, found ', COUNT(*))
    END as result
FROM information_schema.routines
WHERE routine_schema = 'inventory_system'
AND routine_name IN ('update_inventory_batch_summary', 'auto_expire_batches')
AND routine_type = 'PROCEDURE';

SELECT '' as '';

-- Test 5: Check if triggers were created
SELECT '✓ TEST 5: Verify Triggers Created' as TEST;
SELECT 
    CASE 
        WHEN COUNT(*) >= 3 THEN '✓ PASS: All triggers created'
        ELSE CONCAT('✗ FAIL: Expected 3 triggers, found ', COUNT(*))
    END as result
FROM information_schema.triggers
WHERE trigger_schema = 'inventory_system'
AND trigger_name IN (
    'trg_batch_after_update',
    'trg_batch_after_insert',
    'trg_batch_check_depleted'
);

-- List triggers
SELECT 
    trigger_name,
    event_object_table,
    action_timing,
    event_manipulation
FROM information_schema.triggers
WHERE trigger_schema = 'inventory_system'
ORDER BY trigger_name;

SELECT '' as '';

-- Test 6: Check foreign key constraints
SELECT '✓ TEST 6: Verify Foreign Key Constraints' as TEST;
SELECT 
    table_name,
    constraint_name,
    COUNT(*) as constraint_count
FROM information_schema.table_constraints
WHERE constraint_schema = 'inventory_system'
AND constraint_type = 'FOREIGN KEY'
AND table_name IN (
    'suppliers', 
    'procurement_orders', 
    'procurement_order_items',
    'inventory_batches',
    'wastage_records',
    'variance_reasons'
)
GROUP BY table_name, constraint_name
ORDER BY table_name;

SELECT '' as '';

-- Test 7: Check indexes
SELECT '✓ TEST 7: Verify Indexes Created' as TEST;
SELECT 
    table_name,
    index_name,
    GROUP_CONCAT(column_name ORDER BY seq_in_index) as columns
FROM information_schema.statistics
WHERE table_schema = 'inventory_system'
AND table_name IN ('inventory_batches', 'wastage_records', 'procurement_orders')
AND index_name NOT IN ('PRIMARY')
GROUP BY table_name, index_name
ORDER BY table_name, index_name;

SELECT '' as '';

-- Test 8: Check migration record
SELECT '✓ TEST 8: Verify Migration Record' as TEST;
SELECT 
    CASE 
        WHEN COUNT(*) = 1 THEN '✓ PASS: Migration 001 recorded'
        ELSE '✗ FAIL: Migration record not found'
    END as result
FROM schema_migrations
WHERE version = '001';

SELECT * FROM schema_migrations WHERE version = '001';

SELECT '' as '';

-- ========================================
-- DATA INTEGRITY TESTS
-- ========================================

SELECT '✓ TEST 9: Data Integrity Tests' as TEST;

-- Test that tables are empty and ready for data
SELECT 'suppliers' as table_name, COUNT(*) as row_count FROM suppliers
UNION ALL
SELECT 'procurement_orders', COUNT(*) FROM procurement_orders
UNION ALL
SELECT 'procurement_order_items', COUNT(*) FROM procurement_order_items
UNION ALL
SELECT 'inventory_batches', COUNT(*) FROM inventory_batches
UNION ALL
SELECT 'wastage_records', COUNT(*) FROM wastage_records
UNION ALL
SELECT 'variance_reasons', COUNT(*) FROM variance_reasons;

SELECT '' as '';

-- ========================================
-- SAMPLE DATA INSERT TESTS
-- ========================================

SELECT '✓ TEST 10: Sample Data Insert Tests' as TEST;

-- Insert test supplier
INSERT INTO suppliers (supplier_code, supplier_name, contact_person, phone, email, status)
VALUES ('TEST-SUP-001', 'Test Supplier (DELETE ME)', 'Test Contact', '000-000-0000', 'test@test.com', 'active');

-- Verify insert
SELECT 
    CASE 
        WHEN COUNT(*) = 1 THEN '✓ PASS: Can insert into suppliers table'
        ELSE '✗ FAIL: Cannot insert into suppliers table'
    END as result
FROM suppliers WHERE supplier_code = 'TEST-SUP-001';

-- Clean up test data
DELETE FROM suppliers WHERE supplier_code = 'TEST-SUP-001';

SELECT '' as '';

-- ========================================
-- FUNCTION TEST
-- ========================================

SELECT '✓ TEST 11: Function Execution Test' as TEST;

-- Test get_oldest_batch function (should return NULL if no batches)
SELECT 
    CASE 
        WHEN get_oldest_batch(1, 1) IS NULL THEN '✓ PASS: get_oldest_batch function executes'
        ELSE '✓ PASS: get_oldest_batch function executes and returns batch'
    END as result;

SELECT '' as '';

-- ========================================
-- VIEW TEST
-- ========================================

SELECT '✓ TEST 12: View Query Test' as TEST;

-- Test expiring inventory view
SELECT 
    CASE 
        WHEN COUNT(*) >= 0 THEN '✓ PASS: v_expiring_inventory view is queryable'
        ELSE '✗ FAIL: v_expiring_inventory view error'
    END as result
FROM v_expiring_inventory;

-- Test wastage summary view
SELECT 
    CASE 
        WHEN COUNT(*) >= 0 THEN '✓ PASS: v_wastage_summary view is queryable'
        ELSE '✗ FAIL: v_wastage_summary view error'
    END as result
FROM v_wastage_summary;

SELECT '' as '';

-- ========================================
-- FINAL SUMMARY
-- ========================================

SELECT '========================================' as '';
SELECT 'MIGRATION VERIFICATION SUMMARY' as '';
SELECT '========================================' as '';

SELECT 
    'Migration Version' as item, 
    '001' as value
UNION ALL
SELECT 
    'Migration Name', 
    'Perishable Goods & Wastage Tracking'
UNION ALL
SELECT 
    'Status', 
    CASE 
        WHEN EXISTS (SELECT 1 FROM schema_migrations WHERE version = '001')
        THEN '✓ SUCCESSFULLY APPLIED'
        ELSE '✗ NOT APPLIED'
    END
UNION ALL
SELECT 
    'New Tables', 
    CAST((SELECT COUNT(*) FROM information_schema.tables
          WHERE table_schema = 'inventory_system'
          AND table_name IN ('suppliers', 'procurement_orders', 'procurement_order_items',
                             'inventory_batches', 'wastage_records', 'variance_reasons')) AS CHAR)
UNION ALL
SELECT 
    'New Views', 
    CAST((SELECT COUNT(*) FROM information_schema.views
          WHERE table_schema = 'inventory_system'
          AND table_name IN ('v_expiring_inventory', 'v_wastage_summary', 
                             'v_batch_movements', 'v_fifo_batch_availability',
                             'v_variance_category_summary')) AS CHAR)
UNION ALL
SELECT 
    'Procedures/Functions', 
    CAST((SELECT COUNT(*) FROM information_schema.routines
          WHERE routine_schema = 'inventory_system'
          AND routine_name IN ('get_oldest_batch', 'update_inventory_batch_summary', 
                               'auto_expire_batches')) AS CHAR)
UNION ALL
SELECT 
    'Triggers', 
    CAST((SELECT COUNT(*) FROM information_schema.triggers
          WHERE trigger_schema = 'inventory_system'
          AND trigger_name LIKE 'trg_batch%') AS CHAR)
UNION ALL
SELECT 
    'Applied Date', 
    CAST((SELECT applied_at FROM schema_migrations WHERE version = '001') AS CHAR);

SELECT '' as '';
SELECT '========================================' as '';
SELECT 'All tests completed!' as '';
SELECT 'Review results above for any failures.' as '';
SELECT '========================================' as '';

-- ========================================
-- NEXT STEPS
-- ========================================

SELECT '' as '';
SELECT 'NEXT STEPS:' as '';
SELECT '1. If all tests passed, proceed to data migration' as step;
SELECT '2. Create initial batches for existing inventory' as step;
SELECT '3. Set expiry dates for perishable materials' as step;
SELECT '4. Run: CALL update_all_inventory_summaries()' as step;
SELECT '5. Begin Phase 2: Supplier Module Development' as step;
SELECT '' as '';
