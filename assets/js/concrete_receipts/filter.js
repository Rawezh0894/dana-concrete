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
    // Reset to first page when filtering
    loadConcreteReceiptsTable(1, 10);
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
    // Reload table with reset filters
    loadConcreteReceiptsTable(1, 10);
  });

  function updateSummaryCards(summary) {
    function formatNumber(n) {
      if (n === null || n === undefined || n === '') return '0';
      return n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    }
    
    // Update total receipts
    const totalReceipts = summary && summary.total_receipts !== undefined ? summary.total_receipts : 0;
    $('#summary_total_receipts').text(totalReceipts);
    
    // Update total meter
    const totalMeter = summary && summary.total_meter !== undefined ? summary.total_meter : 0;
    $('#summary_total_meter').text(formatNumber(totalMeter) + ' m³');
    
    // Update total customers
    const totalCustomers = summary && summary.total_customers !== undefined ? summary.total_customers : 0;
    $('#summary_total_customers').text(totalCustomers);
  }

  // On page load, fetch and show the real summary values
  // loadFilteredReceipts(); // This is now handled by select_concrete_receipts.js

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

  // Global function to attach event handlers (called from select_concrete_receipts.js)
  window.attachConcreteReceiptsEventHandlers = attachEventHandlers;

  // Function to reload only the summary cards (no filters)
  window.reloadConcreteReceiptsSummary = function() {
    loadConcreteReceiptsTable(1, 10);
  };
});
