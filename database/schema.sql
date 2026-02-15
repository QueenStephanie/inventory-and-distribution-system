-- Web-Based Centralized Inventory and Stock Distribution Management System
-- Database Schema
-- Created: February 15, 2026

DROP DATABASE IF EXISTS inventory_system;
CREATE DATABASE inventory_system;
USE inventory_system;

-- Table: branches
-- Stores all branch locations
CREATE TABLE branches (
    branch_id INT PRIMARY KEY AUTO_INCREMENT,
    branch_name VARCHAR(100) NOT NULL UNIQUE,
    branch_location VARCHAR(255) NOT NULL,
    contact_number VARCHAR(20),
    is_main_branch BOOLEAN DEFAULT FALSE,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Table: users
-- Stores all system users with role-based access
CREATE TABLE users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    role ENUM('branch_user', 'admin', 'superadmin') NOT NULL,
    branch_id INT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (branch_id) REFERENCES branches(branch_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Table: raw_materials
-- Stores all raw materials/products inventory items
CREATE TABLE raw_materials (
    material_id INT PRIMARY KEY AUTO_INCREMENT,
    material_code VARCHAR(50) NOT NULL UNIQUE,
    material_name VARCHAR(100) NOT NULL,
    category ENUM('chicken', 'oil', 'flour', 'spices', 'packaging', 'other') NOT NULL,
    unit_of_measure VARCHAR(20) NOT NULL,
    minimum_stock_level DECIMAL(10,2) DEFAULT 0,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Table: inventory
-- Current stock levels at each branch
CREATE TABLE inventory (
    inventory_id INT PRIMARY KEY AUTO_INCREMENT,
    branch_id INT NOT NULL,
    material_id INT NOT NULL,
    current_quantity DECIMAL(10,2) DEFAULT 0,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (branch_id) REFERENCES branches(branch_id) ON DELETE CASCADE,
    FOREIGN KEY (material_id) REFERENCES raw_materials(material_id) ON DELETE CASCADE,
    UNIQUE KEY unique_branch_material (branch_id, material_id)
) ENGINE=InnoDB;

-- Table: stock_requisitions
-- Stock requests from branches
CREATE TABLE stock_requisitions (
    requisition_id INT PRIMARY KEY AUTO_INCREMENT,
    requisition_number VARCHAR(50) NOT NULL UNIQUE,
    requesting_branch_id INT NOT NULL,
    requested_by INT NOT NULL,
    request_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'approved', 'partially_approved', 'rejected', 'dispatched') DEFAULT 'pending',
    approved_by INT NULL,
    approval_date TIMESTAMP NULL,
    dispatched_by INT NULL,
    dispatch_date TIMESTAMP NULL,
    notes TEXT,
    FOREIGN KEY (requesting_branch_id) REFERENCES branches(branch_id) ON DELETE CASCADE,
    FOREIGN KEY (requested_by) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES users(user_id) ON DELETE SET NULL,
    FOREIGN KEY (dispatched_by) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Table: requisition_items
-- Individual items in each requisition
CREATE TABLE requisition_items (
    item_id INT PRIMARY KEY AUTO_INCREMENT,
    requisition_id INT NOT NULL,
    material_id INT NOT NULL,
    requested_quantity DECIMAL(10,2) NOT NULL,
    approved_quantity DECIMAL(10,2) NULL,
    dispatched_quantity DECIMAL(10,2) NULL,
    unit_of_measure VARCHAR(20) NOT NULL,
    notes TEXT,
    FOREIGN KEY (requisition_id) REFERENCES stock_requisitions(requisition_id) ON DELETE CASCADE,
    FOREIGN KEY (material_id) REFERENCES raw_materials(material_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Table: stock_movements
-- Audit trail for all stock movements
CREATE TABLE stock_movements (
    movement_id INT PRIMARY KEY AUTO_INCREMENT,
    branch_id INT NOT NULL,
    material_id INT NOT NULL,
    movement_type ENUM('receive', 'dispatch', 'adjustment', 'loss', 'return') NOT NULL,
    quantity DECIMAL(10,2) NOT NULL,
    previous_quantity DECIMAL(10,2),
    new_quantity DECIMAL(10,2),
    reference_type ENUM('requisition', 'manual', 'variance') NULL,
    reference_id INT NULL,
    performed_by INT NOT NULL,
    notes TEXT,
    movement_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (branch_id) REFERENCES branches(branch_id) ON DELETE CASCADE,
    FOREIGN KEY (material_id) REFERENCES raw_materials(material_id) ON DELETE CASCADE,
    FOREIGN KEY (performed_by) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Table: physical_stock_counts
-- Daily physical stock counts entered by branches
CREATE TABLE physical_stock_counts (
    count_id INT PRIMARY KEY AUTO_INCREMENT,
    branch_id INT NOT NULL,
    material_id INT NOT NULL,
    count_date DATE NOT NULL,
    system_quantity DECIMAL(10,2) NOT NULL,
    physical_quantity DECIMAL(10,2) NOT NULL,
    variance DECIMAL(10,2) GENERATED ALWAYS AS (physical_quantity - system_quantity) STORED,
    counted_by INT NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (branch_id) REFERENCES branches(branch_id) ON DELETE CASCADE,
    FOREIGN KEY (material_id) REFERENCES raw_materials(material_id) ON DELETE CASCADE,
    FOREIGN KEY (counted_by) REFERENCES users(user_id) ON DELETE CASCADE,
    UNIQUE KEY unique_count (branch_id, material_id, count_date)
) ENGINE=InnoDB;

-- Table: sales_records
-- Daily sales records for variance calculation
CREATE TABLE sales_records (
    sale_id INT PRIMARY KEY AUTO_INCREMENT,
    branch_id INT NOT NULL,
    material_id INT NOT NULL,
    sale_date DATE NOT NULL,
    quantity_used DECIMAL(10,2) NOT NULL,
    recorded_by INT NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (branch_id) REFERENCES branches(branch_id) ON DELETE CASCADE,
    FOREIGN KEY (material_id) REFERENCES raw_materials(material_id) ON DELETE CASCADE,
    FOREIGN KEY (recorded_by) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Insert default main branch (Commissary)
INSERT INTO branches (branch_name, branch_location, contact_number, is_main_branch, status) 
VALUES ('Main Commissary', 'Ozamiz City', '09XX-XXX-XXXX', TRUE, 'active');

-- Insert sample branches
INSERT INTO branches (branch_name, branch_location, contact_number, is_main_branch, status) 
VALUES 
('Clarin Branch', 'Clarin, Misamis Occidental', '09XX-XXX-XXX1', FALSE, 'active'),
('Tudela Branch', 'Tudela, Misamis Occidental', '09XX-XXX-XXX2', FALSE, 'active'),
('Jimenez Branch', 'Jimenez, Misamis Occidental', '09XX-XXX-XXX3', FALSE, 'active');

-- Insert default superadmin user
-- Password: admin123 (hashed using PHP password_hash)
INSERT INTO users (username, password, full_name, email, role, branch_id, status) 
VALUES ('superadmin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'admin@friedchicken.com', 'superadmin', 1, 'active');

-- Insert default admin user (Commissary Manager)
-- Password: admin123
INSERT INTO users (username, password, full_name, email, role, branch_id, status) 
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Commissary Manager', 'commissary@friedchicken.com', 'admin', 1, 'active');

-- Insert sample branch users
-- Password: admin123 (same as other accounts for consistency)
INSERT INTO users (username, password, full_name, email, role, branch_id, status) 
VALUES 
('clarin_user', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Clarin Branch Manager', 'clarin@friedchicken.com', 'branch_user', 2, 'active'),
('tudela_user', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Tudela Branch Manager', 'tudela@friedchicken.com', 'branch_user', 3, 'active'),
('jimenez_user', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Jimenez Branch Manager', 'jimenez@friedchicken.com', 'branch_user', 4, 'active');

-- Insert sample raw materials
INSERT INTO raw_materials (material_code, material_name, category, unit_of_measure, minimum_stock_level, status) 
VALUES 
('CHK-001', 'Whole Chicken', 'chicken', 'kg', 50.00, 'active'),
('CHK-002', 'Chicken Wings', 'chicken', 'kg', 30.00, 'active'),
('CHK-003', 'Chicken Thighs', 'chicken', 'kg', 25.00, 'active'),
('OIL-001', 'Cooking Oil', 'oil', 'liters', 20.00, 'active'),
('FLR-001', 'All Purpose Flour', 'flour', 'kg', 15.00, 'active'),
('FLR-002', 'Breading Mix', 'flour', 'kg', 10.00, 'active'),
('SPC-001', 'Secret Spice Mix', 'spices', 'kg', 5.00, 'active'),
('PKG-001', 'Paper Boxes', 'packaging', 'pieces', 100.00, 'active'),
('PKG-002', 'Plastic Bags', 'packaging', 'pieces', 200.00, 'active');

-- Initialize inventory for main branch (Commissary)
INSERT INTO inventory (branch_id, material_id, current_quantity) 
SELECT 1, material_id, 500.00 FROM raw_materials WHERE status = 'active';

-- Initialize inventory for other branches with zero stock
INSERT INTO inventory (branch_id, material_id, current_quantity) 
SELECT b.branch_id, rm.material_id, 0.00 
FROM branches b 
CROSS JOIN raw_materials rm 
WHERE b.is_main_branch = FALSE AND rm.status = 'active';

-- Create indexes for performance
CREATE INDEX idx_requisitions_status ON stock_requisitions(status);
CREATE INDEX idx_requisitions_branch ON stock_requisitions(requesting_branch_id);
CREATE INDEX idx_movements_date ON stock_movements(movement_date);
CREATE INDEX idx_movements_branch ON stock_movements(branch_id);
CREATE INDEX idx_counts_date ON physical_stock_counts(count_date);
CREATE INDEX idx_sales_date ON sales_records(sale_date);

-- Views for reporting

-- View: Current inventory levels across all branches
CREATE VIEW v_inventory_levels AS
SELECT 
    b.branch_name,
    b.branch_location,
    rm.material_code,
    rm.material_name,
    rm.category,
    i.current_quantity,
    rm.unit_of_measure,
    rm.minimum_stock_level,
    CASE 
        WHEN i.current_quantity <= rm.minimum_stock_level THEN 'LOW'
        WHEN i.current_quantity <= (rm.minimum_stock_level * 1.5) THEN 'MEDIUM'
        ELSE 'ADEQUATE'
    END as stock_status
FROM inventory i
JOIN branches b ON i.branch_id = b.branch_id
JOIN raw_materials rm ON i.material_id = rm.material_id
WHERE b.status = 'active' AND rm.status = 'active';

-- View: Pending requisitions summary
CREATE VIEW v_pending_requisitions AS
SELECT 
    sr.requisition_id,
    sr.requisition_number,
    b.branch_name,
    u.full_name as requested_by_name,
    sr.request_date,
    sr.status,
    COUNT(ri.item_id) as total_items
FROM stock_requisitions sr
JOIN branches b ON sr.requesting_branch_id = b.branch_id
JOIN users u ON sr.requested_by = u.user_id
LEFT JOIN requisition_items ri ON sr.requisition_id = ri.requisition_id
WHERE sr.status IN ('pending', 'approved')
GROUP BY sr.requisition_id;

-- View: Variance report
CREATE VIEW v_variance_report AS
SELECT 
    psc.count_date,
    b.branch_name,
    rm.material_name,
    psc.system_quantity,
    psc.physical_quantity,
    psc.variance,
    CASE 
        WHEN psc.variance < 0 THEN 'SHORTAGE'
        WHEN psc.variance > 0 THEN 'OVERAGE'
        ELSE 'MATCHED'
    END as variance_status,
    u.full_name as counted_by_name
FROM physical_stock_counts psc
JOIN branches b ON psc.branch_id = b.branch_id
JOIN raw_materials rm ON psc.material_id = rm.material_id
JOIN users u ON psc.counted_by = u.user_id
ORDER BY psc.count_date DESC, ABS(psc.variance) DESC;


-- //UPDATE users SET password = '$2y$10$iz2p1UsVOACEvw2FiGUriO7Zt0RyS8WaEbUJOz4ctID1R9KEo3sNG' WHERE username = 'superadmin';