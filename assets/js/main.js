// Main JavaScript untuk HRISKU App

// Confirm Delete
function confirmDelete(message = 'Yakin ingin menghapus data ini?') {
    return confirm(message);
}

// Auto Hide Alert
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            alert.style.opacity = '0';
            setTimeout(function() {
                alert.remove();
            }, 500);
        }, 5000);
    });
});

// Form Validation
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return true;
    
    const requiredFields = form.querySelectorAll('[required]');
    let isValid = true;
    
    requiredFields.forEach(function(field) {
        if (!field.value.trim()) {
            field.style.borderColor = '#ef4444';
            isValid = false;
        } else {
            field.style.borderColor = '#d1d5db';
        }
    });
    
    if (!isValid) {
        alert('Mohon lengkapi semua field yang wajib diisi');
    }
    
    return isValid;
}

// Number Format
function formatNumber(num) {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
}

// Currency Format
function formatCurrency(num) {
    return 'Rp ' + formatNumber(num);
}

// Date Format
function formatDate(dateString) {
    const date = new Date(dateString);
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agt', 'Sep', 'Okt', 'Nov', 'Des'];
    
    return date.getDate() + ' ' + months[date.getMonth()] + ' ' + date.getFullYear();
}

// Print Page
function printPage() {
    window.print();
}

// Export Table to CSV
function exportTableToCSV(tableId, filename = 'export.csv') {
    const table = document.getElementById(tableId);
    if (!table) return;
    
    let csv = [];
    const rows = table.querySelectorAll('tr');
    
    rows.forEach(function(row) {
        const cells = row.querySelectorAll('td, th');
        const rowData = [];
        
        cells.forEach(function(cell) {
            rowData.push('"' + cell.textContent.replace(/"/g, '""') + '"');
        });
        
        csv.push(rowData.join(','));
    });
    
    const csvContent = csv.join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    
    link.setAttribute('href', url);
    link.setAttribute('download', filename);
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

console.log('HRISKU App Loaded Successfully');
