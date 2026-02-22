-- ========================================
-- PERISHABLE GOODS & WASTAGE TRACKING
-- Database Migration Script
-- Version: 001
-- Created: February 22, 2026
-- ========================================
-- 
-- Purpose: Add supplier management, batch tracking, FIFO capability,
--          wastage categorization, and enhanced variance tracking
-- 
-- CRITICAL: Backup your database before running this script!
-- 
-- Usage: mysql -u root -p inventory_system < 001_perishable_goods_schema.sql
-- Rollback: mysql -u root -p inventory_system < 001_rollback.sql
-- ========================================

USE inventory_system;

-- ========================================
-- PART 1: NEW TABLES
-- ========================================

-- Table: suppliers
-- Stores supplier information for procurement tracking
CREATE TABLE suppliers (
    supplier_id INT PRIMARY KEY AUTO_INCREMENT,
    supplier_code VARCHAR(50) NOT NULL UNIQUE,
    supplier_name VARCHAR(100) NOT NULL,
    contact_person VARCHAR(100),
    phone VARCHAR(20),
    email VARCHAR(100),
    address TEXT,
    payment_terms VARCHAR(100),
    status ENUM('active', 'inactive') DEFAULT 'active',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_supplier_code (supplier_code),
    INDEX idx_supplier_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Supplier master data for procurement orders';

-- Table: procurement_orders
-- Tracks purchase orders from suppliers (inbound to commissary)
CREATE TABLE procurement_orders (
    order_id INT PRIMARY KEY AUTO_INCREMENT,
    order_number VARCHAR(50) NOT NULL UNIQUE,
    supplier_id INT NOT NULL,
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expected_delivery_date DATE,
    actual_delivery_date DATE NULL,
    status ENUM('pending', 'partial', 'received', 'cancelled') DEFAULT 'pending',
    ordered_by INT NOT NULL,
    received_by INT NULL,
    notes TEXT,
    total_cost DECIMAL(12,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(supplier_id) ON DELETE RESTRICT,
    FOREIGN KEY (ordered_by) REFERENCES users(user_id) ON DELETE RESTRICT,
    FOREIGN KEY (received_by) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_order_number (order_number),
    INDEX idx_order_status (status),
    INDEX idx_order_date (order_date),
    INDEX idx_supplier_id (supplier_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Purchase orders placed with suppliers';

-- Table: procurement_order_items
-- Line items for each procurement order
CREATE TABLE procurement_order_items (
    item_id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    material_id INT NOT NULL,
    ordered_quantity DECIMAL(10,2) NOT NULL,
    received_quantity DECIMAL(10,2) DEFAULT 0,
    unit_cost DECIMAL(10,2),
    total_cost DECIMAL(12,2) GENERATED ALWAYS AS (ordered_quantity * unit_cost) STORED,
    batch_number VARCHAR(100),
    manufacturing_date DATE,
    expiry_date DATE,
    notes TEXT,
    FOREIGN KEY (order_id) REFERENCES procurement_orders(order_id) ON DELETE CASCADE,
    FOREIGN KEY (material_id) REFERENCES raw_materials(material_id) ON DELETE RESTRICT,
    INDEX idx_order_id (order_id),
    INDEX idx_material_id (material_id),
    INDEX idx_expiry_date (expiry_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Individual items within procurement orders';

-- Table: inventory_batches
-- Tracks inventory at batch level for FIFO management
-- CRITICAL TABLE: Enables tracking of specific batches with expiry dates
CREATE TABLE inventory_batches (
    batch_id INT PRIMARY KEY AUTO_INCREMENT,
    branch_id INT NOT NULL,
    material_id INT NOT NULL,
    batch_number VARCHAR(100) NOT NULL,
    delivery_date DATE NOT NULL,
    expiry_date DATE,
    manufacturing_date DATE,
    initial_quantity DECIMAL(10,2) NOT NULL,
    current_quantity DECIMAL(10,2) NOT NULL,
    unit_cost DECIMAL(10,2),
    supplier_id INT,
    procurement_order_id INT NULL,
    status ENUM('active', 'depleted', 'expired', 'transferred') DEFAULT 'active',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (branch_id) REFERENCES branches(branch_id) ON DELETE CASCADE,
    FOREIGN KEY (material_id) REFERENCES raw_materials(material_id) ON DELETE CASCADE,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(supplier_id) ON DELETE SET NULL,
    FOREIGN KEY (procurement_order_id) REFERENCES procurement_orders(order_id) ON DELETE SET NULL,
    UNIQUE KEY unique_batch (branch_id, material_id, batch_number, delivery_date),
    INDEX idx_batch_number (batch_number),
    INDEX idx_expiry_date (expiry_date),
    INDEX idx_status (status),
    INDEX idx_branch_material (branch_id, material_id),
    INDEX idx_fifo_lookup (branch_id, material_id, expiry_date, current_quantity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Batch-level inventory tracking for FIFO and expiry management';

-- Table: wastage_records
-- CRITICAL TABLE: Tracks all inventory losses with categorization
-- Enables businesses to understand WHY inventory is missing
CREATE TABLE wastage_records (
    wastage_id INT PRIMARY KEY AUTO_INCREMENT,
    branch_id INT NOT NULL,
    material_id INT NOT NULL,
    batch_id INT NULL,
    wastage_date DATE NOT NULL,
    quantity DECIMAL(10,2) NOT NULL,
    wastage_category ENUM('expired', 'damaged', 'spoiled', 'staff_meal', 'other') NOT NULL,
    wastage_reason TEXT NOT NULL,
    estimated_cost DECIMAL(10,2),
    recorded_by INT NOT NULL,
    photo_path VARCHAR(255),
    approval_status ENUM('pending', 'approved', 'rejected') DEFAULT 'approved',
    approved_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (branch_id) REFERENCES branches(branch_id) ON DELETE CASCADE,
    FOREIGN KEY (material_id) REFERENCES raw_materials(material_id) ON DELETE CASCADE,
    FOREIGN KEY (batch_id) REFERENCES inventory_batches(batch_id) ON DELETE SET NULL,
    FOREIGN KEY (recorded_by) REFERENCES users(user_id) ON DELETE RESTRICT,
    FOREIGN KEY (approved_by) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_wastage_date (wastage_date),
    INDEX idx_wastage_category (wastage_category),
    INDEX idx_branch_date (branch_id, wastage_date),
    INDEX idx_approval_status (approval_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Detailed wastage/spoilage records with categorization';

-- Table: variance_reasons
-- Links variance from physical counts to specific reasons/categories
-- Enables breakdown of why inventory doesn't match expected levels
CREATE TABLE variance_reasons (
    reason_id INT PRIMARY KEY AUTO_INCREMENT,
    count_id INT NOT NULL,
    material_id INT NOT NULL,
    variance_category ENUM('sales', 'expired', 'damaged', 'theft', 'error', 'other') NOT NULL,
    quantity DECIMAL(10,2) NOT NULL,
    explanation TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (count_id) REFERENCES physical_stock_counts(count_id) ON DELETE CASCADE,
    FOREIGN KEY (material_id) REFERENCES raw_materials(material_id) ON DELETE CASCADE,
    INDEX idx_count_id (count_id),
    INDEX idx_category (variance_category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Categorized reasons for inventory variances';

-- ========================================
-- PART 2: TABLE MODIFICATIONS
-- ========================================

-- Modify inventory table to track batch-related summary data
ALTER TABLE inventory
ADD COLUMN total_batches INT DEFAULT 0 COMMENT 'Count of active batches for this material',
ADD COLUMN oldest_expiry_date DATE NULL COMMENT 'Expiry date of oldest batch (for alerts)',
ADD COLUMN near_expiry_count INT DEFAULT 0 COMMENT 'Count of batches expiring within 7 days',
ADD INDEX idx_oldest_expiry (oldest_expiry_date);

-- Modify requisition_items to track which batch was dispatched
ALTER TABLE requisition_items
ADD COLUMN batch_id INT NULL COMMENT 'Specific batch dispatched (FIFO tracking)',
ADD COLUMN batch_number VARCHAR(100) NULL COMMENT 'Batch number for reference',
ADD COLUMN expiry_date DATE NULL COMMENT 'Expiry date of dispatched batch',
ADD CONSTRAINT fk_requisition_batch FOREIGN KEY (batch_id) REFERENCES inventory_batches(batch_id) ON DELETE SET NULL,
ADD INDEX idx_batch_id (batch_id);

-- Modify stock_movements to link movements to batches and wastage
ALTER TABLE stock_movements
ADD COLUMN batch_id INT NULL COMMENT 'Batch involved in this movement',
ADD COLUMN wastage_id INT NULL COMMENT 'Wastage record if movement is a loss',
ADD CONSTRAINT fk_movement_batch FOREIGN KEY (batch_id) REFERENCES inventory_batches(batch_id) ON DELETE SET NULL,
ADD CONSTRAINT fk_movement_wastage FOREIGN KEY (wastage_id) REFERENCES wastage_records(wastage_id) ON DELETE SET NULL,
ADD INDEX idx_movement_batch (batch_id),
ADD INDEX idx_movement_wastage (wastage_id);

-- ========================================
-- PART 3: DATABASE VIEWS
-- ========================================

-- View: Inventory items expiring soon (FIFO alert view)
CREATE OR REPLACE VIEW v_expiring_inventory AS
SELECT 
    b.branch_id,
    b.branch_name,
    rm.material_id,
    rm.material_code,
    rm.material_name,
    rm.category,
    ib.batch_id,
    ib.batch_number,
    ib.expiry_date,
    ib.current_quantity,
    ib.unit_cost,
    (ib.current_quantity * ib.unit_cost) as value_at_risk,
    DATEDIFF(ib.expiry_date, CURDATE()) as days_until_expiry,
    CASE 
        WHEN ib.expiry_date < CURDATE() THEN 'EXPIRED'
        WHEN DATEDIFF(ib.expiry_date, CURDATE()) <= 1 THEN 'CRITICAL'
        WHEN DATEDIFF(ib.expiry_date, CURDATE()) <= 3 THEN 'URGENT'
        WHEN DATEDIFF(ib.expiry_date, CURDATE()) <= 7 THEN 'WARNING'
        ELSE 'SAFE'
    END as alert_level
FROM inventory_batches ib
JOIN branches b ON ib.branch_id = b.branch_id
JOIN raw_materials rm ON ib.material_id = rm.material_id
WHERE ib.status = 'active' 
  AND ib.current_quantity > 0
  AND ib.expiry_date IS NOT NULL
  AND ib.expiry_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)
ORDER BY ib.expiry_date ASC, b.branch_name, rm.material_name;

-- View: Wastage summary (grouped by category for reporting)
CREATE OR REPLACE VIEW v_wastage_summary AS
SELECT 
    DATE_FORMAT(w.wastage_date, '%Y-%m') as month_year,
    w.wastage_date,
    b.branch_id,
    b.branch_name,
    w.wastage_category,
    rm.material_name,
    rm.category as material_category,
    COUNT(*) as incident_count,
    SUM(w.quantity) as total_quantity,
    SUM(w.estimated_cost) as total_cost
FROM wastage_records w
JOIN branches b ON w.branch_id = b.branch_id
JOIN raw_materials rm ON w.material_id = rm.material_id
WHERE w.approval_status = 'approved'
GROUP BY DATE_FORMAT(w.wastage_date, '%Y-%m'), w.wastage_date, 
         b.branch_id, w.wastage_category, rm.material_id
ORDER BY w.wastage_date DESC;

-- View: Batch movement tracking (FIFO compliance audit)
CREATE OR REPLACE VIEW v_batch_movements AS
SELECT 
    sm.movement_id,
    sm.movement_date,
    b.branch_name,
    rm.material_name,
    sm.movement_type,
    ib.batch_number,
    ib.expiry_date,
    sm.quantity,
    u.full_name as performed_by,
    sm.notes
FROM stock_movements sm
JOIN branches b ON sm.branch_id = b.branch_id
JOIN raw_materials rm ON sm.material_id = rm.material_id
LEFT JOIN inventory_batches ib ON sm.batch_id = ib.batch_id
LEFT JOIN users u ON sm.performed_by = u.user_id
WHERE sm.batch_id IS NOT NULL
ORDER BY sm.movement_date DESC;

-- View: FIFO batch availability (for dispatch decisions)
CREATE OR REPLACE VIEW v_fifo_batch_availability AS
SELECT 
    ib.branch_id,
    b.branch_name,
    ib.material_id,
    rm.material_code,
    rm.material_name,
    ib.batch_id,
    ib.batch_number,
    ib.delivery_date,
    ib.expiry_date,
    ib.current_quantity,
    ib.unit_cost,
    DATEDIFF(ib.expiry_date, CURDATE()) as days_until_expiry,
    ROW_NUMBER() OVER (
        PARTITION BY ib.branch_id, ib.material_id 
        ORDER BY ib.expiry_date ASC, ib.delivery_date ASC
    ) as fifo_rank
FROM inventory_batches ib
JOIN branches b ON ib.branch_id = b.branch_id
JOIN raw_materials rm ON ib.material_id = rm.material_id
WHERE ib.status = 'active' 
  AND ib.current_quantity > 0
ORDER BY ib.branch_id, ib.material_id, ib.expiry_date ASC;

-- View: Variance breakdown by category
CREATE OR REPLACE VIEW v_variance_category_summary AS
SELECT 
    psc.count_date,
    b.branch_name,
    rm.material_name,
    vr.variance_category,
    SUM(vr.quantity) as category_quantity,
    COUNT(*) as reason_count
FROM variance_reasons vr
JOIN physical_stock_counts psc ON vr.count_id = psc.count_id
JOIN branches b ON psc.branch_id = b.branch_id
JOIN raw_materials rm ON vr.material_id = rm.material_id
GROUP BY psc.count_date, b.branch_id, rm.material_id, vr.variance_category
ORDER BY psc.count_date DESC;

-- ========================================
-- PART 4: STORED PROCEDURES & FUNCTIONS
-- ========================================

DELIMITER $$

-- Function: Get oldest available batch for FIFO dispatch
DROP FUNCTION IF EXISTS get_oldest_batch$$
CREATE FUNCTION get_oldest_batch(
    p_branch_id INT,
    p_material_id INT
) RETURNS INT
DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE v_batch_id INT;
    
    SELECT batch_id INTO v_batch_id
    FROM inventory_batches
    WHERE branch_id = p_branch_id
      AND material_id = p_material_id
      AND status = 'active'
      AND current_quantity > 0
      AND (expiry_date IS NULL OR expiry_date > CURDATE())
    ORDER BY 
        COALESCE(expiry_date, '9999-12-31') ASC,
        delivery_date ASC
    LIMIT 1;
    
    RETURN v_batch_id;
END$$

-- Procedure: Update inventory batch summary fields
DROP PROCEDURE IF EXISTS update_inventory_batch_summary$$
CREATE PROCEDURE update_inventory_batch_summary(
    IN p_branch_id INT,
    IN p_material_id INT
)
BEGIN
    DECLARE v_total_batches INT;
    DECLARE v_oldest_expiry DATE;
    DECLARE v_near_expiry_count INT;
    
    -- Count total active batches
    SELECT COUNT(*) INTO v_total_batches
    FROM inventory_batches
    WHERE branch_id = p_branch_id
      AND material_id = p_material_id
      AND status = 'active'
      AND current_quantity > 0;
    
    -- Get oldest expiry date
    SELECT MIN(expiry_date) INTO v_oldest_expiry
    FROM inventory_batches
    WHERE branch_id = p_branch_id
      AND material_id = p_material_id
      AND status = 'active'
      AND current_quantity > 0
      AND expiry_date IS NOT NULL;
    
    -- Count batches expiring within 7 days
    SELECT COUNT(*) INTO v_near_expiry_count
    FROM inventory_batches
    WHERE branch_id = p_branch_id
      AND material_id = p_material_id
      AND status = 'active'
      AND current_quantity > 0
      AND expiry_date IS NOT NULL
      AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY);
    
    -- Update inventory summary
    UPDATE inventory
    SET total_batches = v_total_batches,
        oldest_expiry_date = v_oldest_expiry,
        near_expiry_count = v_near_expiry_count
    WHERE branch_id = p_branch_id
      AND material_id = p_material_id;
END$$

-- Procedure: Auto-expire batches that have passed expiry date
DROP PROCEDURE IF EXISTS auto_expire_batches$$
CREATE PROCEDURE auto_expire_batches()
BEGIN
    DECLARE v_expired_count INT DEFAULT 0;
    
    -- Update batch status
    UPDATE inventory_batches
    SET status = 'expired'
    WHERE status = 'active'
      AND expiry_date < CURDATE()
      AND current_quantity > 0;
    
    SET v_expired_count = ROW_COUNT();
    
    -- Return count of expired batches
    SELECT v_expired_count as expired_batch_count;
END$$

DELIMITER ;

-- ========================================
-- PART 5: TRIGGERS
-- ========================================

DELIMITER $$

-- Trigger: Auto-update inventory summary when batch changes
DROP TRIGGER IF EXISTS trg_batch_after_update$$
CREATE TRIGGER trg_batch_after_update
AFTER UPDATE ON inventory_batches
FOR EACH ROW
BEGIN
    IF NEW.current_quantity <> OLD.current_quantity 
       OR NEW.status <> OLD.status THEN
        CALL update_inventory_batch_summary(NEW.branch_id, NEW.material_id);
    END IF;
END$$

-- Trigger: Auto-update inventory summary when new batch created
DROP TRIGGER IF EXISTS trg_batch_after_insert$$
CREATE TRIGGER trg_batch_after_insert
AFTER INSERT ON inventory_batches
FOR EACH ROW
BEGIN
    CALL update_inventory_batch_summary(NEW.branch_id, NEW.material_id);
END$$

-- Trigger: Update batch status to depleted when quantity reaches zero
DROP TRIGGER IF EXISTS trg_batch_check_depleted$$
CREATE TRIGGER trg_batch_check_depleted
BEFORE UPDATE ON inventory_batches
FOR EACH ROW
BEGIN
    IF NEW.current_quantity <= 0 AND OLD.current_quantity > 0 THEN
        SET NEW.status = 'depleted';
    END IF;
END$$

DELIMITER ;

-- ========================================
-- PART 6: INITIAL DATA & CONFIGURATION
-- ========================================

-- Create notification preferences table (for future use)
CREATE TABLE IF NOT EXISTS notification_preferences (
    preference_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    notification_type ENUM('expiry_alert', 'wastage_alert', 'variance_alert', 'low_stock') NOT NULL,
    enabled BOOLEAN DEFAULT TRUE,
    email_enabled BOOLEAN DEFAULT TRUE,
    days_before INT DEFAULT 7 COMMENT 'For expiry alerts',
    threshold_amount DECIMAL(10,2) COMMENT 'For cost-based alerts',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_type (user_id, notification_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='User notification preferences for alerts';

-- ========================================
-- MIGRATION COMPLETE
-- ========================================

-- Log migration completion
CREATE TABLE IF NOT EXISTS schema_migrations (
    migration_id INT PRIMARY KEY AUTO_INCREMENT,
    version VARCHAR(20) NOT NULL UNIQUE,
    description TEXT,
    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO schema_migrations (version, description)
VALUES ('001', 'Perishable goods and wastage tracking system')
ON DUPLICATE KEY UPDATE applied_at = CURRENT_TIMESTAMP;

-- Display summary
SELECT 'Migration 001 completed successfully!' as status,
       NOW() as completed_at;

-- Show new tables
SELECT 
    'New Tables Created:' as info,
    GROUP_CONCAT(table_name SEPARATOR ', ') as tables
FROM information_schema.tables
WHERE table_schema = 'inventory_system'
  AND table_name IN (
      'suppliers', 
      'procurement_orders', 
      'procurement_order_items',
      'inventory_batches',
      'wastage_records',
      'variance_reasons',
      'notification_preferences',
      'schema_migrations'
  );

-- ========================================
-- POST-MIGRATION NOTES
-- ========================================
-- 
-- 1. Run database integrity check:
--    SELECT COUNT(*) FROM suppliers; (should be 0)
--    SELECT COUNT(*) FROM inventory_batches; (should be 0)
-- 
-- 2. Existing inventory migration:
--    You need to create initial batches for existing inventory.
--    See: database/migrations/001_data_migration_guide.md
-- 
-- 3. Next steps:
--    - Phase 2: Build supplier management interface
--    - Phase 3: Build procurement order system
--    - Phase 4: Build batch tracking UI
-- 
-- 4. Performance monitoring:
--    - Monitor query performance on inventory_batches table
--    - Ensure indexes are being used (EXPLAIN queries)
-- 
-- ========================================
