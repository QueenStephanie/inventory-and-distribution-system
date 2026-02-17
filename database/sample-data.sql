-- Web-Based Centralized Inventory and Stock Distribution Management System
-- Sample Data for Testing and Development
-- Created: February 17, 2026
-- 
-- IMPORTANT: This file contains sample data for development/testing only.
-- For production, create your own data or import real data.

USE inventory_system;

-- Insert default main branch (Commissary)
INSERT INTO branches (branch_name, branch_location, contact_number, is_main_branch, status) 
VALUES ('Main Commissary', 'Ozamiz City', '+63-912-345-6789', TRUE, 'active');

-- Insert sample branches
INSERT INTO branches (branch_name, branch_location, contact_number, is_main_branch, status) 
VALUES 
('Clarin Branch', 'Clarin, Misamis Occidental', '+63-912-345-6780', FALSE, 'active'),
('Tudela Branch', 'Tudela, Misamis Occidental', '+63-912-345-6781', FALSE, 'active'),
('Jimenez Branch', 'Jimenez, Misamis Occidental', '+63-912-345-6782', FALSE, 'active');

-- Insert default superadmin user
-- Password: admin123 (hashed using PHP password_hash with PASSWORD_DEFAULT)
-- IMPORTANT: Change password after first login in production!
INSERT INTO users (username, password, full_name, email, role, branch_id, status) 
VALUES ('superadmin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'admin@example.com', 'superadmin', 1, 'active');

-- Insert default admin user (Commissary Manager)
-- Password: admin123
-- IMPORTANT: Change password after first login in production!
INSERT INTO users (username, password, full_name, email, role, branch_id, status) 
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Commissary Manager', 'commissary@example.com', 'admin', 1, 'active');

-- Insert sample branch users
-- Password for all: admin123 (same hash for testing purposes)
-- IMPORTANT: Change passwords after first login in production!
INSERT INTO users (username, password, full_name, email, role, branch_id, status) 
VALUES 
('clarin_user', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Clarin Branch Manager', 'clarin@example.com', 'branch_user', 2, 'active'),
('tudela_user', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Tudela Branch Manager', 'tudela@example.com', 'branch_user', 3, 'active'),
('jimenez_user', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Jimenez Branch Manager', 'jimenez@example.com', 'branch_user', 4, 'active');

-- Insert sample raw materials (typical for fried chicken business)
INSERT INTO raw_materials (material_code, material_name, category, unit_of_measure, minimum_stock_level, status) 
VALUES 
('CHK-001', 'Whole Chicken', 'chicken', 'kg', 50.00, 'active'),
('CHK-002', 'Chicken Wings', 'chicken', 'kg', 30.00, 'active'),
('CHK-003', 'Chicken Thighs', 'chicken', 'kg', 25.00, 'active'),
('CHK-004', 'Chicken Breast', 'chicken', 'kg', 20.00, 'active'),
('OIL-001', 'Cooking Oil', 'oil', 'liters', 20.00, 'active'),
('FLR-001', 'All Purpose Flour', 'flour', 'kg', 15.00, 'active'),
('FLR-002', 'Breading Mix', 'flour', 'kg', 10.00, 'active'),
('SPC-001', 'Secret Spice Mix', 'spices', 'kg', 5.00, 'active'),
('SPC-002', 'Salt', 'spices', 'kg', 10.00, 'active'),
('SPC-003', 'Black Pepper', 'spices', 'kg', 3.00, 'active'),
('PKG-001', 'Paper Boxes', 'packaging', 'pieces', 100.00, 'active'),
('PKG-002', 'Plastic Bags', 'packaging', 'pieces', 200.00, 'active'),
('PKG-003', 'Tissue Paper', 'packaging', 'packs', 50.00, 'active');

-- Initialize inventory for main commissary branch with sample stock
-- This gives the commissary a starting stock of 500 units for each material
INSERT INTO inventory (branch_id, material_id, current_quantity) 
SELECT 1, material_id, 500.00 
FROM raw_materials 
WHERE status = 'active';

-- Initialize inventory for branch locations with zero starting stock
-- Branches will request stock from the commissary as needed
INSERT INTO inventory (branch_id, material_id, current_quantity) 
SELECT b.branch_id, rm.material_id, 0.00 
FROM branches b 
CROSS JOIN raw_materials rm 
WHERE b.is_main_branch = FALSE AND rm.status = 'active';

-- End of sample data
-- Total records inserted:
-- - 4 branches (1 commissary + 3 branch locations)
-- - 6 users (1 superadmin, 1 admin, 4 branch users)
-- - 13 raw materials
-- - Inventory records for all branches and materials
