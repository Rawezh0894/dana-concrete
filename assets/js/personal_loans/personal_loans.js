(function () {
    const canManage = window.PERSONAL_LOAN_CAN_MANAGE === true;
    const base = '../process/personal_loans/';

    let personsCache = [];

    function fmtUsd(n) {
        return '$' + parseFloat(n || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function fmtIqd(n) {
        return parseFloat(n || 0).toLocaleString('en-US', { maximumFractionDigits: 0 }) + ' د.ع';
    }

    async function fetchJson(url, options) {
        const res = await fetch(url, options);
        return res.json();
    }

    function updateSummary(summary) {
        if (!summary) return;
        const usdEl = document.getElementById('summaryRemainingUsd');
        const iqdEl = document.getElementById('summaryRemainingIqd');
        if (usdEl) usdEl.textContent = fmtUsd(summary.total_usd);
        if (iqdEl) iqdEl.textContent = fmtIqd(summary.total_iqd);
    }

    function fillPersonSelects() {
        const issueSel = document.getElementById('issue_person_id');
        if (!issueSel) return;
        issueSel.innerHTML = '<option value="">-- هەلبژێرە --</option>';
        personsCache.forEach(p => {
            const opt = document.createElement('option');
            opt.value = p.id;
            opt.textContent = p.name;
            issueSel.appendChild(opt);
        });
    }

    async function loadPersons() {
        const data = await fetchJson(base + 'select_persons.php');
        if (!data.success) {
            Swal.fire('هەڵە', data.message || 'بارکردن سەرنەکەوت', 'error');
            return;
        }
        personsCache = data.persons || [];
        updateSummary(data.summary);
        fillPersonSelects();
        renderPersonsTable();
    }

    function renderPersonsTable() {
        const tbody = document.getElementById('persons-tbody');
        if (!tbody) return;
        if (!personsCache.length) {
            tbody.innerHTML = '<tr><td colspan="' + (canManage ? 6 : 5) + '" class="text-muted py-4">هیچ کەسێک نییە</td></tr>';
            return;
        }
        tbody.innerHTML = personsCache.map((p, i) => {
            let actions = '';
            if (canManage) {
                actions = `
                    <button type="button" class="btn btn-sm btn-warning me-1 btn-edit-person" data-id="${p.id}" data-name="${escapeAttr(p.name)}" data-mobile="${escapeAttr(p.mobile || '')}" data-notes="${escapeAttr(p.notes || '')}"><i class="fa fa-edit"></i></button>
                    <button type="button" class="btn btn-sm btn-danger btn-delete-person" data-id="${p.id}"><i class="fa fa-trash"></i></button>`;
            }
            return `<tr>
                <td>${i + 1}</td>
                <td>${escapeHtml(p.name)}</td>
                <td>${escapeHtml(p.mobile || '—')}</td>
                <td>${fmtUsd(p.active_remaining_usd)}</td>
                <td>${fmtIqd(p.active_remaining_iqd)}</td>
                ${canManage ? '<td>' + actions + '</td>' : ''}
            </tr>`;
        }).join('');
    }

    async function loadActiveLoans() {
        const tbody = document.getElementById('active-loans-tbody');
        if (!tbody) return;
        const data = await fetchJson(base + 'active_loans_list.php');
        if (!data.success) {
            tbody.innerHTML = '<tr><td colspan="' + (canManage ? 6 : 5) + '" class="text-danger">هەڵە</td></tr>';
            return;
        }
        const loans = data.loans || [];
        if (!loans.length) {
            tbody.innerHTML = '<tr><td colspan="' + (canManage ? 6 : 5) + '" class="text-muted py-4">هیچ قەرزی چالاک نییە</td></tr>';
            return;
        }
        tbody.innerHTML = loans.map((l, i) => {
            const repayBtn = canManage
                ? `<button type="button" class="btn btn-sm btn-success btn-repay-loan"
                    data-loan-id="${l.loan_id}" data-name="${escapeAttr(l.person_name)}"
                    data-rem-usd="${l.remaining_usd}" data-rem-iqd="${l.remaining_iqd}">
                    <i class="fas fa-hand-holding-usd"></i> وەرگرتنەوە</button>`
                : '—';
            return `<tr>
                <td>${i + 1}</td>
                <td>${escapeHtml(l.person_name)}</td>
                <td>${escapeHtml(l.loan_date)}</td>
                <td>${fmtUsd(l.remaining_usd)}</td>
                <td>${fmtIqd(l.remaining_iqd)}</td>
                ${canManage ? '<td>' + repayBtn + '</td>' : ''}
            </tr>`;
        }).join('');
    }

    function escapeHtml(s) {
        const d = document.createElement('div');
        d.textContent = s == null ? '' : String(s);
        return d.innerHTML;
    }

    function escapeAttr(s) {
        return String(s || '').replace(/"/g, '&quot;').replace(/</g, '&lt;');
    }

    function reloadAll() {
        return Promise.all([loadPersons(), loadActiveLoans()]);
    }

    function computeRepayPreview() {
        const rate = parseFloat(document.getElementById('dolar_rate')?.value) || 150000;
        const ratePerUsd = rate / 100;
        const recvUsd = parseFloat(document.getElementById('received_usd')?.value) || 0;
        const recvIqd = parseFloat(document.getElementById('received_iqd')?.value) || 0;
        const chUsd = parseFloat(document.getElementById('change_back_usd')?.value) || 0;
        const chIqd = parseFloat(document.getElementById('change_back_iq')?.value) || 0;
        const netUsd = recvUsd - chUsd + (recvIqd - chIqd) / ratePerUsd;
        const el = document.getElementById('repay_net_preview');
        if (el) {
            el.value = '≈ ' + netUsd.toFixed(2) + ' $ (دوای باقی، بە کۆی گشتی)';
        }
    }

    async function fetchDollarRate() {
        try {
            const res = await fetch('https://dinarapi.hediworks.site/api/get-price?id=8&api_token=S3gl9SVEkZ1Vvc93cCjsbLLmwDvgzk');
            const data = await res.json();
            if (data && data.value) {
                const inp = document.getElementById('dolar_rate');
                if (inp) inp.value = data.value;
                computeRepayPreview();
            }
        } catch (e) {
            console.warn('rate fetch', e);
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        reloadAll();

        if (canManage) {
            document.getElementById('addPersonForm')?.addEventListener('submit', async function (e) {
                e.preventDefault();
                const fd = new FormData(this);
                const data = await fetchJson(base + 'add_person.php', { method: 'POST', body: fd });
                if (data.success) {
                    bootstrap.Modal.getInstance(document.getElementById('addPersonModal'))?.hide();
                    this.reset();
                    Swal.fire('سەرکەوتوو', data.message, 'success');
                    reloadAll();
                } else {
                    Swal.fire('هەڵە', data.message, 'error');
                }
            });

            document.getElementById('editPersonForm')?.addEventListener('submit', async function (e) {
                e.preventDefault();
                const fd = new FormData(this);
                const data = await fetchJson(base + 'update_person.php', { method: 'POST', body: fd });
                if (data.success) {
                    bootstrap.Modal.getInstance(document.getElementById('editPersonModal'))?.hide();
                    Swal.fire('سەرکەوتوو', data.message, 'success');
                    reloadAll();
                } else {
                    Swal.fire('هەڵە', data.message, 'error');
                }
            });

            document.getElementById('issueLoanForm')?.addEventListener('submit', async function (e) {
                e.preventDefault();
                const fd = new FormData(this);
                const data = await fetchJson(base + 'issue_loan.php', { method: 'POST', body: fd });
                if (data.success) {
                    bootstrap.Modal.getInstance(document.getElementById('issueLoanModal'))?.hide();
                    this.reset();
                    Swal.fire('سەرکەوتوو', data.message, 'success');
                    reloadAll();
                } else {
                    Swal.fire('هەڵە', data.message, 'error');
                }
            });

            document.getElementById('repayLoanForm')?.addEventListener('submit', async function (e) {
                e.preventDefault();
                const fd = new FormData(this);
                const data = await fetchJson(base + 'repay_loan.php', { method: 'POST', body: fd });
                if (data.success) {
                    bootstrap.Modal.getInstance(document.getElementById('repayLoanModal'))?.hide();
                    Swal.fire('سەرکەوتوو', data.message, 'success');
                    reloadAll();
                } else {
                    Swal.fire('هەڵە', data.message, 'error');
                }
            });

            document.querySelectorAll('.repay-calc').forEach(el => {
                el.addEventListener('input', computeRepayPreview);
            });
            document.getElementById('btnFetchRate')?.addEventListener('click', fetchDollarRate);

            document.body.addEventListener('click', function (e) {
                const editBtn = e.target.closest('.btn-edit-person');
                if (editBtn) {
                    document.getElementById('edit_person_id').value = editBtn.dataset.id;
                    document.getElementById('edit_person_name').value = editBtn.dataset.name;
                    document.getElementById('edit_person_mobile').value = editBtn.dataset.mobile || '';
                    document.getElementById('edit_person_notes').value = editBtn.dataset.notes || '';
                    new bootstrap.Modal(document.getElementById('editPersonModal')).show();
                }
                const delBtn = e.target.closest('.btn-delete-person');
                if (delBtn) {
                    Swal.fire({
                        title: 'دڵنیایت؟',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'بەڵێ',
                        cancelButtonText: 'نەخێر'
                    }).then(async (r) => {
                        if (!r.isConfirmed) return;
                        const fd = new FormData();
                        fd.append('id', delBtn.dataset.id);
                        const data = await fetchJson(base + 'delete_person.php', { method: 'POST', body: fd });
                        if (data.success) {
                            Swal.fire('سڕایەوە', data.message, 'success');
                            reloadAll();
                        } else {
                            Swal.fire('هەڵە', data.message, 'error');
                        }
                    });
                }
                const repayBtn = e.target.closest('.btn-repay-loan');
                if (repayBtn) {
                    document.getElementById('repay_loan_id').value = repayBtn.dataset.loanId;
                    document.getElementById('repay_person_display').textContent = repayBtn.dataset.name;
                    document.getElementById('repay_rem_usd').textContent = parseFloat(repayBtn.dataset.remUsd || 0).toFixed(2);
                    document.getElementById('repay_rem_iqd').textContent = parseFloat(repayBtn.dataset.remIqd || 0).toLocaleString('en-US');
                    document.getElementById('received_usd').value = '0';
                    document.getElementById('received_iqd').value = '0';
                    document.getElementById('change_back_usd').value = '0';
                    document.getElementById('change_back_iq').value = '0';
                    computeRepayPreview();
                    fetchDollarRate();
                    new bootstrap.Modal(document.getElementById('repayLoanModal')).show();
                }
            });
        }
    });
})();
