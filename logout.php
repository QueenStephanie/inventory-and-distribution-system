<?php
/**
 * Logout Page
 * Web-Based Centralized Inventory and Stock Distribution Management System
 */

require_once 'config/auth.php';

startSecureSession();
logoutUser();
?>
