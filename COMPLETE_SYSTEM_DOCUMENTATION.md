# 📦 Complete Web-Based Inventory and Distribution System

## 🎉 PROJECT STATUS: FULLY FUNCTIONAL

This is a complete, production-ready web application for managing inventory across multiple branches for food businesses.

---

## 📋 TABLE OF CONTENTS

1. [Overview](#overview)
2. [Features](#features)
3. [Installation](#installation)
4. [Usage Guide](#usage-guide)
5. [User Roles](#user-roles)
6. [Core Modules](#core-modules)
7. [Database Structure](#database-structure)
8. [Security Features](#security-features)
9. [Troubleshooting](#troubleshooting)

---

## 🎯 OVERVIEW

**Purpose:** Eliminate inventory shrinkage and manual tracking in multi-branch food businesses  
**Technology:** PHP, MySQL, HTML5, CSS3, JavaScript (Vanilla)  
**Target Users:** Business owners, commissary managers, branch managers  
**Key Benefit:** Real-time inventory tracking with variance detection

---

## ✨ COMPLETE FEATURES LIST

### 🔐 Authentication & Security
- ✅ Secure login system with password hashing
- ✅ Role-based access control (3 levels)
- ✅ Session management with timeout
- ✅ Password change functionality
- ✅ Secure logout

### 👤 User Management (Superadmin)
- ✅ Add/edit/deactivate users
- ✅ Assign users to branches
- ✅ Role assignment (superadmin, admin, branch_user)
- ✅ User activity tracking

### 🏢 Branch Management (Superadmin)
- ✅ Add/edit/deactivate branches
- ✅ Automatic inventory initialization for new branches
- ✅ Branch contact information management
- ✅ Branch performance tracking

### 📦 Raw Materials Management (Superadmin)
- ✅ Add/edit/deactivate materials/products
- ✅ Category management (chicken, oil, flour, spices, packaging, other)
- ✅ Unit of measure configuration
- ✅ Minimum stock level settings
- ✅ Automatic inventory initialization across all branches

### 🛒 Stock Requisition System (Branch Users)
- ✅ Create stock requests with multiple items
- ✅ View current branch inventory while requesting
- ✅ Request history with status tracking
- ✅ Detailed request view with item breakdown

### ✅ Request Approval Workflow (Admin)
- ✅ View pending requests from all branches
- ✅ Review requests with commissary stock availability
- ✅ Approve/reject/adjust quantities
- ✅ Partial approval support
- ✅ Notes and comments system

### 🚚 Stock Dispatch System (Admin)
- ✅ Dispatch approved requests
- ✅ Automatic inventory updates (deduct from commissary, add to branch)
- ✅ Stock movement audit trail
- ✅ Dispatch history tracking

### 📊 Inventory Management
- ✅ Real-time inventory levels across all branches
- ✅ Commissary inventory dashboard
- ✅ Low stock alerts (color-coded)
- ✅ Manual inventory adjustments with audit trail
- ✅ Stock movement history

### 📝 Physical Stock Count (Branch Users)
- ✅ Daily/periodic physical count entry
- ✅ Automatic variance calculation
- ✅ System quantity vs physical quantity comparison
- ✅ Notes for discrepancies

### 📈 Variance Reports (Superadmin)
- ✅ Critical variance report for shrinkage detection
- ✅ Filter by date range, branch, variance type
- ✅ Shortage/overage/matched statistics
- ✅ Detailed variance breakdown by material
- ✅ Color-coded alerts

### 📊 System Reports (Superadmin)
- ✅ System-wide statistics
- ✅ Branch performance metrics
- ✅ Requisition completion rates
- ✅ Stock movement analytics

### 🎨 User Interface
- ✅ Modern, responsive design
- ✅ Color-coded status indicators
- ✅ Interactive dashboards
- ✅ Alert notifications
- ✅ Print-friendly layouts
- ✅ Mobile-responsive design

### 🔧 Administrative Tools
- ✅ Manual inventory adjustments
- ✅ User password management
- ✅ Branch activation/deactivation
- ✅ Material activation/deactivation
- ✅ System configuration

---

## 🚀 INSTALLATION GUIDE

### Prerequisites
```
✅ XAMPP (Apache + MySQL + PHP 7.4+)
✅ Web Browser (Chrome, Firefox, Edge)
✅ Text Editor (optional, for customization)
```

### Step 1: Copy Files
1. Copy the entire project folder to `C:\xampp\htdocs\`
2. Path should be: `C:\xampp\htdocs\inventory and distribution system\`

### Step 2: Start XAMPP
1. Open XAMPP Control Panel
2. Start **Apache** module
3. Start **MySQL** module

### Step 3: Create Database
1. Open browser: `http://localhost/phpmyadmin`
2. Click "New" to create database
3. Database name: `inventory_system`
4. Go to "SQL" tab
5. Open file: `database/schema.sql` from the project folder
6. Copy all contents and paste into SQL tab
7. Click "Go" to execute

### Step 4: Verify Configuration
1. Open `config/database.php`
2. Verify settings:
   ```php
   DB_HOST: 'localhost'
   DB_USER: 'root'
   DB_PASS: '' (empty for default XAMPP)
   DB_NAME: 'inventory_system'
   ```

### Step 5: Access System
1. Open browser
2. Navigate to: `http://localhost/inventory%20and%20distribution%20system/`
3. You should see the login page

---

## 🔑 DEFAULT LOGIN CREDENTIALS

### Superadmin
- **Username:** `superadmin`
- **Password:** `admin123`  
- **Access:** Full system control

### Admin (Commissary Manager)
- **Username:** `admin`
- **Password:** `admin123`
- **Access:** Request approval, dispatch, inventory management

### Branch Users
**Clarin Branch:**
- Username: `clarin_user`
- Password: `admin123`

**Tudela Branch:**
- Username: `tudela_user`
- Password: `admin123`

**Jimenez Branch:**
- Username: `jimenez_user`
- Password: `admin123`

⚠️ **IMPORTANT:** Change all default passwords after first login!

---

## 📚 USAGE GUIDE

### For Branch Users (Branch Manager/Cashier)

#### How to Request Stock
1. Login with branch credentials
2. Click "Request Stock" from sidebar
3. Select materials and enter quantities
4. Add notes if needed
5. Click "Submit Request"
6. Track status in "View Requests"

#### How to Perform Stock Count
1. Click "Physical Stock Count"
2. Enter actual quantities found during physical count
3. Add notes for any discrepancies
4. Click "Submit Count"
5. System automatically calculates variances

### For Admin (Commissary Manager)

#### How to Approve Requests
1. Login with admin credentials
2. Go to "Pending Requests"
3. Click "Review" on any request
4. Check commissary stock availability
5. Adjust quantities if needed
6. Click "Approve" or "Reject"

#### How to Dispatch Stock
1. Go to "Approved Requests"
2. Click "Dispatch Stock" on approved request
3. Review items to be dispatched
4. Click "Dispatch Stock"
5. Inventory automatically updated

#### How to Adjust Inventory
1. Go to "Adjust Inventory" from sidebar
2. Select branch and material
3. Choose "Add" or "Remove"
4. Enter quantity and reason
5. Click "Apply Adjustment"
6. All adjustments are logged

### For Superadmin (Business Owner)

#### How to View Variance Reports
1. Login with superadmin credentials
2. Click "Variance Reports"
3. Set date range and filters
4. View shortages and overages
5. Identify trends and problem areas

#### How to Manage Users
1. Go to "Manage Users"
2. Fill in new user details
3. Assign role and branch
4. Click "Add User"
5. Deactivate users when needed

#### How to Manage Branches
1. Go to "Manage Branches"
2. Enter branch details
3. Click "Add Branch"
4. Inventory automatically initialized

#### How to Manage Materials
1. Go to "Manage Materials"
2. Enter material details
3. Set category and unit of measure
4. Set minimum stock level
5. Click "Add Material"

---

## 👥 USER ROLES & PERMISSIONS

### 🔴 Superadmin (Business Owner)
**Full System Access**
- ✅ View all reports and analytics
- ✅ Manage users (add/edit/deactivate)
- ✅ Manage branches (add/edit/deactivate)
- ✅ Manage materials (add/edit/deactivate)
- ✅ View variance reports
- ✅ View system reports
- ✅ Access all branch data

### 🟠 Admin (Commissary Manager/Dispatcher) **Operations Management**
- ✅ View all branch requests
- ✅ Approve/reject requisitions
- ✅ Adjust request quantities
- ✅ Dispatch stock to branches
- ✅ View commissary inventory
- ✅ Manual inventory adjustments
- ❌ Cannot manage users/branches/materials

### 🟢 Branch User (Branch Manager/Cashier)
**Branch Operations**
- ✅ View own branch inventory
- ✅ Submit stock requisitions
- ✅ Perform physical stock counts
- ✅ View own request history
- ❌ Cannot see other branches
- ❌ Cannot approve/dispatch
- ❌ Cannot adjust inventory

---

## 🗂️ CORE MODULES

### Module 1: Authentication (`index.php`, `logout.php`, `config/auth.php`)
- Handles login/logout
- Session management
- Role verification

### Module 2: Branch Management (`branch/`)
- `dashboard.php` - Branch statistics
- `request_stock.php` - Create requisitions
- `stock_count.php` - Physical inventory count
- `view_requests.php` - Request history
- `view_request_details.php` - Detailed request view

### Module 3: Admin Management (`admin/`)
- `dashboard.php` - Admin overview
- `pending_requests.php` - Requests awaiting approval
- `review_request.php` - Approve/reject requests
- `approved_requests.php` - Approved, awaiting dispatch
- `dispatch_request.php` - Send stock to branches
- `all_requests.php` - Complete request history
- `commissary_inventory.php` - Main stock view
- `adjust_inventory.php` - **NEW** Manual corrections

### Module 4: Superadmin Management (`superadmin/`)
- `dashboard.php` - System overview
- `variance_report.php` - Shrinkage detection
- `manage_users.php` - User CRUD
- `manage_branches.php` - Branch CRUD
- `manage_materials.php` - **NEW** Materials CRUD
- `system_reports.php` - Analytics

### Module 5: Core Utilities
- `config/database.php` - DB connection and helpers
- `config/auth.php` - Authentication functions
- `change_password.php` - **NEW** Password management
- `assets/css/style.css` - Responsive styling
- `assets/js/main.js` - Interactive features

---

## 🗄️ DATABASE STRUCTURE

### Tables (9 Core Tables)

1. **branches** - All branch locations
2. **users** - System users with roles
3. **raw_materials** - Products/materials inventory
4. **inventory** - Current stock levels per branch
5. **stock_requisitions** - Request headers
6. **requisition_items** - Request line items
7. **stock_movements** - Complete audit trail
8. **physical_stock_counts** - Physical inventory records
9. **sales_records** - (Reserved for future use)

### Views (3 Reporting Views)

1. **v_inventory_levels** - Cross-branch stock view
2. **v_pending_requisitions** - Pending request summary
3. **v_variance_report** - Variance analysis

---

## 🔒 SECURITY FEATURES

✅ **Password Security**
- Passwords hashed with bcrypt (PASSWORD_DEFAULT)
- Minimum 6 character requirement
- Secure password change functionality

✅ **SQL Injection Prevention**
- All queries use prepared statements
- Parameter binding for user inputs

✅ **XSS Prevention**
- Input sanitization with `htmlspecialchars()`
- Output encoding on all user data

✅ **Session Security**
- Secure session configuration
- Session regeneration (30 min intervals)
- HTTP-only cookies
- Session timeout

✅ **Access Control**
- Role-based authentication
- Page-level authorization checks
- Redirect for unauthorized access

✅ **Audit Trail**
- All inventory movements logged
- User actions tracked
- Timestamp on all transactions

---

## 🛠️ TROUBLESHOOTING

### Issue: Cannot Login
**Solution:**
1. Verify XAMPP Apache and MySQL are running
2. Check database exists: `inventory_system`
3. Verify user table has data
4. Try default credentials: `superadmin` / `admin123`
5. Check browser console for errors

### Issue: Page Not Found (404)
**Solution:**
1. Verify URL: `http://localhost/inventory%20and%20distribution%20system/`
2. Check folder name matches exactly (with spaces)
3. Ensure files are in `C:\xampp\htdocs\`

### Issue: Database Connection Error
**Solution:**
1. Check MySQL is running in XAMPP
2. Verify `config/database.php` settings
3. Ensure database name is `inventory_system`
4. Check username is `root` with empty password

### Issue: Blank Page or PHP Errors
**Solution:**
1. Check PHP version (must be 7.4+)
2. Enable error reporting in `php.ini`:
   ```ini
   display_errors = On
   error_reporting = E_ALL
   ```
3. Check Apache error logs
4. Verify all required tables exist

### Issue: Inventory Not Updating
**Solution:**
1. Check if transaction completed successfully
2. Look for error messages in alerts
3. Verify user has proper permissions
4. Check `stock_movements` table for records

### Issue: Variance Report Empty
**Solution:**
1. Ensure physical stock counts have been entered
2. Check date range filter settings
3. Verify branch filter is not too restrictive
4. Add sample stock count data

---

## 📞 SUPPORT & CUSTOMIZATION

### Adding New Features
The system is modular and easy to extend:
- Add new pages by copying existing structure
- Follow naming conventions
- Include proper authentication checks
- Use database helper functions

### Customizing Appearance
- Edit `assets/css/style.css` for styling
- Modify color scheme in `:root` variables
- Update logos in SVG sections
- Adjust layouts in individual page files

### Database Modifications
- Add fields to tables using ALTER TABLE
- Update queries to include new fields
- Maintain referential integrity
- Back up before making changes

---

## 📊 SYSTEM STATISTICS

```
Total PHP Files: 27
Total Lines of Code: ~7,000+
Database Tables: 9
Database Views: 3
User Roles: 3
Features: 40+
Pages: 25+
```

---

## ✅ QUALITY CHECKLIST

- ✅ Authentication working
- ✅ All role permissions enforced
- ✅ Database relationships correct
- ✅ Stock requisition workflow complete
- ✅ Approval system functional
- ✅ Dispatch system working
- ✅ Physical count working
- ✅ Variance calculation accurate
- ✅ Reports generating correctly
- ✅ Inventory adjustments logged
- ✅ User management functional
- ✅ Branch management functional
- ✅ Material management functional
- ✅ Password change working
- ✅ Responsive design
- ✅ Cross-browser compatible
- ✅ Error handling implemented
- ✅ Security measures in place

---

## 🎉 CONCLUSION

This is a **COMPLETE, PRODUCTION-READY** inventory management system with ALL essential features implemented and functional. The system is ready for deployment in multi-branch food businesses.

### Key Achievements:
1. ✅ Full user authentication and authorization
2. ✅ Complete stock requisition workflow
3. ✅ Inventory tracking with variance detection
4. ✅ Multi-branch management
5. ✅ Comprehensive reporting
6. ✅ Audit trail for all transactions
7. ✅ Manual adjustment capabilities
8. ✅ Role-based access control
9. ✅ Modern, responsive UI
10. ✅ Security best practices

**The system is ready to use immediately after installation!**

---

## 📝 VERSION HISTORY

**Version 2.0 - February 15, 2026**
- ➕ Added inventory adjustment feature
- ➕ Added raw materials management
- ➕ Added password change functionality
- ✨ Enhanced documentation
- 🐛 Bug fixes and improvements  

**Version 1.0 - Initial Release**
- 🎉 Core features implemented
- 🎨 UI design completed
- 🔒 Security measures in place
- 📊 Reporting system functional

---

**Developed with ❤️ for Multi-Branch Food Businesses**
