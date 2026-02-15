# 🚀 QUICK START GUIDE
## Inventory & Distribution Management System

---

## ⚡ 5-MINUTE SETUP

### 1. Install XAMPP
- Download from: https://www.apachefriends.org/
- Install and start Apache + MySQL

### 2. Copy Files
```
Copy folder to: C:\xampp\htdocs\
Final path: C:\xampp\htdocs\inventory and distribution system\
```

### 3. Import Database
```
1. Open: http://localhost/phpmyadmin
2. Create database: inventory_system
3. Import: database/schema.sql
```

### 4. Access System
```
URL: http://localhost/inventory%20and%20distribution%20system/
Login: superadmin / admin123
```

---

## 🎯 COMMON TASKS

### As BRANCH USER

#### Request Stock
```
1. Login → Request Stock
2. Select materials → Enter quantities
3. Add notes → Submit Request
4. Track in "View Requests"
```

#### Physical Stock Count
```
1. Click "Physical Stock Count"
2. Enter actual quantities found
3. Add notes for discrepancies
4. Submit Count
```

---

### As ADMIN

#### Approve Request
```
1. Login → Pending Requests
2. Click "Review" on request
3. Check stock availability
4. Adjust if needed → Approve
```

#### Dispatch Stock
```
1. Approved Requests
2. Click "Dispatch Stock"
3. Review items → Confirm Dispatch
```

#### Adjust Inventory
```
1. Adjust Inventory
2. Select branch + material
3. Choose Add/Remove → Enter quantity
4. Provide reason → Apply
```

---

### As SUPERADMIN

#### View Variance Report
```
1. Login → Variance Reports
2. Set date range
3. Filter by branch/type
4. Analyze shortages/overages
```

#### Add User
```
1. Manage Users
2. Enter user details
3. Assign role + branch
4. Set username/password → Add
```

#### Add Branch
```
1. Manage Branches
2. Enter branch name + location
3. Add contact number
4. Submit (inventory auto-initialized)
```

#### Add Material
```
1. Manage Materials
2. Enter code + name
3. Set category + unit
4. Set minimum level → Add
```

---

## 🔑 DEFAULT LOGINS

| Role | Username | Password | Access Level |
|------|----------|----------|--------------|
| **Superadmin** | superadmin | admin123 | Full System |
| **Admin** | admin | admin123 | Operations |
| **Branch** | clarin_user | admin123 | Branch Only |
| **Branch** | tudela_user | admin123 | Branch Only |
| **Branch** | jimenez_user | admin123 | Branch Only |

⚠️ **Change passwords after first login!**

---

## 📊 KEY FEATURES BY ROLE

### 🔴 SUPERADMIN
- ✅ Variance Reports (detect losses)
- ✅ Manage Users
- ✅ Manage Branches
- ✅ Manage Materials
- ✅ System Reports
- ✅ View All Data

### 🟠 ADMIN
- ✅ Approve/Reject Requests
- ✅ Dispatch Stock
- ✅ View Commissary Inventory
- ✅ Adjust Inventory
- ✅ View All Requests

### 🟢 BRANCH USER
- ✅ Request Stock
- ✅ Physical Stock Count
- ✅ View Branch Inventory
- ✅ Track Request Status

---

## ⚠️ TROUBLESHOOTING

### Cannot Login?
```
✅ Check XAMPP running (Apache + MySQL)
✅ Verify database imported
✅ Try: superadmin / admin123
✅ Clear browser cookies
```

### Page Not Found?
```
✅ URL: http://localhost/inventory%20and%20distribution%20system/
✅ Check folder in C:\xampp\htdocs\
✅ Verify exact folder name (with spaces)
```

### Blank Page?
```
✅ Check PHP version (7.4+)
✅ Enable PHP errors in php.ini
✅ Check Apache error log
✅ Verify all files copied correctly
```

### Database Error?
```
✅ MySQL running in XAMPP?
✅ Database name: inventory_system
✅ Username: root, Password: (empty)
✅ Check config/database.php
```

---

## 🎨 UI COLOR CODES

| Color | Meaning | Used For |
|-------|---------|----------|
| 🟢 Green | Success | Dispatched, Active |
| 🔵 Blue | Info | Approved |
| 🟡 Yellow | Warning | Pending, Low Stock |
| 🔴 Red | Alert | Rejected, Shortage |

---

## 📂 PROJECT STRUCTURE

```
inventory and distribution system/
│
├── index.php              (Login page)
├── logout.php             (Logout handler)
├── change_password.php    (Password change)
├── unauthorized.php       (Access denied)
│
├── admin/                 (Admin pages)
│   ├── dashboard.php
│   ├── pending_requests.php
│   ├── review_request.php
│   ├── approved_requests.php
│   ├── dispatch_request.php
│   ├── all_requests.php
│   ├── commissary_inventory.php
│   └── adjust_inventory.php  ⭐ NEW
│
├── branch/                (Branch pages)
│   ├── dashboard.php
│   ├── request_stock.php
│   ├── stock_count.php
│   ├── view_requests.php
│   └── view_request_details.php
│
├── superadmin/            (Superadmin pages)
│   ├── dashboard.php
│   ├── variance_report.php
│   ├── manage_users.php
│   ├── manage_branches.php
│   ├── manage_materials.php  ⭐ NEW
│   └── system_reports.php
│
├── config/                (Configuration)
│   ├── database.php
│   └── auth.php
│
├── includes/              (Shared components)
│   ├── header.php
│   ├── sidebar_end.php
│   └── footer.php
│
├── assets/                (Static files)
│   ├── css/style.css
│   └── js/main.js
│
├── database/              (Database schema)
│   └── schema.sql
│
└── utilities/             (Helper scripts)
    └── generate_password_hash.php
```

---

## 🔄 WORKFLOW DIAGRAMS

### Stock Request Flow
```
Branch User → Submit Request
     ↓
Admin → Review Request
     ↓
Admin → Approve/Reject
     ↓
Admin → Dispatch Stock
     ↓
Inventory Updated
```

### Variance Detection Flow
```
Branch User → Physical Count
     ↓
System → Calculate Variance
     ↓
Superadmin → View Variance Report
     ↓
Management → Investigate Shortages
```

---

## 📱 MOBILE ACCESS

The system is responsive and can be accessed on:
- ✅ Desktop computers
- ✅ Tablets
- ✅ Smartphones

**Note:** For mobile access, connect to same network as XAMPP server or configure for external access.

---

## 🔒 SECURITY TIPS

1. **Change Default Passwords Immediately**
   - Go to: change_password.php
   - Use strong passwords (8+ characters)

2. **Regular Backups**
   - Backup database weekly
   - Export from phpMyAdmin

3. **User Management**
   - Deactivate users when they leave
   - Review user list regularly

4. **Access Control**
   - Don't share login credentials
   - Assign appropriate roles only

---

## 📈 PERFORMANCE TIPS

1. **Regular Maintenance**
   - Clean old stock count data (keep last 3 months)
   - Archive old requisitions yearly

2. **Database Optimization**
   - Index performance is already optimized
   - Run OPTIMIZE TABLE quarterly

3. **Efficient Usage**
   - Perform stock counts weekly or daily
   - Approve requests promptly
   - Dispatch within 24 hours of approval

---

## 💡 BEST PRACTICES

### For Branch Users
- ✅ Request stock based on actual needs
- ✅ Perform physical counts regularly
- ✅ Report discrepancies immediately
- ✅ Add detailed notes to requests

### For Admins
- ✅ Review requests within 24 hours
- ✅ Check commissary stock before approving
- ✅ Dispatch approved requests promptly
- ✅ Document all adjustments clearly

### For Superadmins
- ✅ Review variance reports weekly
- ✅ Investigate significant shortages
- ✅ Monitor system reports monthly
- ✅ Keep user list up to date

---

## 📞 NEED HELP?

### Documentation
- 📄 README.md - Project overview
- 📄 COMPLETE_SYSTEM_DOCUMENTATION.md - Full docs
- 📄 IMPROVEMENTS_ROADMAP.md - Future enhancements

### Database Issues
- Check phpMyAdmin: http://localhost/phpmyadmin
- Verify tables exist in `inventory_system` database

### Code Issues
- Enable PHP error reporting
- Check Apache error logs
- Verify file permissions

---

## ✅ PRE-LAUNCH CHECKLIST

Before going live:
- [ ] Change all default passwords
- [ ] Add real branch data
- [ ] Add actual raw materials
- [ ] Create real user accounts
- [ ] Test all workflows
- [ ] Perform data backup
- [ ] Train all users
- [ ] Document custom procedures

---

## 🎉 YOU'RE READY!

The system is **fully functional** and ready to use. Start by:

1. Login as superadmin
2. Add your branches
3. Add your materials
4. Create user accounts
5. Train staff
6. Start operations!

**Happy inventory management! 📦**

---

**System Version:** 2.0  
**Last Updated:** February 15, 2026  
**Status:** Production Ready ✅
