# Inventory and Distribution Management System

A web-based centralized inventory management system for multi-branch food businesses. Track raw materials from main commissary to branches, eliminate inventory shrinkage, and automate variance reporting.

## 🎯 Overview

**Purpose:** Eliminate manual paper-based inventory tracking and detect inventory discrepancies  
**Target Users:** Multi-branch food service businesses (restaurants, food processing)  
**Key Benefit:** Real-time inventory tracking with automated variance detection

## 💻 Technology Stack

- **Frontend:** HTML5, CSS3, JavaScript (Vanilla)
- **Backend:** PHP 7.4+
- **Database:** MySQL 5.7+
- **Server:** Apache (XAMPP recommended)

## ✨ Core Features

- **Stock Requisition System** - Branches request materials from commissary
- **Approval Workflow** - Review, adjust, and approve requests
- **Automated Dispatch** - Automatic inventory updates on dispatch
- **Variance Reports** - Compare system records vs physical counts
- **Multi-Branch Management** - Separate tracking for each location
- **Role-Based Access** - Superadmin, Admin, Branch User levels
- **Inventory Adjustments** - Manual corrections with audit trail
- **Materials Management** - Configure products, categories, units

## 🚀 Quick Start

### Prerequisites
- XAMPP (or LAMP/WAMP stack)
- PHP 7.4+
- MySQL 5.7+

### Installation

1. **Copy Files**
   ```bash
   # Copy project folder to web server root
   # Example: C:\xampp\htdocs\inventory and distribution system\
   ```

2. **Create Database**
   ```bash
   # Open phpMyAdmin: http://localhost/phpmyadmin
   # Import: database/schema.sql (creates structure)
   # Import: database/sample-data.sql (adds test data)
   ```

3. **Configure** (Optional)
   ```bash
   # Copy .env.example to .env
   # Update database credentials if different from defaults
   ```

4. **Access System**
   ```
   URL: http://localhost/inventory%20and%20distribution%20system/
   ```

### Default Login Credentials

| Role | Username | Password | Access Level |
|------|----------|----------|--------------|
| Superadmin | `superadmin` | `admin123` | Full system access, reports |
| Admin | `admin` | `admin123` | Approve/dispatch requests |
| Branch User | `clarin_user` | `admin123` | Request stock, stock counts |

**⚠️ Change all passwords after first login!**

## 📁 Project Structure

```
/config          → Configuration files
/database        → SQL schema and sample data
/assets          → CSS, JavaScript, images
/includes        → Shared header, footer, sidebar
/branch          → Branch user pages
/admin           → Admin pages
/superadmin      → Superadmin pages
/utilities       → Helper scripts
index.php        → Login page
```

## 🔐 User Roles

### Branch User
- View branch inventory
- Submit stock requisition requests
- Record physical stock counts
- View request history

### Admin (Commissary Manager)
- Review pending requests
- Approve/adjust requisition quantities
- Dispatch stock to branches
- Adjust commissary inventory
- Monitor stock levels

### Superadmin (Business Owner)
- All admin capabilities
- Manage users and branches
- Configure raw materials
- View variance reports
- System-wide analytics

## 📖 Documentation

- **[QUICK_START_GUIDE.md](QUICK_START_GUIDE.md)** - Common tasks and workflows
- **[COMPLETE_SYSTEM_DOCUMENTATION.md](COMPLETE_SYSTEM_DOCUMENTATION.md)** - Full technical documentation
- **[IMPROVEMENTS_ROADMAP.md](IMPROVEMENTS_ROADMAP.md)** - Future enhancements

## 🛠️ Troubleshooting

### Database Connection Failed
- Verify MySQL is running in XAMPP
- Check credentials in `config/database.php` or `.env`
- Ensure `inventory_system` database exists

### Login Not Working
- Verify sample data was imported
- Check username/password (case-sensitive)
- Clear browser cache and cookies

### CSS/Assets Not Loading
- Verify Apache is running
- Check file paths are correct
- Review browser console for 404 errors

### Blank Page After Login
- Enable error reporting: `display_errors = On` in php.ini
- Check Apache error logs
- Verify session support is enabled in PHP

## 🔒 Security Notes

- All passwords are hashed using PHP `password_hash()`
- Session management with automatic timeout (30 minutes)
- Role-based access control on all pages
- Input sanitization and prepared statements prevent SQL injection
- XSS protection via `htmlspecialchars()`

**For Production:**
- Enable HTTPS and set `session.cookie_secure = 1`
- Change all default passwords
- Restrict database user permissions
- Configure proper error logging (disable display_errors)
- Review and implement security improvements from roadmap

## 🚧 Development Status

**Current Version:** 1.0  
**Status:** Fully functional for core operations  
**Last Updated:** February 17, 2026

See [IMPROVEMENTS_ROADMAP.md](IMPROVEMENTS_ROADMAP.md) for planned enhancements.

## 📄 License

Academic project - Educational use permitted.

---

**Need Help?** Check the [QUICK_START_GUIDE.md](QUICK_START_GUIDE.md) for common workflows.
