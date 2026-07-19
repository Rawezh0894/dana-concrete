let locationsSummaryTable = null;
let locationLedgerTable = null;
let currentLocationName = '';

function printLocationStatement() {
    if(!currentLocationName) return;
    let url = `print_location_statement.php?company_id=${COMPANY_ID}&location=${encodeURIComponent(currentLocationName)}`;
    if(currentFilters.from_date) url += `&from_date=${currentFilters.from_date}`;
    if(currentFilters.to_date) url += `&to_date=${currentFilters.to_date}`;
    window.open(url, '_blank');
}

function loadLocationsSummary() {
    if (locationsSummaryTable) {
        locationsSummaryTable.destroy();
    }

    locationsSummaryTable = $('#locationsSummaryTable').DataTable({
        ajax: {
            url: '../process/company_profile/select_locations_summary.php',
            type: 'GET',
            data: function (d) {
                d.company_id = COMPANY_ID;
                d.from_date = currentFilters.from_date;
                d.to_date = currentFilters.to_date;
            },
            dataSrc: ''
        },
        columns: [
            { data: 'location', title: 'ناوی شوێن' },
            { 
                data: 'total_cost_usd', 
                title: 'کۆی گشتی کرێ ($)',
                render: function(data) {
                    return `<span class="fw-bold text-dark">${parseFloat(data).toLocaleString()} $</span>`;
                }
            },
            { 
                data: 'total_cost_iqd', 
                title: 'کۆی گشتی کرێ (د.ع)',
                render: function(data) {
                    return `<span class="fw-bold text-dark">${parseFloat(data).toLocaleString()} د.ع</span>`;
                }
            },
            { 
                data: 'total_paid_usd', 
                title: 'پارەی دراو ($)',
                render: function(data) {
                    return `<span class="fw-bold text-success">${parseFloat(data).toLocaleString()} $</span>`;
                }
            },
            { 
                data: 'total_paid_iqd', 
                title: 'پارەی دراو (د.ع)',
                render: function(data) {
                    return `<span class="fw-bold text-success">${parseFloat(data).toLocaleString()} د.ع</span>`;
                }
            },
            { 
                data: 'remaining_usd', 
                title: 'قەرزی ماوە ($)',
                render: function(data) {
                    let val = parseFloat(data);
                    let colorClass = val > 0 ? 'text-danger' : (val < 0 ? 'text-warning' : 'text-success');
                    return `<span class="fw-bold ${colorClass}">${val.toLocaleString()} $</span>`;
                }
            },
            { 
                data: 'remaining_iqd', 
                title: 'قەرزی ماوە (د.ع)',
                render: function(data) {
                    let val = parseFloat(data);
                    let colorClass = val > 0 ? 'text-danger' : (val < 0 ? 'text-warning' : 'text-success');
                    return `<span class="fw-bold ${colorClass}">${val.toLocaleString()} د.ع</span>`;
                }
            },
            {
                data: null,
                title: 'کردار',
                render: function(data, type, row) {
                    return `<button onclick="openLocationLedger('${row.location}')" class="btn btn-sm btn-teal text-white rounded-pill px-3"><i class="fas fa-eye me-1"></i> بینینی کەشف حیساب</button>`;
                }
            }
        ],
        language: {
            url: "https://cdn.datatables.net/plug-ins/1.13.6/i18n/ku.json",
            emptyTable: "هیچ داتایەک نەدۆزرایەوە بۆ شوێنەکان"
        },
        order: [[0, 'asc']]
    });
}

function openLocationLedger(locationName) {
    currentLocationName = locationName;
    $('#locationLedgerTitle').text(`کەشف حیسابی شوێن - ${locationName}`);
    
    if (locationLedgerTable) {
        locationLedgerTable.destroy();
    }

    locationLedgerTable = $('#locationLedgerTable').DataTable({
        ajax: {
            url: '../process/company_profile/select_locations_ledger.php',
            type: 'GET',
            data: {
                company_id: COMPANY_ID,
                location: locationName,
                from_date: currentFilters.from_date,
                to_date: currentFilters.to_date
            },
            dataSrc: ''
        },
        columns: [
            { data: 'date', title: 'بەروار' },
            { data: 'invoice_number', title: 'ژ.پسوڵە' },
            { data: 'driver', title: 'شۆفێر' },
            { 
                data: 'kg', 
                title: 'کێش (کگم)',
                render: function(data) { return parseFloat(data).toLocaleString(); }
            },
            { 
                data: null, 
                title: 'بڕی کرێ',
                render: function(data, type, row) {
                    let usd = parseFloat(row.total_freight_cost_usd);
                    let iqd = parseFloat(row.total_freight_cost_iqd);
                    if(usd > 0) return `<span class="text-danger">${usd.toLocaleString()} $</span>`;
                    if(iqd > 0) return `<span class="text-danger">${iqd.toLocaleString()} د.ع</span>`;
                    return '0';
                }
            },
            { 
                data: null, 
                title: 'پارەی دراو',
                render: function(data, type, row) {
                    let usd = parseFloat(row.paid_to_location_usd);
                    let iqd = parseFloat(row.paid_to_location_iqd);
                    if(usd > 0) return `<span class="text-success">${usd.toLocaleString()} $</span>`;
                    if(iqd > 0) return `<span class="text-success">${iqd.toLocaleString()} د.ع</span>`;
                    return '0';
                }
            },
            { 
                data: null, 
                title: 'باڵانس (ماوە)',
                render: function(data, type, row) {
                    let usd = parseFloat(row.remaining_usd);
                    let iqd = parseFloat(row.remaining_iqd);
                    if(usd !== 0) return `<span class="fw-bold ${usd > 0 ? 'text-danger' : 'text-success'}">${usd.toLocaleString()} $</span>`;
                    if(iqd !== 0) return `<span class="fw-bold ${iqd > 0 ? 'text-danger' : 'text-success'}">${iqd.toLocaleString()} د.ع</span>`;
                    return '0';
                }
            }
        ],
        language: {
            url: "https://cdn.datatables.net/plug-ins/1.13.6/i18n/ku.json",
            emptyTable: "هیچ مامەڵەیەک نەدۆزرایەوە لەم ماوەیەدا"
        },
        order: [[0, 'asc']],
        pageLength: 25
    });

    $('#locationLedgerModal').modal('show');
}

$(document).ready(function() {
    loadLocationsSummary();
});
