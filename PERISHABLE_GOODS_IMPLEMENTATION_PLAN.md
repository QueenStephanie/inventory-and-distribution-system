# 🍗 Perishable Goods & Wastage Tracking Implementation Plan

**Project:** Enhancement for Food Business (Perishable Goods Management)  
**Priority:** CRITICAL - Essential for accurate food inventory management  
**Timeline:** 6-8 weeks (3-4 sprints)  
**Status:** 🔴 Not Started

---

## 📋 Executive Summary

This plan addresses critical gaps in tracking perishable inventory (raw chicken, oil, breading) by implementing:

- Supplier/procurement tracking (inbound)
- Batch number & expiry date management (FIFO)
- Wastage/spoilage categorization
- Enhanced variance reporting with loss reasons

**Business Impact:** Enables accurate tracking of food spoilage, damage, and expiration to calculate true profit and reduce losses.

---

## 🎯 Core Business Logic Enhancement

### Current State → Target State

| Process Step    | Current State       | Target State                                   |
| --------------- | ------------------- | ---------------------------------------------- |
| **Procurement** | ❌ Not tracked      | ✅ Supplier orders with batch/expiry tracking  |
| **Requisition** | ✅ Implemented      | ✅ Enhanced with FIFO recommendations          |
| **Fulfillment** | ✅ Implemented      | ✅ Enhanced with batch assignment              |
| **Receiving**   | ❌ No batch logging | ✅ Requires batch number & delivery date entry |
| **Consumption** | ⚠️ Basic variance   | ✅ Categorized wastage (Expired/Damaged/Sales) |
| **Reporting**   | ⚠️ Generic variance | ✅ Detailed wastage breakdown by category      |

---

## 🗂️ Database Schema Changes

### New Tables Required

#### 1. **suppliers** table

```sql
CREATE TABLE suppliers (
    supplier_id INT PRIMARY KEY AUTO_INCREMENT,
    supplier_code VARCHAR(50) NOT NULL UNIQUE,
    supplier_name VARCHAR(100) NOT NULL,
    contact_person VARCHAR(100),
    phone VARCHAR(20),
    email VARCHAR(100),
    address TEXT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;
```

#### 2. **procurement_orders** table

```sql
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
    FOREIGN KEY (supplier_id) REFERENCES suppliers(supplier_id),
    FOREIGN KEY (ordered_by) REFERENCES users(user_id),
    FOREIGN KEY (received_by) REFERENCES users(user_id)
) ENGINE=InnoDB;
```

#### 3. **procurement_order_items** table

```sql
CREATE TABLE procurement_order_items (
    item_id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    material_id INT NOT NULL,
    ordered_quantity DECIMAL(10,2) NOT NULL,
    received_quantity DECIMAL(10,2) DEFAULT 0,
    unit_cost DECIMAL(10,2),
    batch_number VARCHAR(100),
    manufacturing_date DATE,
    expiry_date DATE,
    notes TEXT,
    FOREIGN KEY (order_id) REFERENCES procurement_orders(order_id) ON DELETE CASCADE,
    FOREIGN KEY (material_id) REFERENCES raw_materials(material_id)
) ENGINE=InnoDB;
```

#### 4. **inventory_batches** table

```sql
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
    status ENUM('active', 'depleted', 'expired') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (branch_id) REFERENCES branches(branch_id) ON DELETE CASCADE,
    FOREIGN KEY (material_id) REFERENCES raw_materials(material_id) ON DELETE CASCADE,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(supplier_id) ON DELETE SET NULL,
    FOREIGN KEY (procurement_order_id) REFERENCES procurement_orders(order_id) ON DELETE SET NULL,
    UNIQUE KEY unique_batch (branch_id, material_id, batch_number, delivery_date)
) ENGINE=InnoDB;
```

#### 5. **wastage_records** table (CRITICAL)

```sql
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
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (branch_id) REFERENCES branches(branch_id) ON DELETE CASCADE,
    FOREIGN KEY (material_id) REFERENCES raw_materials(material_id) ON DELETE CASCADE,
    FOREIGN KEY (batch_id) REFERENCES inventory_batches(batch_id) ON DELETE SET NULL,
    FOREIGN KEY (recorded_by) REFERENCES users(user_id)
) ENGINE=InnoDB;
```

#### 6. **variance_reasons** table

```sql
CREATE TABLE variance_reasons (
    reason_id INT PRIMARY KEY AUTO_INCREMENT,
    count_id INT NOT NULL,
    material_id INT NOT NULL,
    variance_category ENUM('sales', 'expired', 'damaged', 'theft', 'error', 'other') NOT NULL,
    quantity DECIMAL(10,2) NOT NULL,
    explanation TEXT,
    FOREIGN KEY (count_id) REFERENCES physical_stock_counts(count_id) ON DELETE CASCADE,
    FOREIGN KEY (material_id) REFERENCES raw_materials(material_id) ON DELETE CASCADE
) ENGINE=InnoDB;
```

### Modified Tables

#### Update **inventory** table

```sql
ALTER TABLE inventory
ADD COLUMN total_batches INT DEFAULT 0,
ADD COLUMN oldest_expiry_date DATE NULL,
ADD COLUMN near_expiry_count INT DEFAULT 0;
```

#### Update **requisition_items** table

```sql
ALTER TABLE requisition_items
ADD COLUMN batch_id INT NULL,
ADD COLUMN batch_number VARCHAR(100) NULL,
ADD COLUMN expiry_date DATE NULL,
ADD CONSTRAINT fk_batch FOREIGN KEY (batch_id) REFERENCES inventory_batches(batch_id);
```

#### Update **stock_movements** table

```sql
ALTER TABLE stock_movements
ADD COLUMN batch_id INT NULL,
ADD COLUMN wastage_id INT NULL,
ADD CONSTRAINT fk_movement_batch FOREIGN KEY (batch_id) REFERENCES inventory_batches(batch_id),
ADD CONSTRAINT fk_movement_wastage FOREIGN KEY (wastage_id) REFERENCES wastage_records(wastage_id);
```

---

## 📦 Implementation Phases

### **PHASE 1: Database Foundation** (Week 1)

**Goal:** Create all database structures

#### Tasks:

1. ✅ Create migration script for new tables
2. ✅ Create migration script for table modifications
3. ✅ Add indexes for performance
4. ✅ Create database views for reporting
5. ✅ Write rollback scripts
6. ✅ Test on development database

**Deliverables:**

- `database/migrations/001_perishable_goods_schema.sql`
- `database/migrations/001_rollback.sql`
- Migration documentation

**Testing Checklist:**

- [ ] All tables created successfully
- [ ] Foreign keys working correctly
- [ ] Indexes created
- [ ] Sample data insertion works
- [ ] Rollback tested

---

### **PHASE 2: Supplier Module** (Week 1-2)

**Goal:** Enable supplier management and procurement tracking

#### Tasks:

##### 2.1 Supplier Management (Admin)

- ✅ Create `admin/manage_suppliers.php`
  - Add/Edit/Deactivate suppliers
  - Supplier listing with search
  - Contact information management
- ✅ Update admin dashboard with supplier count
- ✅ Add supplier menu item to admin sidebar

##### 2.2 Procurement Order System (Admin)

- ✅ Create `admin/create_procurement_order.php`
  - Select supplier
  - Add multiple materials
  - Set expected delivery date
  - Calculate total cost
- ✅ Create `admin/procurement_orders.php`
  - List all orders
  - Filter by status/supplier/date
  - View order details
- ✅ Create `admin/receive_procurement.php`
  - Mark items as received
  - Input actual quantities
  - Record batch numbers & expiry dates
  - Update commissary inventory
  - Create inventory batches

**Deliverables:**

- Supplier CRUD interface
- Procurement order creation
- Receiving interface with batch entry
- Order tracking dashboard

---

### **PHASE 3: FIFO & Batch Tracking** (Week 2-3)

**Goal:** Track inventory by batch with expiry dates

#### Tasks:

##### 3.1 Batch Management

- ✅ Create `admin/batch_inventory.php`
  - View all batches by branch
  - See expiry dates
  - Track quantities per batch
  - FIFO recommendations
- ✅ Create batch helper functions
  - `getOldestBatch($branchId, $materialId)`
  - `checkExpiringBatches($days = 7)`
  - `depleteFromBatch($batchId, $quantity)`

##### 3.2 Enhanced Dispatch with FIFO

- ✅ Modify `admin/dispatch_request.php`
  - Automatically assign oldest batch
  - Show expiry dates during dispatch
  - Log batch_id in requisition_items
- ✅ Update stock_movements to track batch_id

##### 3.3 Branch Receiving Enhancement

- ✅ Create `branch/receive_stock.php`
  - View dispatched items
  - Confirm receipt
  - Log delivery date & batch (if new batch)
  - Update branch inventory batches

##### 3.4 Expiry Alerts

- ✅ Create `admin/expiry_alerts.php`
  - Dashboard widget for items expiring soon (7 days)
  - Color-coded alerts (red: expired, orange: <3 days, yellow: <7 days)
  - Filter by branch

**Deliverables:**

- Batch tracking interface
- FIFO dispatch logic
- Expiry alert system
- Batch-aware stock movements

---

### **PHASE 4: Wastage & Spoilage Module** (Week 3-4)

**Goal:** Enable categorized wastage tracking

#### Tasks:

##### 4.1 Wastage Entry Interface (Branch & Admin)

- ✅ Create `branch/record_wastage.php`
  - Select material
  - Select batch (if applicable)
  - Choose category: Expired / Damaged / Spoiled / Staff Meal / Other
  - Enter quantity
  - Provide detailed reason
  - Estimated cost calculation
- ✅ Create `admin/record_wastage.php` (for commissary)
- ✅ Auto-deduct from inventory
- ✅ Create stock movement with wastage_id

##### 4.2 Wastage Dashboard

- ✅ Create `branch/wastage_log.php`
  - View all wastage entries
  - Filter by date/category
  - Export to Excel
- ✅ Create `admin/commissary_wastage.php`

##### 4.3 Wastage Validation

- ✅ Photo upload support (optional)
- ✅ Auto-mark batches as expired if past expiry date
- ✅ Validation rules (can't waste more than current quantity)

**Deliverables:**

- Wastage entry forms (Branch & Admin)
- Wastage log viewer
- Automatic inventory updates
- Photo upload capability

---

### **PHASE 5: Enhanced Variance Tracking** (Week 4-5)

**Goal:** Replace generic variance with categorized reasons

#### Tasks:

##### 5.1 Enhanced Stock Count

- ✅ Modify `branch/stock_count.php`
  - When variance detected, prompt for category
  - For each material with variance:
    - Show variance amount
    - Require category selection: Sales / Expired / Damaged / Theft / Error / Other
    - Require explanation
  - Save to variance_reasons table
- ✅ Allow breaking down variance across multiple categories
  - Example: -10kg variance = -6kg (sales) + -3kg (expired) + -1kg (damaged)

##### 5.2 Variance Reason Modal (JavaScript)

```javascript
// When variance != 0, show modal:
// "This material has a variance of -10.5 kg. Please categorize:"
// - Sales: [input]
// - Expired: [input]
// - Damaged: [input]
// - Theft: [input]
// - Error: [input]
// - Other: [input]
// Total must equal variance amount
```

**Deliverables:**

- Interactive variance categorization
- Variance reasons database logging
- Validation (total = variance)

---

### **PHASE 6: Superadmin Reporting** (Week 5-6)

**Goal:** Comprehensive wastage & loss reporting

#### Tasks:

##### 6.1 Enhanced Variance Report

- ✅ Modify `superadmin/variance_report.php`
  - Add breakdown by category (Sales, Expired, Damaged, etc.)
  - Show percentage of each category
  - Color-coded by severity
  - Branch comparison
  - Material comparison
- ✅ Add cost impact calculations

##### 6.2 Wastage Analysis Report

- ✅ Create `superadmin/wastage_analysis.php`
  - Total wastage by branch
  - Total wastage by category
  - Trend analysis (daily/weekly/monthly)
  - Cost of wastage
  - Top 10 most wasted materials
  - Wastage rate (wastage / total inventory)
- ✅ Charts & visualizations
- ✅ Export to PDF & Excel

##### 6.3 Expiry Loss Report

- ✅ Create `superadmin/expiry_loss_report.php`
  - Items that expired before use
  - Cost of expired inventory
  - Branch-wise expiry rates
  - Materials with frequent expiry

##### 6.4 Dashboard Enhancements

- ✅ Add to Superadmin dashboard:
  - Total wastage cost (current month)
  - Most wastage branch
  - Critical expiry alerts
  - Wastage trend chart

**Deliverables:**

- Enhanced variance report with categories
- Wastage analysis dashboard
- Expiry loss tracking
- Executive summary widgets

---

### **PHASE 7: Notifications & Automation** (Week 6)

**Goal:** Proactive alerts for wastage prevention

#### Tasks:

##### 7.1 Email Alerts (if email system ready)

- ✅ Daily expiry alert email to Admin
- ✅ Weekly wastage summary to Superadmin
- ✅ High-value wastage instant alert (>threshold)

##### 7.2 In-System Notifications

- ✅ Dashboard badges for alerts
- ✅ Banner alerts for expiring inventory
- ✅ Low-stock with expiry consideration

##### 7.3 Scheduled Tasks

- ✅ Create `utilities/check_expiries.php` (cron job)
  - Auto-mark expired batches
  - Generate daily report
  - Send alerts
- ✅ Create `utilities/wastage_reminder.php`
  - Remind branches to record wastage daily

**Deliverables:**

- Alert system
- Scheduled tasks for automation
- Email templates

---

### **PHASE 8: Testing & Documentation** (Week 6)

**Goal:** Ensure quality and usability

#### Tasks:

##### 8.1 Integration Testing

- ✅ Test complete workflow:
  1. Procurement → Receiving → Batch creation
  2. Branch request → Dispatch → FIFO allocation
  3. Branch usage → Physical count → Variance categorization
  4. Wastage entry → Inventory update → Reporting
- ✅ Test edge cases:
  - Expired inventory dispatch prevention
  - Negative inventory prevention
  - Variance total validation
  - FIFO logic accuracy

##### 8.2 User Acceptance Testing

- ✅ Create test scenarios document
- ✅ Train users on new features
- ✅ Gather feedback
- ✅ Fix issues

##### 8.3 Documentation

- ✅ Update `COMPLETE_SYSTEM_DOCUMENTATION.md`
- ✅ Create `USER_GUIDE_PERISHABLE_GOODS.md`
- ✅ Create `ADMIN_GUIDE_WASTAGE_TRACKING.md`
- ✅ Update database schema documentation
- ✅ Create video tutorials (optional)

**Deliverables:**

- Test report with results
- User guides
- Updated system documentation
- Training materials

---

## 🎯 Success Metrics

### Key Performance Indicators (KPIs)

After implementation, track:

1. **Wastage Visibility**
   - [ ] 100% of variances categorized (no more generic "notes")
   - [ ] 100% of expiring items flagged 7 days in advance
   - [ ] 90% reduction in expired inventory

2. **Cost Impact**
   - [ ] Accurate wastage cost calculation
   - [ ] Measure monthly wastage reduction
   - [ ] ROI on implementation (reduced losses)

3. **User Adoption**
   - [ ] 100% of branches recording wastage daily
   - [ ] 100% of physical counts with categorized variances
   - [ ] Superadmin reviewing wastage reports weekly

4. **Data Quality**
   - [ ] All batches have expiry dates
   - [ ] FIFO compliance rate >95%
   - [ ] Zero inventory discrepancies at month-end

---

## 📁 File Structure

New files to be created:

```
inventory and distribution system/
├── database/
│   └── migrations/
│       ├── 001_perishable_goods_schema.sql
│       └── 001_rollback.sql
├── admin/
│   ├── manage_suppliers.php
│   ├── create_procurement_order.php
│   ├── procurement_orders.php
│   ├── receive_procurement.php
│   ├── batch_inventory.php
│   ├── expiry_alerts.php
│   ├── commissary_wastage.php
│   └── record_wastage.php (commissary version)
├── branch/
│   ├── receive_stock.php
│   ├── record_wastage.php
│   └── wastage_log.php
├── superadmin/
│   ├── wastage_analysis.php
│   └── expiry_loss_report.php
├── utilities/
│   ├── check_expiries.php (cron job)
│   ├── wastage_reminder.php (cron job)
│   └── fifo_helper.php
├── includes/
│   └── wastage_functions.php
└── docs/
    ├── USER_GUIDE_PERISHABLE_GOODS.md
    └── ADMIN_GUIDE_WASTAGE_TRACKING.md
```

Modified files:

```
- branch/stock_count.php (add variance categorization)
- admin/dispatch_request.php (add FIFO batch assignment)
- superadmin/variance_report.php (add category breakdown)
- includes/header.php (add menu items)
- database/schema.sql (update documentation)
```

---

## 🚨 Critical Implementation Notes

### Must-Have Features

1. **Batch Number Entry is Mandatory** at receiving stage
2. **Expiry Date is Mandatory** for perishable materials (chicken, meat)
3. **Variance Categorization is Mandatory** - cannot submit count without categorizing variance
4. **FIFO Must Be Enforced** - system should not allow newer batch dispatch if older exists
5. **Wastage Requires Approval** for quantities > threshold (e.g., >10kg)

### Data Migration

If you have existing inventory:

1. Create "Initial Inventory" batch for all current stock
2. Set delivery_date = system implementation date
3. Set expiry_date = NULL (or estimate)
4. Mark as `batch_number = 'INITIAL-2026'`

### Performance Considerations

1. **Indexes Required:**
   - `inventory_batches.expiry_date`
   - `wastage_records.wastage_date`
   - `wastage_records.wastage_category`
   - `inventory_batches.status`

2. **Archiving Strategy:**
   - Archive depleted batches older than 6 months
   - Archive wastage records older than 12 months

---

## 🔐 Security Considerations

1. **Wastage Entry Permissions:**
   - Branch users: Can only record wastage for their branch
   - Admin: Can record for commissary
   - Superadmin: View-only for reports

2. **Audit Trail:**
   - All wastage entries logged with user_id and timestamp
   - Cannot delete wastage records (only superadmin can mark as "void")

3. **Approval Workflow:**
   - High-value wastage (e.g., >$500) requires superadmin approval
   - Auto-email on large wastage entries

---

## 💰 Cost-Benefit Analysis

### Implementation Cost

- Development Time: 240 hours (6 weeks × 40 hours)
- Testing: 40 hours
- Training: 10 hours
- **Total: 290 hours**

### Expected Benefits

- **Reduce Expired Inventory Loss:** 30-50% reduction = $5,000-$10,000/month
- **Accurate Profit Calculation:** Know true COGS
- **Theft Detection:** Identify suspicious patterns
- **Better Purchasing:** Order based on expiry tracking
- **Compliance:** Meet food safety audit requirements

**ROI:** Payback in 1-2 months

---

## 📊 Reporting Examples

### Sample Wastage Report Output

```
WASTAGE ANALYSIS REPORT
Period: February 1-22, 2026

Total Wastage: 245.5 kg | Estimated Cost: $2,456.00

By Category:
  Expired:      85.0 kg (34.6%) - $850.00
  Damaged:      67.5 kg (27.5%) - $675.00
  Spoiled:      48.0 kg (19.6%) - $480.00
  Staff Meal:   30.0 kg (12.2%) - $300.00
  Other:        15.0 kg (6.1%)  - $151.00

By Material:
  1. Chicken Thighs:  120.0 kg - $1,200.00 (48.9%)
  2. Breading Mix:     65.5 kg - $655.00 (26.7%)
  3. Cooking Oil:      45.0 L  - $450.00 (18.3%)
  4. Spices:           15.0 kg - $151.00 (6.1%)

By Branch:
  1. Downtown Branch: 98.0 kg - $980.00 (39.9%)
  2. Northside Branch: 82.5 kg - $825.00 (33.6%)
  3. Westside Branch: 65.0 kg - $651.00 (26.5%)

Recommendation: Focus wastage reduction training on Downtown Branch
```

---

## ✅ Acceptance Criteria

### Definition of Done

This implementation is complete when:

- [x] All 6 new tables created and tested
- [x] Supplier management CRUD functional
- [x] Procurement orders can be created and received
- [x] Batch numbers and expiry dates tracked
- [x] FIFO logic working in dispatch
- [x] Wastage can be recorded with categories
- [x] Physical stock count requires variance categorization
- [x] Enhanced variance report shows category breakdown
- [x] Wastage analysis report shows trends
- [x] Expiry alerts working
- [x] All tests passing
- [x] Documentation complete
- [x] User training completed
- [x] Superadmin can generate wastage reports

---

## 🚀 Next Steps

1. **Review & Approve:** Stakeholder review of this plan
2. **Prioritize:** Confirm phases can be adjusted based on urgency
3. **Assign Resources:** Assign developer(s)
4. **Kickoff:** Schedule Phase 1 start date
5. **Weekly Check-ins:** Track progress against phases

---

## 📞 Questions to Resolve Before Starting

1. **Supplier Count:** How many suppliers do you have? (affects UI design)
2. **Expiry Days:** What's your typical shelf life for chicken? (7 days? 14 days?)
3. **Alert Threshold:** Alert how many days before expiry? (Recommend: 3 days)
4. **Cost Tracking:** Do you need detailed cost per unit or just estimates?
5. **Wastage Approval:** What's the threshold for requiring superadmin approval? (Recommend: $500+)
6. **Batch Numbers:** Do suppliers provide batch numbers or will you generate them?
7. **Photo Evidence:** Required for wastage or optional?

---

**Document Status:** 📝 Draft - Ready for Review  
**Created:** February 22, 2026  
**Last Updated:** February 22, 2026  
**Next Review:** Before Phase 1 kickoff
