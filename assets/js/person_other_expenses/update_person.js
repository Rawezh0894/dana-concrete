// Edit modal HTML (add to page if not present)
if (!document.getElementById('editPersonModal')) {
    const modalHtml = `
    <div class="modal fade" id="editPersonModal" tabindex="-1" aria-labelledby="editPersonModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <form id="editPersonForm">
            <div class="modal-header">
              <h5 class="modal-title" id="editPersonModalLabel">دەستکاری کەس</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <input type="hidden" id="edit_person_id" name="id">
              <div class="mb-3">
                <label for="edit_person_name" class="form-label">ناوی کەس</label>
                <input type="text" class="form-control" id="edit_person_name" name="person_name" required>
              </div>
              <div class="mb-3">
                <label for="edit_person_expense_usd" class="form-label d-none">خەرجی بە دۆلار</label>
                <input type="number" step="0.01" class="form-control d-none" id="edit_person_expense_usd" name="expense_usd" value="0">
              </div>
              <div class="mb-3">
                <label for="edit_person_expense_iqd" class="form-label d-none">خەرجی بە دینار</label>
                <input type="number" step="0.01" class="form-control d-none" id="edit_person_expense_iqd" name="expense_iqd" value="0">
              </div>
              <div class="mb-3">
                <label for="edit_opening_debt_usd" class="form-label"> پارەی سەرەتایی بە دۆلار </label>
                <input type="number" step="0.01" class="form-control" id="edit_opening_debt_usd" name="opening_debt_usd" value="0">
              </div>
              <div class="mb-3">
                <label for="edit_opening_debt_iqd" class="form-label"> پارەی سەرەتایی بە دینار </label>
                <input type="number" step="0.01" class="form-control" id="edit_opening_debt_iqd" name="opening_debt_iqd" value="0">
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">داخستن</button>
              <button type="submit" class="btn btn-success" style="background: var(--seafoam-green); font-weight: bold;">نوێکردنەوە</button>
            </div>
          </form>
        </div>
      </div>
    </div>
    `;
    document.body.insertAdjacentHTML('beforeend', modalHtml);
}
// Attach edit button events (for backward compatibility, but mainly handled in AG Grid cell renderer now)
function attachEditPersonEvents() {
    // This function is kept for backward compatibility
    // The actual edit functionality is now handled in select_person.js actionCellRenderer
}
// Event listeners are now attached centrally in select_person.js after each pagination render
// Handle edit form submit
const editPersonForm = document.getElementById('editPersonForm');
if (editPersonForm) {
    editPersonForm.onsubmit = async function(e) {
        e.preventDefault();
        const formData = new FormData(editPersonForm);
        const res = await fetch('../process/person_other_expenses/update_person.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        if (data.success) {
            Swal.fire('سەرکەوتوو!', 'کەس نوێکرایەوە', 'success');
            var modal = bootstrap.Modal.getInstance(document.getElementById('editPersonModal'));
            modal.hide();
            if (typeof loadPersons === 'function') loadPersons();
        } else {
            Swal.fire('هەڵە!', data.msg || 'هەڵەیەک ڕویدا', 'error');
        }
    }
}
