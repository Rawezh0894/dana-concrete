$(document).ready(function() {
  function getFilters() {
    return {
      customer_id: $('#filter_customer_id').val(),
      location: $('#filter_location').val(),
      formulas_id: $('#filter_formulas_id').val(),
      date_from: $('#filter_date_from').val(),
      date_to: $('#filter_date_to').val()
    };
  }

  function loadFilteredReceipts() {
    const filters = getFilters();
    $.ajax({
      url: '../process/concrete_receipts/select_concrete_receipts.php',
      method: 'GET',
      data: filters,
      dataType: 'json',
      success: function(response) {
        if (response.success) {
          updateSummaryCards(response.summary);
          if (response.data.length === 0) {
            $('#concreteReceiptsTable tbody').html('<tr><td colspan="11">هیچ پسوڵەیەک نیە</td></tr>');
            return;
          }
          let rows = '';
          response.data.forEach(function(receipt, idx) {
            function formatNumber(n) {
              if (n === null || n === undefined || n === '') return '';
              return n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
            }
            function formatDate(dt) {
              if (!dt) return '-';
              const d = new Date(dt);
              if (isNaN(d)) return dt;
              return d.getFullYear() + '-' + String(d.getMonth()+1).padStart(2,'0') + '-' + String(d.getDate()).padStart(2,'0') + ' ' + String(d.getHours()).padStart(2,'0') + ':' + String(d.getMinutes()).padStart(2,'0');
            }
            rows += `<tr>
                <td>${idx + 1}</td>
                <td>${receipt.receipt_number || '-'}</td>
                <td>${receipt.customer_name || '-'}</td>
                <td>${receipt.location || '-'}</td>
                <td>${formatDate(receipt.created_at)}</td>
                <td>${receipt.meter_amount !== null && receipt.meter_amount !== undefined && receipt.meter_amount !== '' ? formatNumber(receipt.meter_amount) + ' m³' : '-'}</td>
                <td>${receipt.formula_name || '-'}</td>
                <td>${receipt.pump_car_name || '-'}</td>
                <td>${receipt.pump_driver_name || '-'}</td>
                <td>${receipt.mixer_car_name || '-'}</td>
                <td>${receipt.mixer_driver_name || '-'}</td>
                <td>
                    <button class='btn btn-sm btn-warning edit-receipt' data-id='${receipt.id}' title='نوێکردنەوە'><i class='fa fa-edit'></i></button>
                    <button class='btn btn-sm btn-danger delete-receipt' data-id='${receipt.id}' title='سڕینەوە'><i class='fa fa-trash'></i></button>
                </td>
            </tr>`;
          });
          $('#concreteReceiptsTable tbody').html(rows);
        } else {
          updateSummaryCards({total_receipts: 0, total_meter: 0, total_customers: 0});
          $('#concreteReceiptsTable tbody').html('<tr><td colspan="11">هەڵەیەک روویدا</td></tr>');
        }
      },
      error: function() {
        updateSummaryCards({total_receipts: 0, total_meter: 0, total_customers: 0});
        $('#concreteReceiptsTable tbody').html('<tr><td colspan="11">هەڵەیەک روویدا</td></tr>');
      }
    });
  }

  // Bind filter events
  $('#filter_customer_id, #filter_formulas_id').on('change', loadFilteredReceipts);
  $('#filter_location').on('input', loadFilteredReceipts);
  $('#filter_date_from, #filter_date_to').on('change', loadFilteredReceipts);

  // Today/Yesterday filter buttons
  function setDateFilter(from, to) {
    $('#filter_date_from').val(from);
    $('#filter_date_to').val(to);
    loadFilteredReceipts();
  }
  function formatDateInput(d) {
    // Format JS Date to yyyy-mm-dd
    return d.getFullYear() + '-' + String(d.getMonth()+1).padStart(2,'0') + '-' + String(d.getDate()).padStart(2,'0');
  }
  $('#filter_today').on('click', function() {
    const today = new Date();
    const f = formatDateInput(today);
    setDateFilter(f, f);
    $('#filter_today').addClass('active btn-primary').removeClass('btn-outline-primary');
    $('#filter_yesterday').removeClass('active btn-secondary').addClass('btn-outline-secondary');
  });
  $('#filter_yesterday').on('click', function() {
    const yesterday = new Date();
    yesterday.setDate(yesterday.getDate() - 1);
    const f = formatDateInput(yesterday);
    setDateFilter(f, f);
    $('#filter_yesterday').addClass('active btn-secondary').removeClass('btn-outline-secondary');
    $('#filter_today').removeClass('active btn-primary').addClass('btn-outline-primary');
  });
  // Remove highlight if manual date change
  $('#filter_date_from, #filter_date_to').on('input', function() {
    $('#filter_today').removeClass('active btn-primary').addClass('btn-outline-primary');
    $('#filter_yesterday').removeClass('active btn-secondary').addClass('btn-outline-secondary');
  });

  // Initial load (optional, since select_concrete_receipts.js may already do this)
  // loadFilteredReceipts();

  $('#filter_reset').on('click', function() {
    // Clear all filters
    $('#filter_customer_id').val('');
    $('#filter_location').val('');
    $('#filter_formulas_id').val('');
    $('#filter_date_from').val('');
    $('#filter_date_to').val('');
    // Remove highlights
    $('#filter_today').removeClass('active btn-primary').addClass('btn-outline-primary');
    $('#filter_yesterday').removeClass('active btn-secondary').addClass('btn-outline-secondary');
    // Reload table
    loadFilteredReceipts();
  });

  function updateSummaryCards(summary) {
    $('#summary_total_receipts').text(summary && summary.total_receipts ? summary.total_receipts : 0);
    if (summary && summary.total_meter) {
      $('#summary_total_meter').text(summary.total_meter + ' m³');
    } else {
      $('#summary_total_meter').text('0 m³');
    }
    $('#summary_total_customers').text(summary && summary.total_customers ? summary.total_customers : 0);
  }

  // Set summary cards to 0 by default on page load
  updateSummaryCards({total_receipts: 0, total_meter: 0, total_customers: 0});
});
