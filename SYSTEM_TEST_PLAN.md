# 🧪 SYSTEM TEST PLAN
## Web-Based Inventory & Distribution Management System

**Version:** 2.0  
**Test Date:** February 15, 2026  
**Status:** Ready for Testing  

---

## 📋 TEST OVERVIEW

This document provides a comprehensive test plan to verify all system features are working according to specifications. Follow each test case sequentially and mark as PASS/FAIL.

---

## ✅ PRE-TEST SETUP

### Prerequisites Checklist
- [ ] XAMPP Apache is running
- [ ] XAMPP MySQL is running
- [ ] Database `inventory_system` exists and is populated
- [ ] Access URL: `http://localhost/inventory%20and%20distribution%20system/`
- [ ] Browser ready (Chrome/Firefox/Edge)
- [ ] All default accounts available

### Test Data Required
```
Superadmin: superadmin / admin123
Admin: admin / admin123
Branch Users:
  - clarin_user / admin123
  - tudela_user / admin123
  - jimenez_user / admin123
```

---

## TEST SECTION 1: AUTHENTICATION & SECURITY

### Test 1.1: Login Functionality
**Priority:** CRITICAL  
**Module:** Authentication  

| Step | Action | Expected Result | Status |
|------|--------|-----------------|--------|
| 1 | Navigate to login page | Login page displays correctly | ⬜ |
| 2 | Leave fields empty, click Login | Error: "Please enter both username and password" | ⬜ |
| 3 | Enter invalid credentials | Error: "Invalid username or password" | ⬜ |
| 4 | Enter: superadmin / admin123 | Successful login, redirect to superadmin dashboard | ⬜ |
| 5 | Check user info displayed | Shows name and role in top nav | ⬜ |

**Result:** ⬜ PASS / ⬜ FAIL  
**Notes:** _____________________________________________

---

### Test 1.2: Role-Based Access Control
**Priority:** CRITICAL  
**Module:** Authorization  

| Step | Action | Expected Result | Status |
|------|--------|-----------------|--------|
| 1 | Login as branch user (clarin_user) | Redirect to branch dashboard | ⬜ |
| 2 | Try to access: ../admin/dashboard.php | Redirect to unauthorized page | ⬜ |
| 3 | Try to access: ../superadmin/dashboard.php | Redirect to unauthorized page | ⬜ |
| 4 | Verify sidebar menu | Only shows branch user options | ⬜ |
| 5 | Logout | Successful logout, redirect to login | ⬜ |

**Result:** ⬜ PASS / ⬜ FAIL  
**Notes:** _____________________________________________

---

### Test 1.3: Password Change
**Priority:** HIGH  
**Module:** Security  

| Step | Action | Expected Result | Status |
|------|--------|-----------------|--------|
| 1 | Login as any user | Successful login | ⬜ |
| 2 | Navigate to: change_password.php | Password change page displays | ⬜ |
| 3 | Enter wrong current password | Error: "Current password is incorrect" | ⬜ |
| 4 | Enter mismatched new passwords | Error: "New passwords do not match" | ⬜ |
| 5 | Enter valid: current + new (min 6 chars) | Success: "Password changed successfully" | ⬜ |
| 6 | Logout and login with NEW password | Successful login | ⬜ |
| 7 | Change back to original password | Success (for testing continuity) | ⬜ |

**Result:** ⬜ PASS / ⬜ FAIL  
**Notes:** _____________________________________________

---

### Test 1.4: Session Management
**Priority:** MEDIUM  
**Module:** Security  

| Step | Action | Expected Result | Status |
|------|--------|-----------------|--------|
| 1 | Login successfully | Session started | ⬜ |
| 2 | Close browser tab | Session persists | ⬜ |
| 3 | Reopen same URL | Still logged in (within 30 min) | ⬜ |
| 4 | Click Logout | Session destroyed, redirect to login | ⬜ |
| 5 | Click browser Back button | Cannot access pages, redirect to login | ⬜ |

**Result:** ⬜ PASS / ⬜ FAIL  
**Notes:** _____________________________________________

---

## TEST SECTION 2: SUPERADMIN MODULE

### Test 2.1: User Management (CRUD)
**Priority:** CRITICAL  
**Module:** Superadmin - User Management  
**Login as:** superadmin / admin123  

| Step | Action | Expected Result | Status |
|------|--------|-----------------|--------|
| 1 | Navigate to "Manage Users" | User list displays | ⬜ |
| 2 | **ADD:** Fill form with test user data | Form accepts input | ⬜ |
| 3 | Submit new user | Success: "User added successfully" | ⬜ |
| 4 | Verify in user list | New user appears in table | ⬜ |
| 5 | Try duplicate username | Error: "Username already exists" | ⬜ |
| 6 | **DEACTIVATE:** Click deactivate on test user | Status changes to "Inactive" | ⬜ |
| 7 | **ACTIVATE:** Click activate | Status changes back to "Active" | ⬜ |

**Test Data:**
```
Username: test_user_001
Password: test123
Full Name: Test User One
Role: branch_user
Branch: Clarin Branch
```

**Result:** ⬜ PASS / ⬜ FAIL  
**Notes:** _____________________________________________

---

### Test 2.2: Branch Management (CRUD)
**Priority:** CRITICAL  
**Module:** Superadmin - Branch Management  

| Step | Action | Expected Result | Status |
|------|--------|-----------------|--------|
| 1 | Navigate to "Manage Branches" | Branch list displays | ⬜ |
| 2 | **ADD:** Fill form with test branch | Form accepts input | ⬜ |
| 3 | Submit new branch | Success: "Branch added successfully" | ⬜ |
| 4 | Verify in branch list | New branch appears | ⬜ |
| 5 | Check database: inventory table | New records created for all materials | ⬜ |
| 6 | **DEACTIVATE:** Click deactivate | Status changes to "Inactive" | ⬜ |
| 7 | **ACTIVATE:** Click activate | Status changes to "Active" | ⬜ |

**Test Data:**
```
Branch Name: Test Branch 001
Location: Test Location, Philippines
Contact: 0912-345-6789
```

**Result:** ⬜ PASS / ⬜ FAIL  
**Notes:** _____________________________________________

---

### Test 2.3: Material Management (CRUD)
**Priority:** CRITICAL  
**Module:** Superadmin - Material Management  

| Step | Action | Expected Result | Status |
|------|--------|-----------------|--------|
| 1 | Navigate to "Manage Materials" | Material list displays | ⬜ |
| 2 | **ADD:** Fill form with test material | Form accepts input | ⬜ |
| 3 | Submit new material | Success: "Material added successfully" | ⬜ |
| 4 | Verify in material list | New material appears | ⬜ |
| 5 | Check database: inventory table | New records for all branches | ⬜ |
| 6 | Try duplicate material code | Error: "Code already exists" | ⬜ |
| 7 | **DEACTIVATE:** Click deactivate | Status changes to "Inactive" | ⬜ |
| 8 | **ACTIVATE:** Click activate | Status changes to "Active" | ⬜ |

**Test Data:**
```
Material Code: TEST-001
Material Name: Test Material One
Category: other
Unit: pieces
Minimum Stock: 10.00
```

**Result:** ⬜ PASS / ⬜ FAIL  
**Notes:** _____________________________________________

---

### Test 2.4: Variance Report
**Priority:** CRITICAL (Core Feature)  
**Module:** Superadmin - Variance Report  

| Step | Action | Expected Result | Status |
|------|--------|-----------------|--------|
| 1 | Navigate to "Variance Reports" | Report page displays | ⬜ |
| 2 | Set date range (last 30 days) | Filters apply | ⬜ |
| 3 | Verify statistics cards | Shows shortage/overage/matched counts | ⬜ |
| 4 | Check variance table | Displays all variances with details | ⬜ |
| 5 | Filter by branch | Results filter correctly | ⬜ |
| 6 | Filter by variance type: "shortage" | Only shortages display | ⬜ |
| 7 | Filter by variance type: "overage" | Only overages display | ⬜ |
| 8 | Verify color coding | Shortages in red, overages in green | ⬜ |

**Result:** ⬜ PASS / ⬜ FAIL  
**Notes:** _____________________________________________

---

### Test 2.5: System Reports
**Priority:** MEDIUM  
**Module:** Superadmin - System Reports  

| Step | Action | Expected Result | Status |
|------|--------|-----------------|--------|
| 1 | Navigate to "System Reports" | Reports page displays | ⬜ |
| 2 | Verify system overview stats | Shows counts for users/branches/materials | ⬜ |
| 3 | Verify requisition stats | Shows total and completion rate | ⬜ |
| 4 | Check branch performance table | Shows all branches with metrics | ⬜ |
| 5 | Verify calculated percentages | Completion rates calculated correctly | ⬜ |

**Result:** ⬜ PASS / ⬜ FAIL  
**Notes:** _____________________________________________

---

### Test 2.6: Superadmin Dashboard
**Priority:** MEDIUM  
**Module:** Superadmin - Dashboard  

| Step | Action | Expected Result | Status |
|------|--------|-----------------|--------|
| 1 | Navigate to dashboard | Dashboard displays | ⬜ |
| 2 | Verify stat cards | Shows branches, users, requisitions, losses | ⬜ |
| 3 | Check recent variances | Displays top 10 variance issues | ⬜ |
| 4 | Verify quick action buttons | Links work correctly | ⬜ |
| 5 | Check all navigation links | All menu items accessible | ⬜ |

**Result:** ⬜ PASS / ⬜ FAIL  
**Notes:** _____________________________________________

---

## TEST SECTION 3: ADMIN MODULE

### Test 3.1: Stock Requisition Approval Workflow
**Priority:** CRITICAL  
**Module:** Admin - Request Management  
**Login as:** admin / admin123  
**Prerequisite:** Must have pending request (create one as branch user first)

| Step | Action | Expected Result | Status |
|------|--------|-----------------|--------|
| 1 | Navigate to "Pending Requests" | List of pending requests displays | ⬜ |
| 2 | Click "Review" on a request | Review page opens with details | ⬜ |
| 3 | Verify commissary stock shown | Shows available stock for each item | ⬜ |
| 4 | Verify branch current stock shown | Shows current branch levels | ⬜ |
| 5 | Check insufficient stock warning | Items with low stock highlighted | ⬜ |
| 6 | Adjust quantities (reduce one item) | Input accepts changes | ⬜ |
| 7 | Click "Approve Request" | Confirmation dialog appears | ⬜ |
| 8 | Confirm approval | Success: Redirect to dispatch page | ⬜ |
| 9 | Verify status changed | Request now in "Approved Requests" | ⬜ |

**Result:** ⬜ PASS / ⬜ FAIL  
**Notes:** _____________________________________________

---

### Test 3.2: Request Rejection
**Priority:** HIGH  
**Module:** Admin - Request Management  

| Step | Action | Expected Result | Status |
|------|--------|-----------------|--------|
| 1 | Go to "Pending Requests" | List displays | ⬜ |
| 2 | Click "Review" on a request | Review page opens | ⬜ |
| 3 | Click "Reject Request" | Confirmation dialog appears | ⬜ |
| 4 | Confirm rejection | Success message, redirect to pending list | ⬜ |
| 5 | Verify status | Request status = "rejected" | ⬜ |
| 6 | Check in "All Requests" | Appears with rejected badge | ⬜ |

**Result:** ⬜ PASS / ⬜ FAIL  
**Notes:** _____________________________________________

---

### Test 3.3: Stock Dispatch (CRITICAL FIX VERIFICATION)
**Priority:** CRITICAL  
**Module:** Admin - Dispatch System  
**Prerequisite:** Must have approved request  

| Step | Action | Expected Result | Status |
|------|--------|-----------------|--------|
| 1 | Note current commissary inventory levels | Record quantities before dispatch | ⬜ |
| 2 | Note current branch inventory levels | Record quantities before dispatch | ⬜ |
| 3 | Navigate to "Approved Requests" | List displays | ⬜ |
| 4 | Click "Dispatch Stock" | Dispatch page opens | ⬜ |
| 5 | Verify items to dispatch shown | All approved items listed | ⬜ |
| 6 | Click "Confirm Dispatch" | Confirmation dialog appears | ⬜ |
| 7 | Confirm dispatch | Success: Redirect with "dispatched" message | ⬜ |
| 8 | **VERIFY:** Check commissary inventory | Quantities DECREASED by dispatch amounts | ⬜ |
| 9 | **VERIFY:** Check branch inventory | Quantities INCREASED by dispatch amounts | ⬜ |
| 10 | Check requisition status | Status = "dispatched" | ⬜ |
| 11 | Verify stock movements logged | 2 movements: dispatch (commissary) + receive (branch) | ⬜ |
| 12 | Check movement quantities | Previous ≠ New (should be different) | ⬜ |

**Critical Verification:**
- Commissary stock MUST decrease
- Branch stock MUST increase
- Previous and new quantities MUST be different
- Stock movements MUST show correct values

**Result:** ⬜ PASS / ⬜ FAIL  
**Notes:** _____________________________________________

---

### Test 3.4: Commissary Inventory View
**Priority:** MEDIUM  
**Module:** Admin - Inventory Management  

| Step | Action | Expected Result | Status |
|------|--------|-----------------|--------|
| 1 | Navigate to "Commissary Inventory" | Inventory list displays | ⬜ |
| 2 | Verify all materials shown | All active materials listed | ⬜ |
| 3 | Check stock status colors | Low=red, Medium=yellow, Adequate=green | ⬜ |
| 4 | Verify minimum levels shown | Min stock level displayed | ⬜ |
| 5 | Compare with database | Quantities match actual inventory | ⬜ |

**Result:** ⬜ PASS / ⬜ FAIL  
**Notes:** _____________________________________________

---

### Test 3.5: Manual Inventory Adjustment
**Priority:** CRITICAL  
**Module:** Admin - Inventory Adjustment  

| Step | Action | Expected Result | Status |
|------|--------|-----------------|--------|
| 1 | Navigate to "Adjust Inventory" | Adjustment page displays | ⬜ |
| 2 | Note current inventory for test item | Record quantity before adjustment | ⬜ |
| 3 | **ADD:** Select branch, material, type=add | Form accepts input | ⬜ |
| 4 | Enter quantity: 10.00 | Input accepts value | ⬜ |
| 5 | Enter reason: "Testing add adjustment" | Textarea accepts text | ⬜ |
| 6 | Submit adjustment | Success: "Inventory adjusted successfully" | ⬜ |
| 7 | Verify inventory increased | Quantity = previous + 10.00 | ⬜ |
| 8 | Check adjustment history | New entry appears with details | ⬜ |
| 9 | **REMOVE:** Select type=subtract | Form updates | ⬜ |
| 10 | Enter quantity: 10.00, reason | Submit successful | ⬜ |
| 11 | Verify inventory decreased | Quantity back to original | ⬜ |
| 12 | Verify audit trail | Both adjustments logged with user | ⬜ |

**Result:** ⬜ PASS / ⬜ FAIL  
**Notes:** _____________________________________________

---

### Test 3.6: All Requests History
**Priority:** MEDIUM  
**Module:** Admin - Request Management  

| Step | Action | Expected Result | Status |
|------|--------|-----------------|--------|
| 1 | Navigate to "All Requests" | Full history displays | ⬜ |
| 2 | Filter by status: "dispatched" | Only dispatched requests show | ⬜ |
| 3 | Filter by branch | Only selected branch shows | ⬜ |
| 4 | Clear filters | All requests display again | ⬜ |
| 5 | Verify status badges | Color-coded correctly | ⬜ |

**Result:** ⬜ PASS / ⬜ FAIL  
**Notes:** _____________________________________________

---

### Test 3.7: Admin Dashboard
**Priority:** MEDIUM  
**Module:** Admin - Dashboard  

| Step | Action | Expected Result | Status |
|------|--------|-----------------|--------|
| 1 | Navigate to dashboard | Dashboard displays | ⬜ |
| 2 | Verify stat cards | Pending, approved, low stock counts | ⬜ |
| 3 | Check recent requests table | Shows latest requests | ⬜ |
| 4 | Verify navigation menu | All admin options available | ⬜ |
| 5 | Test quick action links | Links work correctly | ⬜ |

**Result:** ⬜ PASS / ⬜ FAIL  
**Notes:** _____________________________________________

---

## TEST SECTION 4: BRANCH USER MODULE

### Test 4.1: Stock Requisition Creation
**Priority:** CRITICAL  
**Module:** Branch - Stock Request  
**Login as:** clarin_user / admin123  

| Step | Action | Expected Result | Status |
|------|--------|-----------------|--------|
| 1 | Navigate to "Request Stock" | Request form displays | ⬜ |
| 2 | Verify current inventory shown | Branch stock levels visible | ⬜ |
| 3 | Select multiple materials | Materials selectable | ⬜ |
| 4 | Enter quantities for 3+ items | Inputs accept numbers | ⬜ |
| 5 | Add notes: "Test requisition for QA" | Textarea accepts text | ⬜ |
| 6 | Submit without items (qty=0) | Error: "Add at least one item" | ⬜ |
| 7 | Enter valid quantities | Form validates | ⬜ |
| 8 | Submit request | Success with requisition number | ⬜ |
| 9 | Verify auto-generated number | Format: REQ-YYYYMMDD-#### | ⬜ |
| 10 | Check in "View Requests" | New request appears | ⬜ |

**Result:** ⬜ PASS / ⬜ FAIL  
**Notes:** _____________________________________________

---

### Test 4.2: Physical Stock Count Entry
**Priority:** CRITICAL  
**Module:** Branch - Stock Count  

| Step | Action | Expected Result | Status |
|------|--------|-----------------|--------|
| 1 | Navigate to "Physical Stock Count" | Count form displays | ⬜ |
| 2 | Verify count date (default today) | Date field shows current date | ⬜ |
| 3 | Verify system quantities shown | All materials with current quantities | ⬜ |
| 4 | Enter physical counts (vary from system) | Inputs accept numbers | ⬜ |
| 5 | Add notes: "Test count with variances" | Textarea accepts text | ⬜ |
| 6 | Submit count | Success: "Stock count recorded" | ⬜ |
| 7 | Verify variances calculated | System automatically calculates differences | ⬜ |
| 8 | Check database: physical_stock_counts | New records created | ⬜ |
| 9 | Try duplicate count (same date) | Updates existing instead of error | ⬜ |

**Test Data Example:**
```
Material: Whole Chicken
System Quantity: 50.00
Physical Count: 45.00 (shortage of 5)
```

**Result:** ⬜ PASS / ⬜ FAIL  
**Notes:** _____________________________________________

---

### Test 4.3: Request History & Details
**Priority:** MEDIUM  
**Module:** Branch - Request Management  

| Step | Action | Expected Result | Status |
|------|--------|-----------------|--------|
| 1 | Navigate to "View Requests" | Request list displays | ⬜ |
| 2 | Verify all branch requests shown | Only this branch's requests | ⬜ |
| 3 | Check status badges | Color-coded correctly | ⬜ |
| 4 | Click "View Details" on a request | Details page opens | ⬜ |
| 5 | Verify complete information shown | All fields populated | ⬜ |
| 6 | Check item breakdown | All requested items listed | ⬜ |
| 7 | Verify approved vs requested | Shows adjustments if any | ⬜ |
| 8 | Check dispatch information | Shows dispatch date if dispatched | ⬜ |

**Result:** ⬜ PASS / ⬜ FAIL  
**Notes:** _____________________________________________

---

### Test 4.4: Branch Dashboard
**Priority:** MEDIUM  
**Module:** Branch - Dashboard  

| Step | Action | Expected Result | Status |
|------|--------|-----------------|--------|
| 1 | Navigate to dashboard | Dashboard displays | ⬜ |
| 2 | Verify branch name shown | Correct branch displayed | ⬜ |
| 3 | Check stat cards | Pending requests, low stock counts | ⬜ |
| 4 | Verify quick action buttons | Links work correctly | ⬜ |
| 5 | Check recent requests table | Shows latest requests | ⬜ |
| 6 | Test all navigation links | All branch options accessible | ⬜ |

**Result:** ⬜ PASS / ⬜ FAIL  
**Notes:** _____________________________________________

---

## TEST SECTION 5: USER INTERFACE & UX

### Test 5.1: Responsive Design
**Priority:** MEDIUM  
**Module:** UI/UX  

| Step | Action | Expected Result | Status |
|------|--------|-----------------|--------|
| 1 | View on desktop (1920x1080) | Proper layout, no overflow | ⬜ |
| 2 | View on tablet (768x1024) | Responsive adjustments | ⬜ |
| 3 | View on mobile (375x667) | Mobile-friendly layout | ⬜ |
| 4 | Test navigation on mobile | Sidebar accessible | ⬜ |
| 5 | Test forms on mobile | Inputs properly sized | ⬜ |

**Result:** ⬜ PASS / ⬜ FAIL  
**Notes:** _____________________________________________

---

### Test 5.2: Alert Messages
**Priority:** LOW  
**Module:** UI/UX  

| Step | Action | Expected Result | Status |
|------|--------|-----------------|--------|
| 1 | Trigger success message | Green alert displays | ⬜ |
| 2 | Wait 5 seconds | Alert auto-hides | ⬜ |
| 3 | Trigger error message | Red alert displays | ⬜ |
| 4 | Trigger warning message | Yellow alert displays | ⬜ |
| 5 | Trigger info message | Blue alert displays | ⬜ |

**Result:** ⬜ PASS / ⬜ FAIL  
**Notes:** _____________________________________________

---

### Test 5.3: Confirmation Dialogs
**Priority:** MEDIUM  
**Module:** UI/UX - JavaScript  

| Step | Action | Expected Result | Status |
|------|--------|-----------------|--------|
| 1 | Attempt dispatch | SweetAlert2 confirmation appears | ⬜ |
| 2 | Click Cancel | Action cancelled, stays on page | ⬜ |
| 3 | Attempt dispatch again | Confirmation appears | ⬜ |
| 4 | Click Confirm | Action proceeds | ⬜ |
| 5 | Try deactivate user | Confirmation dialog works | ⬜ |

**Result:** ⬜ PASS / ⬜ FAIL  
**Notes:** _____________________________________________

---

### Test 5.4: Form Validation
**Priority:** MEDIUM  
**Module:** UI/UX - Forms  

| Step | Action | Expected Result | Status |
|------|--------|-----------------|--------|
| 1 | Submit form with empty required fields | Browser validation message | ⬜ |
| 2 | Enter negative number in quantity | Prevented or validation error | ⬜ |
| 3 | Enter text in number field | Browser validation prevents | ⬜ |
| 4 | Test date field | Only allows valid dates | ⬜ |
| 5 | Test email field | Validates email format | ⬜ |

**Result:** ⬜ PASS / ⬜ FAIL  
**Notes:** _____________________________________________

---

### Test 5.5: Navigation & Links
**Priority:** MEDIUM  
**Module:** UI/UX - Navigation  

| Step | Action | Expected Result | Status |
|------|--------|-----------------|--------|
| 1 | Click all sidebar menu items | All links work | ⬜ |
| 2 | Verify active page highlighted | Current page styled differently | ⬜ |
| 3 | Test breadcrumb links | Navigate correctly | ⬜ |
| 4 | Test back buttons | Return to previous page | ⬜ |
| 5 | Test logo click | Returns to dashboard | ⬜ |

**Result:** ⬜ PASS / ⬜ FAIL  
**Notes:** _____________________________________________

---

## TEST SECTION 6: DATABASE INTEGRITY

### Test 6.1: Foreign Key Relationships
**Priority:** HIGH  
**Module:** Database  

| Step | Action | Expected Result | Status |
|------|--------|-----------------|--------|
| 1 | Try to delete branch with users | Should fail or cascade properly | ⬜ |
| 2 | Try to delete user with requisitions | Should set NULL or cascade | ⬜ |
| 3 | Try to delete material with inventory | Should prevent or cascade | ⬜ |
| 4 | Verify referential integrity | All FKs working correctly | ⬜ |

**Result:** ⬜ PASS / ⬜ FAIL  
**Notes:** _____________________________________________

---

### Test 6.2: Data Validation
**Priority:** HIGH  
**Module:** Database  

| Step | Action | Expected Result | Status |
|------|--------|-----------------|--------|
| 1 | Check for NULL values in required fields | No NULLs in NOT NULL columns | ⬜ |
| 2 | Verify UNIQUE constraints | Duplicate codes/usernames prevented | ⬜ |
| 3 | Check ENUM values | Only valid status values exist | ⬜ |
| 4 | Verify timestamp fields | All records have timestamps | ⬜ |

**Result:** ⬜ PASS / ⬜ FAIL  
**Notes:** _____________________________________________

---

### Test 6.3: Calculated Fields
**Priority:** MEDIUM  
**Module:** Database  

| Step | Action | Expected Result | Status |
|------|--------|-----------------|--------|
| 1 | Check variance calculation | variance = physical - system | ⬜ |
| 2 | Verify generated columns | Automatically computed correctly | ⬜ |
| 3 | Test view queries | Views return correct data | ⬜ |

**Result:** ⬜ PASS / ⬜ FAIL  
**Notes:** _____________________________________________

---

## TEST SECTION 7: SECURITY TESTING

### Test 7.1: SQL Injection Prevention
**Priority:** CRITICAL  
**Module:** Security  

| Step | Action | Expected Result | Status |
|------|--------|-----------------|--------|
| 1 | Enter SQL in username: `' OR '1'='1` | Login fails, no SQL execution | ⬜ |
| 2 | Enter SQL in search: `'; DROP TABLE users;--` | Query fails safely, no execution | ⬜ |
| 3 | Test all input fields | All use prepared statements | ⬜ |

**Result:** ⬜ PASS / ⬜ FAIL  
**Notes:** _____________________________________________

---

### Test 7.2: XSS Prevention
**Priority:** HIGH  
**Module:** Security  

| Step | Action | Expected Result | Status |
|------|--------|-----------------|--------|
| 1 | Enter script in username: `<script>alert('XSS')</script>` | Encoded/escaped, no alert | ⬜ |
| 2 | Enter HTML in notes: `<b>Test</b>` | Displayed as text, not rendered | ⬜ |
| 3 | Check all output fields | All properly escaped | ⬜ |

**Result:** ⬜ PASS / ⬜ FAIL  
**Notes:** _____________________________________________

---

### Test 7.3: Password Security
**Priority:** HIGH  
**Module:** Security  

| Step | Action | Expected Result | Status |
|------|--------|-----------------|--------|
| 1 | Check database: users table | Passwords are hashed (60+ chars) | ⬜ |
| 2 | Verify hash format | Starts with $2y$ (bcrypt) | ⬜ |
| 3 | Change password | New hash different from old | ⬜ |
| 4 | Test password verification | Correct password accepts | ⬜ |

**Result:** ⬜ PASS / ⬜ FAIL  
**Notes:** _____________________________________________

---

## TEST SECTION 8: END-TO-END WORKFLOWS

### Test 8.1: Complete Requisition Lifecycle
**Priority:** CRITICAL  
**Module:** Complete Workflow  

| Step | Action | Result | Status |
|------|--------|--------|--------|
| 1 | **Branch:** Login as branch user | Success | ⬜ |
| 2 | **Branch:** Create stock request | Request submitted | ⬜ |
| 3 | **Branch:** Verify in request history | Appears as "pending" | ⬜ |
| 4 | **Admin:** Login as admin | Success | ⬜ |
| 5 | **Admin:** View pending request | Request visible | ⬜ |
| 6 | **Admin:** Review and approve | Status = "approved" | ⬜ |
| 7 | **Admin:** Dispatch stock | Inventory updated correctly | ⬜ |
| 8 | **Admin:** Verify stock movements | 2 movements logged | ⬜ |
| 9 | **Branch:** Login and check inventory | Stock increased | ⬜ |
| 10 | **Branch:** View request details | Status = "dispatched" | ⬜ |
| 11 | **Verify:** Commissary inventory | Decreased by dispatch amount | ⬜ |
| 12 | **Verify:** Branch inventory | Increased by dispatch amount | ⬜ |

**Result:** ⬜ PASS / ⬜ FAIL  
**Notes:** _____________________________________________

---

### Test 8.2: Variance Detection Workflow
**Priority:** CRITICAL  
**Module:** Complete Workflow  

| Step | Action | Result | Status |
|------|--------|--------|--------|
| 1 | **Branch:** Login as branch user | Success | ⬜ |
| 2 | **Branch:** Note system quantities | Record 3-5 items | ⬜ |
| 3 | **Branch:** Enter physical count | Create shortage (qty < system) | ⬜ |
| 4 | **Branch:** Submit count | Success | ⬜ |
| 5 | **Superadmin:** Login as superadmin | Success | ⬜ |
| 6 | **Superadmin:** Open variance report | Report displays | ⬜ |
| 7 | **Superadmin:** Filter by branch | Test branch shown | ⬜ |
| 8 | **Verify:** Shortage highlighted | Negative variance in red | ⬜ |
| 9 | **Verify:** Totals calculated | Total shortage amount shown | ⬜ |
| 10 | **Verify:** Material details | All info displayed correctly | ⬜ |

**Result:** ⬜ PASS / ⬜ FAIL  
**Notes:** _____________________________________________

---

### Test 8.3: User Lifecycle
**Priority:** HIGH  
**Module:** Complete Workflow  

| Step | Action | Result | Status |
|------|--------|--------|--------|
| 1 | **Superadmin:** Create new user | User created | ⬜ |
| 2 | **New User:** Login with credentials | Success | ⬜ |
| 3 | **New User:** Change password | Password changed | ⬜ |
| 4 | **New User:** Perform role activities | Works correctly | ⬜ |
| 5 | **Superadmin:** Deactivate user | Status = inactive | ⬜ |
| 6 | **New User:** Try to login | Login fails | ⬜ |
| 7 | **Superadmin:** Reactivate user | Status = active | ⬜ |
| 8 | **New User:** Login again | Success | ⬜ |

**Result:** ⬜ PASS / ⬜ FAIL  
**Notes:** _____________________________________________

---

## TEST SECTION 9: PERFORMANCE & LOAD

### Test 9.1: Page Load Times
**Priority:** LOW  
**Module:** Performance  

| Page | Expected Load Time | Actual | Status |
|------|-------------------|--------|--------|
| Login page | < 2 seconds | _____ | ⬜ |
| Dashboard | < 3 seconds | _____ | ⬜ |
| Inventory list | < 3 seconds | _____ | ⬜ |
| Variance report | < 5 seconds | _____ | ⬜ |

**Result:** ⬜ PASS / ⬜ FAIL  
**Notes:** _____________________________________________

---

### Test 9.2: Large Data Sets
**Priority:** LOW  
**Module:** Performance  

| Step | Action | Expected Result | Status |
|------|--------|-----------------|--------|
| 1 | Load page with 100+ requisitions | Loads without timeout | ⬜ |
| 2 | Filter large data set | Filters quickly (< 2 sec) | ⬜ |
| 3 | Sort large table | Sorts without lag | ⬜ |

**Result:** ⬜ PASS / ⬜ FAIL  
**Notes:** _____________________________________________

---

## TEST SECTION 10: BROWSER COMPATIBILITY

### Test 10.1: Cross-Browser Testing
**Priority:** MEDIUM  
**Module:** Compatibility  

| Browser | Version | Login | Forms | Reports | Overall |
|---------|---------|-------|-------|---------|---------|
| Chrome | Latest | ⬜ | ⬜ | ⬜ | ⬜ |
| Firefox | Latest | ⬜ | ⬜ | ⬜ | ⬜ |
| Edge | Latest | ⬜ | ⬜ | ⬜ | ⬜ |
| Safari | Latest | ⬜ | ⬜ | ⬜ | ⬜ |

**Result:** ⬜ PASS / ⬜ FAIL  
**Notes:** _____________________________________________

---

## 📊 TEST SUMMARY

### Completion Tracker

| Section | Tests | Passed | Failed | % |
|---------|-------|--------|--------|---|
| 1. Authentication | 4 | ___ | ___ | ___ |
| 2. Superadmin | 6 | ___ | ___ | ___ |
| 3. Admin | 7 | ___ | ___ | ___ |
| 4. Branch User | 4 | ___ | ___ | ___ |
| 5. UI/UX | 5 | ___ | ___ | ___ |
| 6. Database | 3 | ___ | ___ | ___ |
| 7. Security | 3 | ___ | ___ | ___ |
| 8. E2E Workflows | 3 | ___ | ___ | ___ |
| 9. Performance | 2 | ___ | ___ | ___ |
| 10. Compatibility | 1 | ___ | ___ | ___ |
| **TOTAL** | **38** | **___** | **___** | **___** |

---

## 🐛 BUG TRACKING

### Critical Issues
| # | Issue | Module | Status | Notes |
|---|-------|--------|--------|-------|
| 1 | | | | |
| 2 | | | | |

### High Priority Issues
| # | Issue | Module | Status | Notes |
|---|-------|--------|--------|-------|
| 1 | | | | |

### Medium/Low Issues
| # | Issue | Module | Status | Notes |
|---|-------|--------|--------|-------|
| 1 | | | | |

---

## ✅ ACCEPTANCE CRITERIA

### Must Pass (Critical)
- [ ] All authentication tests pass
- [ ] All role-based access tests pass
- [ ] Stock requisition workflow complete
- [ ] Dispatch updates inventory correctly ⚠️ FIXED
- [ ] Variance report displays correctly
- [ ] Database integrity maintained
- [ ] Security tests pass

### Should Pass (High Priority)
- [ ] All CRUD operations work
- [ ] All dashboards display correctly
- [ ] All reports generate correctly
- [ ] UI/UX tests pass
- [ ] Forms validate properly

### Nice to Have (Medium/Low)
- [ ] Performance benchmarks met
- [ ] All browsers compatible
- [ ] Mobile responsive
- [ ] Auto-hide alerts work

---

## 📝 FINAL SIGN-OFF

**Test Conducted By:** _______________________  
**Date:** _______________________  
**Environment:** Local XAMPP  
**Database Version:** _______________________  

**Overall Result:** ⬜ PASS / ⬜ FAIL  

**Recommendation:**
- [ ] Ready for production
- [ ] Ready with minor fixes
- [ ] Requires major fixes
- [ ] Not ready for production

**Comments:**
_______________________________________________
_______________________________________________
_______________________________________________

---

## 🎯 QUICK TEST CHECKLIST

For rapid verification, use this checklist:

**Critical Features (Must Work):**
- [ ] Login/Logout
- [ ] Role permissions enforced
- [ ] Branch creates requisition
- [ ] Admin approves requisition
- [ ] Admin dispatches stock
- [ ] **Inventory updates after dispatch** ⚠️
- [ ] Variance report shows data
- [ ] Manual adjustments work

**Essential Features:**
- [ ] User management (add/edit)
- [ ] Branch management (add/edit)
- [ ] Material management (add/edit)
- [ ] Physical count entry
- [ ] Password change
- [ ] Request history
- [ ] All dashboards load

**System Health:**
- [ ] No PHP errors
- [ ] No database errors
- [ ] No JavaScript console errors
- [ ] All pages accessible
- [ ] All forms submit
- [ ] All navigation works

---

**System Status:** Ready for comprehensive testing  
**Last Updated:** February 15, 2026  
**Version:** 2.0  

**Happy Testing! 🧪✨**
