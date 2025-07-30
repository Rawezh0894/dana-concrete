// Global variable to store customer ID
let CUSTOMER_ID_FOR_REDIRECT = null;

document.addEventListener('DOMContentLoaded', function() {
    // Get debt payment ID from URL
    const urlParams = new URLSearchParams(window.location.search);
    const debtId = urlParams.get('id');
    
    if (debtId) {
        loadDebtPaymentInformation(debtId);
    } else {
        console.error('No debt payment ID provided');
        Swal.fire('هەڵە', 'ناسنامەی دانەوەی قەرز دیاری نەکراوە', 'error');
    }
});

async function loadDebtPaymentInformation(debtId) {
    try {
        const response = await fetch(`../process/debt_payment_receipt/get_information.php?id=${debtId}`);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.message || 'هەڵەیەک ڕووی دا');
        }
        
        // Store customer ID for redirect
        CUSTOMER_ID_FOR_REDIRECT = data.data.customer_id;
        
        // Populate receipt with data
        populateReceipt(data.data);
        
    } catch (error) {
        console.error('Error loading debt payment information:', error);
        Swal.fire('هەڵە', 'هەڵەیەک ڕووی دا لە بارکردنی داتاکان', 'error');
    }
}

function populateReceipt(data) {
    // Format numbers
    function formatNumber(n) {
        if (n === null || n === undefined || n === '') return '-';
        return n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    }
    
    function formatUSD(n) {
        if (!n || isNaN(n)) return '-';
        return formatNumber(Number(n).toFixed(2)) + ' $';
    }
    
    function formatIQD(n) {
        if (!n || isNaN(n)) return '-';
        return formatNumber(Number(n).toFixed(0)) + ' د.ع';
    }
    
    // Populate receipt fields
    document.getElementById('receipt_number').textContent = data.receipt_number || 'QW-' + data.id.toString().padStart(4, '0');
    document.getElementById('customer_name').textContent = data.customer_name || '-';
    document.getElementById('customer_phone').textContent = data.customer_phone || '-';
    document.getElementById('customer_address').textContent = data.customer_address || '-';
    document.getElementById('payment_date').textContent = data.payment_date || '-';
    
    // Debt payment details
    document.getElementById('debt_payment_date').textContent = data.date || '-';
    document.getElementById('dolar_rate').textContent = data.dolar_rate ? formatNumber(data.dolar_rate) + ' د.ع' : '-';
    document.getElementById('paid_usd').textContent = data.paid_usd ? formatUSD(data.paid_usd) : '-';
    document.getElementById('paid_iqd').textContent = data.paid_iqd ? formatIQD(data.paid_iqd) : '-';
    document.getElementById('discount').textContent = data.discount ? formatUSD(data.discount) : '-';
    document.getElementById('note').textContent = data.note || '-';
} 