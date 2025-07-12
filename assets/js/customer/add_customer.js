const addCustomerForm = document.getElementById('addCustomerForm');
if (addCustomerForm) {
    addCustomerForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const name = document.getElementById('customer_name').value.trim();
        const mobile1 = document.getElementById('customer_mobile1').value.trim();
        const mobile2 = document.getElementById('customer_mobile2').value.trim();
        // Validation: mobile1 and mobile2 must start with 07 and be 11 digits
        const mobileRegex = /^07\d{9}$/;
        if (!mobileRegex.test(mobile1)) {
            Swal.fire({ icon: 'error', title: 'هەڵە', text: 'ژمارە مۆبایلی یەکەم دەبێت بە 07 دەست پێ بکات و 11 ژمارە بێت.' });
            return;
        }
        if (mobile2 && !mobileRegex.test(mobile2)) {
            Swal.fire({ icon: 'error', title: 'هەڵە', text: 'ژمارە مۆبایلی دووەم دەبێت بە 07 دەست پێ بکات و 11 ژمارە بێت.' });
            return;
        }
        if (mobile2 && mobile1 === mobile2) {
            Swal.fire({ icon: 'error', title: 'هەڵە', text: 'ژمارە مۆبایلی یەکەم و دووەم نابێت یەکسان بن.' });
            return;
        }
        const formData = new FormData(addCustomerForm);
        fetch('../process/customer/add_customer.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'سەرکەوتوو بوو',
                    text: data.message || 'کڕیار بە سەرکەوتوویی زیادکرا',
                    timer: 1500,
                    showConfirmButton: false
                });
                const modal = bootstrap.Modal.getInstance(document.getElementById('addCustomerModal'));
                if (modal) modal.hide();
                addCustomerForm.reset();
                if (typeof loadCustomers === 'function') loadCustomers();
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'هەڵە',
                    text: data.message || 'هەڵەیەک ڕووی دا',
                });
            }
        })
        .catch(() => {
            Swal.fire({
                icon: 'error',
                title: 'هەڵە',
                text: 'هەڵەیەک ڕووی دا',
            });
        });
    });
}

document.addEventListener('DOMContentLoaded', function() {
    // Set default value for opening debt fields
    const openingDebtUsd = document.getElementById('customer_opening_debt_usd');
    const openingDebtIqd = document.getElementById('customer_opening_debt_iqd');
    if (openingDebtUsd) openingDebtUsd.value = 0;
    if (openingDebtIqd) openingDebtIqd.value = 0;
    // Set placeholder for mobile fields
    const mobile1 = document.getElementById('customer_mobile1');
    const mobile2 = document.getElementById('customer_mobile2');
    if (mobile1) mobile1.placeholder = '07xxxxxxxxx';
    if (mobile2) mobile2.placeholder = '07xxxxxxxxx';
});
