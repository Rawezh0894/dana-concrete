let submitting = false;
// Handle add formula form submission
const addFormulaForm = document.getElementById('addFormulaForm');
if (addFormulaForm) {
    addFormulaForm.addEventListener('submit', function(e) {
        if (submitting) return false;
        submitting = true;
        e.preventDefault();
        const formData = new FormData(addFormulaForm);
        const strengthType = formData.get('strength_type');
        if (strengthType === 'kg') {
            formData.delete('strength_mpa');
        } else if (strengthType === 'mpa') {
            formData.delete('strength_kg');
        }
        fetch('../process/concrete_fomulas/add_formulas.php', {
            method: 'POST',
            body: formData
        })
        .then(async response => {
            const text = await response.text();
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('Server response (not JSON):', text);
                throw new Error('Invalid JSON');
            }
        })
        .then(data => {
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'سەرکەوتوو بوو',
                    text: data.message || 'فۆرمولا بە سەرکەوتوویی زیادکرا',
                    timer: 1500,
                    showConfirmButton: false
                });
                // Close modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('addFormulaModal'));
                if (modal) modal.hide();
                addFormulaForm.reset();
                // Optionally reload formulas table
                if (typeof loadFormulas === 'function') loadFormulas();
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'هەڵە',
                    text: data.message || 'هەڵەیەک ڕووی دا',
                });
            }
            submitting = false;
        })
        .catch((error) => {
            console.error('Fetch error:', error);
            Swal.fire({
                icon: 'error',
                title: 'هەڵە',
                text: 'هەڵەیەک ڕووی دا',
            });
            submitting = false;
        });
    });
}
