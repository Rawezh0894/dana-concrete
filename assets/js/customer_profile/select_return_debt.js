async function loadCustomerReturnDebts(customerId) {
    if (!customerId || customerId <= 0) {
        console.error('Invalid customer ID for loading return debts:', customerId);
        return;
    }
    
    try {
        const res = await fetch(`../process/customer_profile/select_return_debt.php?customer_id=${customerId}`);
        if (!res.ok) {
            throw new Error(`HTTP error! status: ${res.status}`);
        }
        const data = await res.json();
        const columns = ['#', 'date', 'dolar_rate', 'paid_usd', 'paid_iqd', 'discount', 'note', 'actions'];
        function formatNumber(n) {
            if (n === null || n === undefined || n === '') return '';
            return n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        }
        function formatUSD(n) {
            if (!n || isNaN(n)) return '';
            return formatNumber(Number(n).toFixed(2)) + ' $';
        }
        function formatIQD(n) {
            if (!n || isNaN(n)) return '';
            return formatNumber(Number(n).toFixed(0)) + ' د.ع';
        }
        const rows = (data || []).map((row, idx) => ({
            '#': idx + 1,
            date: row.date || '-',
            dolar_rate: row.dolar_rate !== null && row.dolar_rate !== undefined && row.dolar_rate !== '' ? formatNumber(row.dolar_rate) : '-',
            paid_usd: row.paid_usd !== null && row.paid_usd !== undefined && row.paid_usd !== '' ? formatUSD(row.paid_usd) : '-',
            paid_iqd: row.paid_iqd !== null && row.paid_iqd !== undefined && row.paid_iqd !== '' ? formatIQD(row.paid_iqd) : '-',
            discount: row.discount !== null && row.discount !== undefined && row.discount !== '' ? formatUSD(row.discount) : '-',
            note: row.note || '-',
            actions: `
                <button class="btn btn-sm btn-primary edit-return-debt" data-id="${row.id}" title="دەستکاریکردن">
                    <i class="fa fa-edit"></i>
                </button>
                <button class="btn btn-sm btn-danger delete-return-debt" data-id="${row.id}" title="سڕینەوە">
                    <i class="fa fa-trash"></i>
                </button>
                <button class="btn btn-sm btn-success print-debt-receipt" data-id="${row.id}" title="پرێنت">
                    <i class="fa fa-print"></i>
                </button>
            `
        }));
        TableController.renderWithPagination('#customerDebtTable', rows, columns, { pageSize: 10 });
    } catch (error) {
        console.error('Error loading customer return debts:', error);
        Swal.fire('هەڵە', 'هەڵەیەک ڕووی دا لە بارکردنی داتاکان', 'error');
    }
}

// Make function globally available
window.loadCustomerReturnDebts = loadCustomerReturnDebts;

document.addEventListener('DOMContentLoaded', function() {
    if (typeof CUSTOMER_ID !== 'undefined' && CUSTOMER_ID && CUSTOMER_ID > 0) {
        loadCustomerReturnDebts(CUSTOMER_ID);
    } else {
        console.error('Invalid CUSTOMER_ID for loading return debts:', CUSTOMER_ID);
    }
});

// Improved edit button handler with better error handling
document.addEventListener('click', async function(e) {
    if (e.target.classList.contains('edit-return-debt') || e.target.closest('.edit-return-debt')) {
        e.preventDefault();
        e.stopPropagation();
        
        const button = e.target.classList.contains('edit-return-debt') ? e.target : e.target.closest('.edit-return-debt');
        const id = button.getAttribute('data-id');
        
        if (!id) {
            console.error('No debt ID found for editing');
            Swal.fire('هەڵە', 'ناسنامەی قەرز نەدۆزرایەوە!', 'error');
            return;
        }
        
        // Show loading state
        button.disabled = true;
        const originalHTML = button.innerHTML;
        button.innerHTML = '<i class="fa fa-spinner fa-spin"></i>';
        
        try {
            // وەرگرتنی داتای دانەوەی قەرز
            const res = await fetch(`../process/customer_profile/select_return_debt.php?debt_id=${id}`);
            if (!res.ok) {
                throw new Error(`HTTP error! status: ${res.status}`);
            }
            
            const data = await res.json();
            if (!data || !data.id) {
                console.error('Debt fetch error:', data);
                Swal.fire('هەڵە', 'داتای دانەوە نەدۆزرایەوە!', 'error');
                return;
            }
            
            // پڕکردنەوەی مۆداڵ
            document.getElementById('edit_customer_debt_id').value = data.id;
            document.getElementById('edit_customer_debt_date').value = data.date || '';
            document.getElementById('edit_customer_debt_dolar_rate').value = data.dolar_rate || '';
            document.getElementById('edit_customer_debt_paid_usd').value = data.paid_usd || '';
            document.getElementById('edit_customer_debt_paid_iqd').value = data.paid_iqd || '';
            document.getElementById('edit_customer_debt_discount').value = data.discount || '';
            document.getElementById('edit_customer_debt_note').value = data.note || '';
            
            // نیشاندانی مۆداڵ
            const modalElement = document.getElementById('editCustomerDebtModal');
            if (!modalElement) {
                throw new Error('Edit modal not found');
            }
            
            const modal = new bootstrap.Modal(modalElement);
            modal.show();
            
        } catch (error) {
            console.error('Error loading debt data for editing:', error);
            Swal.fire('هەڵە', 'هەڵەیەک ڕووی دا لە بارکردنی داتاکان', 'error');
        } finally {
            // Restore button state
            button.disabled = false;
            button.innerHTML = originalHTML;
        }
    }
});

// Print debt receipt button handler
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('print-debt-receipt') || e.target.closest('.print-debt-receipt')) {
        e.preventDefault();
        e.stopPropagation();
        
        const button = e.target.classList.contains('print-debt-receipt') ? e.target : e.target.closest('.print-debt-receipt');
        const id = button.getAttribute('data-id');
        
        if (!id) {
            console.error('No debt ID found for printing');
            Swal.fire('هەڵە', 'ناسنامەی قەرز نەدۆزرایەوە!', 'error');
            return;
        }
        
        // Open debt payment receipt in new window
        window.open(`../pages/debt_payment_receipt.php?id=${id}&auto_print=1`, '_blank');
    }
});
