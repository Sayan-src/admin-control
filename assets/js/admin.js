// Admin JavaScript Functions

// Modal Functions
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'block';
        document.body.style.overflow = 'hidden';
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        if (event.target === modal) {
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }
    });
}

// Member Functions
function editMember(memberData) {
    // Populate edit form with member data
    document.getElementById('edit_id').value = memberData.id;
    document.getElementById('edit_first_name').value = memberData.first_name;
    document.getElementById('edit_last_name').value = memberData.last_name;
    document.getElementById('edit_email').value = memberData.email;
    document.getElementById('edit_phone').value = memberData.phone || '';
    document.getElementById('edit_address').value = memberData.address || '';
    document.getElementById('edit_city').value = memberData.city || '';
    document.getElementById('edit_state').value = memberData.state || '';
    document.getElementById('edit_zip_code').value = memberData.zip_code || '';
    document.getElementById('edit_membership_type').value = memberData.membership_type;
    document.getElementById('edit_status').value = memberData.status;
    
    openModal('editMemberModal');
}

function deleteMember(memberId) {
    if (confirm('Are you sure you want to delete this member? This action cannot be undone.')) {
        window.location.href = `members.php?action=delete&id=${memberId}`;
    }
}

// Invoice Functions
function editInvoice(invoiceData) {
    // Populate edit form with invoice data
    document.getElementById('edit_id').value = invoiceData.id;
    document.getElementById('edit_member_id').value = invoiceData.member_id;
    document.getElementById('edit_service_id').value = invoiceData.service_id;
    document.getElementById('edit_amount').value = invoiceData.amount;
    document.getElementById('edit_tax_amount').value = invoiceData.tax_amount;
    document.getElementById('edit_invoice_date').value = invoiceData.invoice_date;
    document.getElementById('edit_due_date').value = invoiceData.due_date;
    document.getElementById('edit_status').value = invoiceData.status;
    document.getElementById('edit_notes').value = invoiceData.notes || '';
    
    openModal('editInvoiceModal');
}

function deleteInvoice(invoiceId) {
    if (confirm('Are you sure you want to delete this invoice? This action cannot be undone.')) {
        window.location.href = `invoices.php?action=delete&id=${invoiceId}`;
    }
}

function viewInvoice(invoiceId) {
    window.open(`view_invoice.php?id=${invoiceId}`, '_blank');
}

// Service price update function
function updateServicePrice() {
    const serviceSelect = document.getElementById('service_id');
    const amountInput = document.getElementById('amount');
    
    if (serviceSelect && amountInput) {
        const selectedOption = serviceSelect.options[serviceSelect.selectedIndex];
        if (selectedOption && selectedOption.dataset.price) {
            amountInput.value = selectedOption.dataset.price;
        }
    }
}

// Export Functions
function exportMembers() {
    const urlParams = new URLSearchParams(window.location.search);
    const startDate = urlParams.get('start_date');
    const endDate = urlParams.get('end_date');
    const status = urlParams.get('status');
    const membershipType = urlParams.get('membership_type');
    const search = urlParams.get('search');
    
    let exportUrl = 'export_members.php?';
    if (startDate) exportUrl += `start_date=${startDate}&`;
    if (endDate) exportUrl += `end_date=${endDate}&`;
    if (status) exportUrl += `status=${status}&`;
    if (membershipType) exportUrl += `membership_type=${membershipType}&`;
    if (search) exportUrl += `search=${encodeURIComponent(search)}&`;
    
    window.open(exportUrl, '_blank');
}

function exportInvoices() {
    const urlParams = new URLSearchParams(window.location.search);
    const startDate = urlParams.get('start_date');
    const endDate = urlParams.get('end_date');
    const status = urlParams.get('status');
    const memberId = urlParams.get('member_id');
    const search = urlParams.get('search');
    
    let exportUrl = 'export_invoices.php?';
    if (startDate) exportUrl += `start_date=${startDate}&`;
    if (endDate) exportUrl += `end_date=${endDate}&`;
    if (status) exportUrl += `status=${status}&`;
    if (memberId) exportUrl += `member_id=${memberId}&`;
    if (search) exportUrl += `search=${encodeURIComponent(search)}&`;
    
    window.open(exportUrl, '_blank');
}

// Form validation
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return true;
    
    const requiredFields = form.querySelectorAll('[required]');
    let isValid = true;
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            field.style.borderColor = '#dc3545';
            isValid = false;
        } else {
            field.style.borderColor = '#e1e5e9';
        }
    });
    
    return isValid;
}

// Add form validation to all forms
document.addEventListener('DOMContentLoaded', function() {
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!validateForm(form.id)) {
                e.preventDefault();
                alert('Please fill in all required fields.');
            }
        });
    });
    
    // Auto-hide alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => {
                alert.remove();
            }, 300);
        }, 5000);
    });
});

// Date range picker for filters
function setDateRange(range) {
    const today = new Date();
    let startDate, endDate;
    
    switch(range) {
        case 'today':
            startDate = endDate = today.toISOString().split('T')[0];
            break;
        case 'week':
            const weekAgo = new Date(today.getTime() - 7 * 24 * 60 * 60 * 1000);
            startDate = weekAgo.toISOString().split('T')[0];
            endDate = today.toISOString().split('T')[0];
            break;
        case 'month':
            startDate = new Date(today.getFullYear(), today.getMonth(), 1).toISOString().split('T')[0];
            endDate = new Date(today.getFullYear(), today.getMonth() + 1, 0).toISOString().split('T')[0];
            break;
        case 'quarter':
            const quarter = Math.floor(today.getMonth() / 3);
            startDate = new Date(today.getFullYear(), quarter * 3, 1).toISOString().split('T')[0];
            endDate = new Date(today.getFullYear(), (quarter + 1) * 3, 0).toISOString().split('T')[0];
            break;
        case 'year':
            startDate = new Date(today.getFullYear(), 0, 1).toISOString().split('T')[0];
            endDate = new Date(today.getFullYear(), 11, 31).toISOString().split('T')[0];
            break;
    }
    
    if (startDate && endDate) {
        const startDateInput = document.getElementById('start_date');
        const endDateInput = document.getElementById('end_date');
        
        if (startDateInput) startDateInput.value = startDate;
        if (endDateInput) endDateInput.value = endDate;
    }
}

// Search functionality
function performSearch(searchTerm) {
    const currentUrl = new URL(window.location);
    currentUrl.searchParams.set('search', searchTerm);
    window.location.href = currentUrl.toString();
}

// Debounce function for search
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Auto-search with debounce
const searchInputs = document.querySelectorAll('input[type="text"][name="search"]');
searchInputs.forEach(input => {
    input.addEventListener('input', debounce(function() {
        if (this.value.length >= 2 || this.value.length === 0) {
            performSearch(this.value);
        }
    }, 500));
});

// Print functionality
function printInvoice(invoiceId) {
    const printWindow = window.open(`view_invoice.php?id=${invoiceId}&print=1`, '_blank');
    printWindow.onload = function() {
        printWindow.print();
    };
}

// Loading states
function showLoading(button) {
    const originalText = button.textContent;
    button.textContent = 'Loading...';
    button.disabled = true;
    button.classList.add('loading');
    
    return function() {
        button.textContent = originalText;
        button.disabled = false;
        button.classList.remove('loading');
    };
}

// Add loading states to submit buttons
document.addEventListener('DOMContentLoaded', function() {
    const submitButtons = document.querySelectorAll('button[type="submit"]');
    submitButtons.forEach(button => {
        button.addEventListener('click', function() {
            const hideLoading = showLoading(this);
            setTimeout(hideLoading, 2000); // Fallback in case form doesn't submit
        });
    });
});

// Responsive table handling
function makeTableResponsive() {
    const tables = document.querySelectorAll('.table');
    tables.forEach(table => {
        const wrapper = document.createElement('div');
        wrapper.style.overflowX = 'auto';
        table.parentNode.insertBefore(wrapper, table);
        wrapper.appendChild(table);
    });
}

// Initialize responsive tables
document.addEventListener('DOMContentLoaded', makeTableResponsive);

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // Ctrl/Cmd + N for new member
    if ((e.ctrlKey || e.metaKey) && e.key === 'n') {
        e.preventDefault();
        openModal('addMemberModal');
    }
    
    // Ctrl/Cmd + I for new invoice
    if ((e.ctrlKey || e.metaKey) && e.key === 'i') {
        e.preventDefault();
        openModal('createInvoiceModal');
    }
    
    // Escape to close modals
    if (e.key === 'Escape') {
        const openModals = document.querySelectorAll('.modal[style*="block"]');
        openModals.forEach(modal => {
            closeModal(modal.id);
        });
    }
});

// Auto-save form data to localStorage
function autoSaveForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return;
    
    const formData = new FormData(form);
    const data = {};
    for (let [key, value] of formData.entries()) {
        data[key] = value;
    }
    
    localStorage.setItem(`form_${formId}`, JSON.stringify(data));
}

// Restore form data from localStorage
function restoreFormData(formId) {
    const form = document.getElementById(formId);
    if (!form) return;
    
    const savedData = localStorage.getItem(`form_${formId}`);
    if (savedData) {
        const data = JSON.parse(savedData);
        Object.keys(data).forEach(key => {
            const field = form.querySelector(`[name="${key}"]`);
            if (field) {
                field.value = data[key];
            }
        });
    }
}

// Clear saved form data
function clearSavedFormData(formId) {
    localStorage.removeItem(`form_${formId}`);
}

// Add auto-save to forms
document.addEventListener('DOMContentLoaded', function() {
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        // Restore data on load
        restoreFormData(form.id);
        
        // Auto-save on input
        const inputs = form.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            input.addEventListener('input', () => autoSaveForm(form.id));
            input.addEventListener('change', () => autoSaveForm(form.id));
        });
        
        // Clear saved data on successful submit
        form.addEventListener('submit', function() {
            setTimeout(() => clearSavedFormData(form.id), 1000);
        });
    });
}); 