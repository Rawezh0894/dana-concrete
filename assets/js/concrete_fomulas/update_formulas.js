// Handle edit button click for formulas
// Open update modal and fill fields

document.addEventListener('click', function(e) {
    if (e.target.closest('.edit-formula-btn')) {
        const btn = e.target.closest('.edit-formula-btn');
        const id = btn.getAttribute('data-id');
        // Fetch formula data and fill modal
        fetch(`../process/concrete_fomulas/select_formulas.php?id=${id}`)
            .then(res => res.json())
            .then(row => {
                var modal = new bootstrap.Modal(document.getElementById('updateFormulaModal'));
                modal.show();
                document.getElementById('update_formula_name').value = row.name || '';
                document.getElementById('update_formula_type').value = row.type || '';
                // Set strength type and show/hide fields
                if (row.strength_kg) {
                    document.getElementById('update_strength_type').value = 'kg';
                    document.getElementById('update_strength_kg').value = row.strength_kg;
                    document.getElementById('update_strength_kg').closest('.col-md-6').style.display = '';
                    document.getElementById('update_strength_mpa').closest('.col-md-6').style.display = 'none';
                } else if (row.strength_mpa) {
                    document.getElementById('update_strength_type').value = 'mpa';
                    document.getElementById('update_strength_mpa').value = row.strength_mpa;
                    document.getElementById('update_strength_kg').closest('.col-md-6').style.display = 'none';
                    document.getElementById('update_strength_mpa').closest('.col-md-6').style.display = '';
                } else {
                    document.getElementById('update_strength_type').value = '';
                    document.getElementById('update_strength_kg').closest('.col-md-6').style.display = 'none';
                    document.getElementById('update_strength_mpa').closest('.col-md-6').style.display = 'none';
                }
                document.getElementById('update_black_sand_kg').value = row.black_sand_kg || 0;
                document.getElementById('update_brown_sand_kg').value = row.brown_sand_kg || 0;
                document.getElementById('update_gravel_bin3_kg').value = row.gravel_bin3_kg || 0;
                document.getElementById('update_gravel_bin4_kg').value = row.gravel_bin4_kg || 0;
                document.getElementById('update_cement_cem1_kg').value = row.cement_cem1_kg || 0;
                document.getElementById('update_cement_cem2_kg').value = row.cement_cem2_kg || 0;
                document.getElementById('update_water_kg').value = row.water_kg || 0;
                document.getElementById('update_additive_kg').value = row.additive_kg || 0;
                document.getElementById('updateFormulaForm').setAttribute('data-update-id', id);
            });
    }
});

// Show/hide strength fields in update modal
const updateStrengthType = document.getElementById('update_strength_type');
if (updateStrengthType) {
    updateStrengthType.addEventListener('change', function() {
        if (this.value === 'kg') {
            document.getElementById('update_strength_kg').closest('.col-md-6').style.display = '';
            document.getElementById('update_strength_mpa').closest('.col-md-6').style.display = 'none';
            document.getElementById('update_strength_mpa').value = '';
        } else if (this.value === 'mpa') {
            document.getElementById('update_strength_kg').closest('.col-md-6').style.display = 'none';
            document.getElementById('update_strength_mpa').closest('.col-md-6').style.display = '';
            document.getElementById('update_strength_kg').value = '';
        } else {
            document.getElementById('update_strength_kg').closest('.col-md-6').style.display = 'none';
            document.getElementById('update_strength_mpa').closest('.col-md-6').style.display = 'none';
            document.getElementById('update_strength_kg').value = '';
            document.getElementById('update_strength_mpa').value = '';
        }
    });
}

// Handle update submit
const updateFormulaForm = document.getElementById('updateFormulaForm');
if (updateFormulaForm) {
    updateFormulaForm.addEventListener('submit', function(e) {
        const updateId = updateFormulaForm.getAttribute('data-update-id');
        if (updateId) {
            e.preventDefault();
            const formData = new FormData(updateFormulaForm);
            const strengthType = formData.get('strength_type');
            if (strengthType === 'kg') {
                formData.delete('strength_mpa');
            } else if (strengthType === 'mpa') {
                formData.delete('strength_kg');
            }
            formData.append('id', updateId);
            fetch('../process/concrete_fomulas/update_formulas.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('نوێکرایەوە!', data.message || 'فۆرمولا نوێکرایەوە', 'success');
                    updateFormulaForm.reset();
                    updateFormulaForm.removeAttribute('data-update-id');
                    if (typeof loadFormulas === 'function') loadFormulas();
                    const modal = bootstrap.Modal.getInstance(document.getElementById('updateFormulaModal'));
                    if (modal) modal.hide();
                } else {
                    Swal.fire('هەڵە!', data.message || 'هەڵەیەک ڕووی دا', 'error');
                }
            })
            .catch(() => {
                Swal.fire('هەڵە!', 'هەڵەیەک ڕووی دا', 'error');
            });
        }
    });
}
