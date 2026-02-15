/**
 * Main JavaScript
 * Web-Based Centralized Inventory and Stock Distribution Management System
 */

document.addEventListener('DOMContentLoaded', function() {
    // Auto-hide alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transition = 'opacity 0.5s';
            setTimeout(() => alert.remove(), 500);
        }, 5000);
    });

    // Confirm delete/deactivate actions with SweetAlert2
    const confirmButtons = document.querySelectorAll('[onclick*="confirm"]');
    confirmButtons.forEach(button => {
        button.addEventListener('click', async function(e) {
            e.preventDefault();
            const confirmText = this.getAttribute('onclick').match(/'([^']+)'/)[1];
            const result = await Swal.fire({
                title: 'Are you sure?',
                text: confirmText,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#FF6B35',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, proceed',
                cancelButtonText: 'Cancel'
            });
            
            if (result.isConfirmed) {
                // Re-trigger the action by temporarily removing the onclick and clicking
                const originalOnclick = this.getAttribute('onclick');
                this.removeAttribute('onclick');
                if (this.type === 'submit' || this.closest('form')) {
                    this.closest('form').submit();
                } else {
                    this.setAttribute('onclick', originalOnclick);
                    this.onclick = null;
                    this.click();
                }
            }
        });
    });

    // Table row highlighting
    const tableRows = document.querySelectorAll('.data-table tbody tr');
    tableRows.forEach(row => {
        row.addEventListener('click', function() {
            const link = this.querySelector('a.btn');
            if (link && link.href) {
                // Optionally navigate to detail page on row click
                // window.location.href = link.href;
            }
        });
    });

    // Form validation feedback
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;

            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.style.borderColor = '#F44336';
                } else {
                    field.style.borderColor = '#ddd';
                }
            });

            if (!isValid) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Validation Error',
                    text: 'Please fill in all required fields.',
                    confirmButtonColor: '#FF6B35'
                });
            }
        });
    });

    // Number input validation (prevent negative values)
    const numberInputs = document.querySelectorAll('input[type="number"]');
    numberInputs.forEach(input => {
        input.addEventListener('change', function() {
            if (parseFloat(this.value) < 0) {
                this.value = 0;
            }
        });
    });

    // Auto-calculate totals if needed
    const quantityInputs = document.querySelectorAll('.calculate-total');
    if (quantityInputs.length > 0) {
        quantityInputs.forEach(input => {
            input.addEventListener('input', calculateTotals);
        });
    }

    // Sidebar mobile toggle (for responsive design)
    const menuToggle = document.querySelector('.menu-toggle');
    const sidebar = document.querySelector('.sidebar');
    
    if (menuToggle && sidebar) {
        menuToggle.addEventListener('click', function() {
            sidebar.classList.toggle('active');
        });
    }

    // Smooth scroll for anchors
    const anchorLinks = document.querySelectorAll('a[href^="#"]');
    anchorLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            const targetId = this.getAttribute('href');
            if (targetId !== '#') {
                e.preventDefault();
                const targetElement = document.querySelector(targetId);
                if (targetElement) {
                    targetElement.scrollIntoView({ behavior: 'smooth' });
                }
            }
        });
    });

    // Search/filter functionality for tables
    const searchInputs = document.querySelectorAll('.table-search');
    searchInputs.forEach(input => {
        input.addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase();
            const tableId = this.getAttribute('data-table');
            const table = document.getElementById(tableId);
            
            if (table) {
                const rows = table.querySelectorAll('tbody tr');
                rows.forEach(row => {
                    const text = row.textContent.toLowerCase();
                    row.style.display = text.includes(searchTerm) ? '' : 'none';
                });
            }
        });
    });

    // Initialize tooltips (if you add a tooltip library)
    const tooltips = document.querySelectorAll('[data-tooltip]');
    tooltips.forEach(element => {
        element.setAttribute('title', element.getAttribute('data-tooltip'));
    });
});

// Helper function to calculate totals
function calculateTotals() {
    // This can be customized based on specific needs
    console.log('Calculating totals...');
}

// SweetAlert2 helper functions for global use
window.showSuccess = function(message, title = 'Success') {
    return Swal.fire({
        icon: 'success',
        title: title,
        text: message,
        confirmButtonColor: '#FF6B35',
        timer: 3000,
        timerProgressBar: true
    });
};

window.showError = function(message, title = 'Error') {
    return Swal.fire({
        icon: 'error',
        title: title,
        text: message,
        confirmButtonColor: '#FF6B35'
    });
};

window.showWarning = function(message, title = 'Warning') {
    return Swal.fire({
        icon: 'warning',
        title: title,
        text: message,
        confirmButtonColor: '#FF6B35'
    });
};

window.showInfo = function(message, title = 'Information') {
    return Swal.fire({
        icon: 'info',
        title: title,
        text: message,
        confirmButtonColor: '#FF6B35'
    });
};

window.showConfirm = async function(message, title = 'Are you sure?') {
    const result = await Swal.fire({
        title: title,
        text: message,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#FF6B35',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, proceed',
        cancelButtonText: 'Cancel'
    });
    return result.isConfirmed;
};

// Export functions for use in inline scripts
window.appFunctions = {
    calculateTotals,
    showSuccess,
    showError,
    showWarning,
    showInfo,
    showConfirm
};
