$(document).ready(function() {
  // Initialize Select2 for filters
  if ($('#filter_customer_id').length > 0) {
    enableSelect2('#filter_customer_id', 'body');
  }
  if ($('#filter_formulas_id').length > 0) {
    enableSelect2('#filter_formulas_id', 'body');
  }
  if ($('#filter_driver_id').length > 0) {
    enableSelect2('#filter_driver_id', 'body');
  }
  
  function getFilters() {
    return {
      customer_id: $('#filter_customer_id').val(),
      location: $('#filter_location').val(),
      formulas_id: $('#filter_formulas_id').val(),
      driver_id: $('#filter_driver_id').val(),
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
                <td>${receipt.receiver_name || '-'}</td>
                <td>${formatDate(receipt.created_at)}</td>
                <td>${receipt.meter_amount !== null && receipt.meter_amount !== undefined && receipt.meter_amount !== '' ? formatNumber(receipt.meter_amount) + ' m³' : '-'}</td>
                <td>${receipt.formula_name || '-'}</td>
                <td>${receipt.pump_car_name || '-'}</td>
                <td>${receipt.pump_driver_name || '-'}</td>
                <td>${receipt.mixer_car_name || '-'}</td>
                <td>${receipt.mixer_driver_name || '-'}</td>
                <td>
                    ${window.userPermissions && window.userPermissions.canEdit ? `<button class='btn btn-sm btn-warning edit-receipt' data-id='${receipt.id}' title='نوێکردنەوە'><i class='fa fa-edit'></i></button>` : ''}
                    ${window.userPermissions && window.userPermissions.canDelete ? `<button class='btn btn-sm btn-danger delete-receipt' data-id='${receipt.id}' title='سڕینەوە'><i class='fa fa-trash'></i></button>` : ''}
                    ${window.userPermissions && window.userPermissions.canPrint ? `<button class='btn btn-sm btn-info print-receipt' data-id='${receipt.id}' title='پرێنت'><i class='fa fa-print'></i></button>` : ''}
                </td>
            </tr>`;
          });
          $('#concreteReceiptsTable tbody').html(rows);
          // Re-attach event handlers for the new buttons
          attachEventHandlers();
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
  $('#filter_customer_id, #filter_formulas_id, #filter_driver_id').on('change', loadFilteredReceipts);
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
    $('#filter_driver_id').val('');
    $('#filter_date_from').val('');
    $('#filter_date_to').val('');
    // Remove highlights
    $('#filter_today').removeClass('active btn-primary').addClass('btn-outline-primary');
    $('#filter_yesterday').removeClass('active btn-secondary').addClass('btn-outline-secondary');
    // Reload table
    if (typeof window.reloadConcreteReceipts === 'function') {
      window.reloadConcreteReceipts();
    } else {
      loadFilteredReceipts();
    }
    // Reload summary cards as well
    if (typeof window.reloadConcreteReceiptsSummary === 'function') {
      window.reloadConcreteReceiptsSummary();
    }
  });

  function updateSummaryCards(summary) {
    const filters = getFilters();
    const hasFilters = filters.customer_id || filters.location || filters.formulas_id || filters.driver_id || filters.date_from || filters.date_to;
    
    if (!hasFilters) {
      // No filters applied - show placeholder text
      $('#summary_total_receipts').text('فلتەر دابنێ');
      $('#summary_total_meter').text('فلتەر دابنێ');
      $('#summary_total_customers').text('فلتەر دابنێ');
      $('#summary_total_driver_trips').text('فلتەر دابنێ');
      $('#summary_total_driver_meters').text('فلتەر دابنێ');
      return;
    }
    
    // Update total receipts
    const totalReceipts = summary && summary.total_receipts !== undefined ? summary.total_receipts : 0;
    $('#summary_total_receipts').text(totalReceipts);
    
    // Update total meter
    const totalMeter = summary && summary.total_meter !== undefined ? summary.total_meter : 0;
    $('#summary_total_meter').text(totalMeter + ' m³');
    
    // Update total customers
    const totalCustomers = summary && summary.total_customers !== undefined ? summary.total_customers : 0;
    $('#summary_total_customers').text(totalCustomers);
    
    // Update total driver trips
    const totalDriverTrips = summary && summary.total_driver_trips !== undefined ? summary.total_driver_trips : 0;
    $('#summary_total_driver_trips').text(totalDriverTrips);
    
    // Update total driver meters
    const totalDriverMeters = summary && summary.total_driver_meters !== undefined ? summary.total_driver_meters : 0;
    $('#summary_total_driver_meters').text(totalDriverMeters + ' m³');
  }

  // On page load, fetch and show the real summary values
  loadFilteredReceipts(); // Load data immediately on page load

  // Function to attach event handlers to buttons
  function attachEventHandlers() {
    // Edit button handlers
    $('.edit-receipt').off('click').on('click', function() {
      var id = $(this).data('id');
      if (typeof window.loadEditForm === 'function') {
        window.loadEditForm(id);
      }
    });
    
    // Delete button handlers
    $('.delete-receipt').off('click').on('click', function() {
      var id = $(this).data('id');
      if (typeof window.deleteReceipt === 'function') {
        window.deleteReceipt(id);
      }
    });
    
    // Print button handlers
    $('.print-receipt').off('click').on('click', function() {
      var id = $(this).data('id');
      if (typeof Swal !== 'undefined') {
        Swal.fire({
          icon: 'question',
          title: 'چاپکردن',
          text: 'دەتەوێت پسوڵە چاپ بکەیت؟',
          showCancelButton: true,
          confirmButtonText: 'بەڵێ',
          cancelButtonText: 'نەخێر',
        }).then((result) => {
          if (result.isConfirmed) {
            window.open('../pages/central_receipts.php?id=' + id, '_self');
          }
        });
      } else {
        if (window.confirm('دەتەوێت پسوڵە چاپ بکەیت؟')) {
          window.open('../pages/central_receipts.php?id=' + id, '_self');
        }
      }
    });
  }

  // Function to reload only the summary cards (no filters)
  window.reloadConcreteReceiptsSummary = function() {
    $.ajax({
      url: '../process/concrete_receipts/select_concrete_receipts.php',
      method: 'GET',
      dataType: 'json',
      success: function(response) {
        if (response.success && response.summary) {
          updateSummaryCards(response.summary);
        }
      }
    });
  };
});
