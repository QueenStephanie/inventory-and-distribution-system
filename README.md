# Web-Based Centralized Inventory and Stock Distribution Management System

## 📋 Project Overview

A comprehensive inventory management system designed specifically for multi-branch fried chicken businesses to track raw materials from the main commissary to smaller branches, eliminating inventory shrinkage and manual paper-based tracking.

**Target Client:** Growing multi-branch food businesses in areas like Clarin, Tudela, or Jimenez experiencing inventory management challenges.

## 🎯 Core Problem Solved

Eliminates inventory shrinkage ("nawala nga manok o mantika") by strictly tracking the movement of raw materials and providing automated variance reports to identify discrepancies between expected and actual inventory levels.

## 💻 Technology Stack

- **Frontend:** HTML5, CSS3, JavaScript (Vanilla)
- **Backend:** PHP 7.4+
- **Database:** MySQL 5.7+
- **Server:** XAMPP (Apache + MySQL)

## 🔐 User Access Levels

### 1. Branch User (Cashier/Branch Manager)
- View assigned branch inventory
- Submit stock requisition requests
- Input daily physical stock counts
- View request history

### 2. Admin (Commissary Manager/Dispatcher)
- View all branch requests
- Approve/adjust requisition quantities
- Dispatch stock to branches
- Monitor commissary inventory levels

### 3. Superadmin (Business Owner)
- Full system access ("God Mode")
- Manage user accounts
- Add/manage branches
- **View critical variance reports** (main feature)
- System-wide analytics

## 🚀 Installation Instructions

### Prerequisites
- XAMPP installed (or similar LAMP/WAMP stack)
- PHP 7.4 or higher
- MySQL 5.7 or higher

### Step 1: Setup Files
1. Copy the entire `inventory and distribution system` folder to `C:\xampp\htdocs\`
2. Your path should be: `C:\xampp\htdocs\inventory and distribution system\`

### Step 2: Create Database
1. Start XAMPP (Apache and MySQL modules)
2. Open phpMyAdmin: http://localhost/phpmyadmin
3. Navigate to SQL tab
4. Open the file: `database/schema.sql`
5. Copy all contents and paste into SQL tab
6. Click "Go" to execute

### Step 3: Verify Database Configuration
1. Open `config/database.php`
2. Check database credentials:
   ```php
   DB_HOST: 'localhost'
   DB_USER: 'root'
   DB_PASS: '' (leave empty for default XAMPP)
   DB_NAME: 'inventory_system'
   ```

### Step 4: Access the System
1. Open browser and navigate to: http://localhost/inventory%20and%20distribution%20system/
2. You should see the login page

## 🔑 Default Login Credentials

### Superadmin Account
- **Username:** `superadmin`
- **Password:** `admin123`
- **Access:** Full system control, variance reports, user/branch management

### Admin Account
- **Username:** `admin`
- **Password:** `admin123`
- **Access:** Approve requests, dispatch stock, commissary inventory

### Branch User Accounts
- **Clarin Branch:**
  - Username: `clarin_user`
  - Password: `admin123`
  
- **Tudela Branch:**
  - Username: `tudela_user`
  - Password: `admin123`
  
- **Jimenez Branch:**
  - Username: `jimenez_user`
  - Password: `admin123`

## 📊 Key Features

### 1. Stock Requisition & Approval Workflow
- Branches request materials digitally
- Admins review and approve/adjust quantities
- Automatic inventory deduction upon dispatch
- Complete audit trail

### 2. Inventory Variance Report ⭐ (Critical Feature)
- Compares system records vs physical counts
- Highlights missing items (shortages)
- Identifies overages
- Filters by date range and branch
- **This is the main feature for panel defense**

### 3. Multi-Branch Management
- Separate inventory tracking per branch
- Main commissary oversight
- Real-time stock levels

### 4. User Management
- Role-based access control
- Branch-specific assignments
- Activity tracking

## 📁 Project Structure

```
inventory and distribution system/
├── config/
│   ├── database.php          # Database connection
│   └── auth.php              # Authentication functions
├── database/
│   └── schema.sql            # Database structure & sample data
├── assets/
│   ├── css/
│   │   └── style.css         # Main stylesheet
│   └── js/
│       └── main.js           # JavaScript functionality
├── includes/
│   ├── header.php            # Common header
│   ├── sidebar_end.php       # Sidebar end tag
│   └── footer.php            # Common footer
├── branch/                   # Branch User Module
│   ├── dashboard.php
│   ├── request_stock.php
│   ├── view_requests.php
│   ├── view_request_details.php
│   └── stock_count.php
├── admin/                    # Admin Module
│   ├── dashboard.php
│   ├── pending_requests.php
│   ├── review_request.php
│   ├── approved_requests.php
│   ├── dispatch_request.php
│   ├── all_requests.php
│   └── commissary_inventory.php
├── superadmin/              # Superadmin Module
│   ├── dashboard.php
│   ├── variance_report.php  # CRITICAL FEATURE
│   ├── manage_users.php
│   ├── manage_branches.php
│   └── system_reports.php
├── index.php                # Login page
├── logout.php               # Logout handler
└── unauthorized.php         # Access denied page
```

## 🎓 Panel Defense Strategy

### The "Urgent Need" Argument
When asked about missing features (mobile app, AI, customer module):
- **Response:** "Based on client interviews, the business is experiencing critical inventory losses. Our priority is stabilizing internal operations before external expansion."
- **Evidence:** The Variance Report feature directly addresses the bleeding money issue.

### Key Talking Points
1. **Focused Solution:** Two core features deeply implemented rather than shallow coverage of many features
2. **Business Impact:** Direct ROI - stops inventory shrinkage immediately
3. **Scalability:** Architecture supports future additions (documented in recommendations) 4. **Real-World Ready:** Tested workflow matches actual business operations

### Feature Highlights for Demo
1. Show Branch User requesting stock
2. Show Admin approving and dispatching
3. **Emphasize Variance Report** - show how it catches missing inventory
4. Demonstrate role-based security

## 🛠️ Troubleshooting

### Database Connection Error
- Verify XAMPP MySQL is running
- Check database credentials in `config/database.php`
- Ensure database `inventory_system` exists

### Login Not Working
- Clear browser cache
- Verify correct username/password
- Check that users table is populated

### CSS Not Loading
- Check file path: `assets/css/style.css`
- Verify Apache is running
- Check browser console for errors

### Blank Page After Login
- Enable PHP error reporting in `php.ini`
- Check Apache error logs
- Verify session support is enabled

## 📝 Future Enhancements (Recommendations Chapter)

1. **Mobile Application** - Native apps for iOS/Android
2. **SMS Notifications** - Alert on low stock
3. **Barcode Scanning** - Speed up inventory counting
4. **Sales Integration** - Automatic deduction based on POS
5. **Supplier Module** - Track orders to commissary
6. **Advanced Analytics** - Predictive demand forecasting
7. **Email Reports** - Automated variance alerts
8. **Multi-Currency** - For expansion beyond local area

## 👥 Team Roles

- **The Hustler:** Client communication, panel defense lead
- **The Coder:** PHP/MySQL implementation, debugging
- **The Documenter:** Chapters 1-5 documentation, formatting

## 📞 Support

For questions or issues during development:
1. Check this README thoroughly
2. Review code comments
3. Test with demo accounts first
4. Verify database schema is correctly loaded

## ⚖️ License

This is an academic capstone project. All rights to modify and use for educational purposes are granted to the development team and academic institution.

---

**Developed:** February 2026
**Platform:** XAMPP (Windows)
**Purpose:** Capstone Project - Inventory Management System

---

## ✅ Pre-Deployment Checklist

- [ ] Database created and populated
- [ ] All default accounts tested
- [ ] Variance report generates correctly
- [ ] Stock requisition workflow complete
- [ ] All three user roles tested
- [ ] Print function works on reports
- [ ] Responsive design verified
- [ ] Security tested (role restrictions)
- [ ] Documentation complete (Chapters 1-5)

**Good luck with your defense! Focus on the Variance Report - that's your winning feature! 🎯**
