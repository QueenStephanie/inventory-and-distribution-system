# Coding Standards

## Inventory & Distribution Management System

This document outlines coding standards and best practices for maintaining code quality and consistency across the project.

---

## 📋 General Principles

1. **Write clean, readable code** - Code is read more than it's written
2. **Follow DRY** - Don't Repeat Yourself
3. **Keep it simple** - Prefer simple solutions over complex ones
4. **Comment wisely** - Explain *why*, not *what*
5. **Security first** - Always sanitize input and validate data

---

## 🐘 PHP Standards

### File Structure
```php
<?php
/**
 * File Purpose/Description
 * Web-Based Centralized Inventory and Stock Distribution Management System
 */

require_once '../config/database.php';
require_once '../config/auth.php';

// Session and authentication checks
startSecureSession();
requireRole('role_name');

// Define page constants
define('PAGE_TITLE', 'Page Title');

// Business logic here
// ...

// Include header
include '../includes/header.php';
?>
<!-- HTML content -->
<?php include '../includes/footer.php'; ?>
```

### Naming Conventions

**Variables**
```php
$camelCase          // Use camelCase for variables
$userId             // Not $user_id or $UserID
$requestCount       // Descriptive and clear
```

**Functions**
```php
function getUserById($id)           // camelCase, verb + noun
function calculateVariance($a, $b)  // Clear purpose
function isValidRequest($req)       // Boolean functions start with 'is', 'has', 'can'
```

**Database Tables**
```sql
-- Use lowercase with underscores
users
stock_requisitions
requisition_items
physical_stock_counts
```

**Database Columns**
```sql
-- Use lowercase with underscores
user_id
full_name
request_date
created_at
```

### Code Formatting

**Indentation:** 4 spaces (no tabs)

**Braces:**
```php
// Opening brace on same line
if ($condition) {
    // code
} else {
    // code
}

function myFunction() {
    // code
}
```

**Spacing:**
```php
// Operators
$total = $price + $tax;

// Function calls
getUserById($id);

// Arrays
$array = [1, 2, 3, 4];
$assoc = ['key' => 'value', 'name' => 'John'];
```

### Security Best Practices

**1. Always Use Prepared Statements**
```php
// ✅ GOOD
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();

// ❌ BAD
$query = "SELECT * FROM users WHERE user_id = $userId";
$conn->query($query);
```

**2. Sanitize Output**
```php
// ✅ GOOD
echo htmlspecialchars($userInput, ENT_QUOTES, 'UTF-8');

// ❌ BAD
echo $userInput;
```

**3. Validate Input**
```php
// ✅ GOOD
$quantity = floatval($_POST['quantity']);
if ($quantity <= 0) {
    $error = 'Quantity must be greater than 0';
}

// ❌ BAD
$quantity = $_POST['quantity'];
```

**4. Use Helper Functions**
```php
// Use existing helper
$clean = sanitizeInput($_POST['field']);

// For database queries
$conn = getDBConnection();
// ... use connection
closeDBConnection($conn);
```

### Error Handling

```php
try {
    $conn->begin_transaction();
    
    // Database operations
    
    $conn->commit();
    $success = true;
    
} catch (Exception $e) {
    $conn->rollback();
    error_log("Error description: " . $e->getMessage());
    $error = "User-friendly error message";
}
```

### Comments and Documentation

**File Headers:**
```php
<?php
/**
 * Brief file description
 * Web-Based Centralized Inventory and Stock Distribution Management System
 */
```

**Function Documentation:**
```php
/**
 * Calculate variance between system and physical quantity
 * 
 * @param float $systemQty System recorded quantity
 * @param float $physicalQty Actual physical count
 * @return float Variance (negative = shortage, positive = overage)
 */
function calculateVariance($systemQty, $physicalQty) {
    return $physicalQty - $systemQty;
}
```

**Inline Comments:**
```php
// Calculate total including tax (only for complex logic)
$total = $subtotal * (1 + $taxRate);

// Don't comment obvious code:
// Set user ID - ❌ BAD
$userId = 123;
```

---

## 🎨 CSS Standards

### Organization
```css
/* ===== Section Name ===== */
/* Subsection */
```

### Naming
```css
/* Use descriptive class names */
.login-container { }        /* ✅ Good */
.lc { }                     /* ❌ Bad */

/* Use kebab-case */
.user-profile { }           /* ✅ Good */
.user_profile { }           /* ❌ Bad */
```

### Properties Order
```css
.element {
    /* Positioning */
    position: relative;
    top: 0;
    left: 0;
    
    /* Display & Box Model */
    display: flex;
    width: 100%;
    padding: 10px;
    margin: 20px;
    
    /* Typography */
    font-size: 14px;
    color: #333;
    
    /* Visual */
    background: white;
    border: 1px solid #ddd;
    
    /* Misc */
    transition: all 0.3s;
}
```

---

## 📜 JavaScript Standards

### Naming
```javascript
// Variables and functions: camelCase
let userName = 'John';
function getUserData() { }

// Constants: UPPER_SNAKE_CASE
const MAX_ATTEMPTS = 3;
const API_URL = '/api/endpoint';
```

### Code Style
```javascript
// Use const/let, not var
const items = [];
let count = 0;

// Function expressions
const calculateTotal = (items) => {
    return items.reduce((sum, item) => sum + item.price, 0);
};

// Consistent spacing
if (condition) {
    doSomething();
}

// Event listeners
element.addEventListener('click', function(e) {
    e.preventDefault();
    // handle click
});
```

---

## 🗄️ SQL Standards

### Query Formatting
```sql
-- Use uppercase for keywords
SELECT user_id, username, full_name
FROM users
WHERE status = 'active'
    AND role = 'branch_user'
ORDER BY full_name ASC;

-- Indent for readability
SELECT 
    u.user_id,
    u.username,
    b.branch_name
FROM users u
LEFT JOIN branches b ON u.branch_id = b.branch_id
WHERE u.status = 'active';
```

### Always Specify Columns
```sql
-- ✅ GOOD
SELECT material_id, material_name, category
FROM raw_materials;

-- ❌ BAD
SELECT * FROM raw_materials;
```

### Use Meaningful Aliases
```sql
-- ✅ GOOD
SELECT 
    b.branch_name,
    rm.material_name,
    i.current_quantity
FROM inventory i
JOIN branches b ON i.branch_id = b.branch_id
JOIN raw_materials rm ON i.material_id = rm.material_id;

-- ❌ BAD (confusing aliases)
SELECT 
    t1.name,
    t2.name,
    t3.qty
FROM table3 t3
JOIN table1 t1 ON t3.id1 = t1.id
JOIN table2 t2 ON t3.id2 = t2.id;
```

---

## 📁 File Organization

### Directory Structure
```
/config              → Configuration files only
/database            → SQL files only
/assets/css          → Stylesheets
/assets/js           → JavaScript files
/includes            → Shared PHP includes
/[role]/             → Role-specific pages
```

### File Naming
- Use lowercase with underscores: `manage_users.php`
- Be descriptive: `variance_report.php` not `report.php`
- Group related files with prefixes if needed

---

## ✅ Code Review Checklist

Before committing code, verify:

- [ ] Code follows naming conventions
- [ ] All user input is sanitized/validated
- [ ] Prepared statements used for database queries
- [ ] Output is escaped (htmlspecialchars)
- [ ] No hardcoded paths or credentials
- [ ] Error handling implemented
- [ ] Comments explain complex logic
- [ ] No console.log or var_dump left in code
- [ ] Functions are single-purpose
- [ ] Code is DRY (no repetition)
- [ ] Indentation is consistent
- [ ] No trailing whitespace

---

## 🚫 Code Smells to Avoid

### PHP
```php
// ❌ Don't use deprecated functions
mysql_query()           // Use mysqli or PDO
ereg()                  // Use preg_match()

// ❌ Don't suppress errors without handling
@include('file.php');   // Handle errors properly

// ❌ Don't use magic numbers
if ($status == 3)       // Use constants: STATUS_APPROVED

// ❌ Don't use short tags
<? echo $var; ?>        // Use <?php echo $var; ?>
```

### SQL
```php
// ❌ Don't concatenate SQL
$sql = "SELECT * FROM users WHERE id = " . $id;

// ❌ Don't use SELECT *
$sql = "SELECT * FROM users";
```

### General
```php
// ❌ Deep nesting
if ($a) {
    if ($b) {
        if ($c) {
            // too deep
        }
    }
}

// ✅ Early returns
if (!$a) return;
if (!$b) return;
if (!$c) return;
// code here
```

---

## 📚 Additional Resources

- PHP: https://www.php-fig.org/psr/
- MySQL: https://dev.mysql.com/doc/
- JavaScript: https://standardjs.com/
- Security: https://owasp.org/www-project-php-security-cheat-sheet/

---

**Remember:** Consistent code is maintainable code!
