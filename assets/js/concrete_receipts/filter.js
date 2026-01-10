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
    // Use AG Grid reload function if available, otherwise fallback to old method
    if (typeof window.reloadConcreteReceiptsFromStart === 'function') {
      // Reset to first page when filtering
      window.reloadConcreteReceiptsFromStart();
    } else if (typeof window.reloadConcreteReceipts === 'function') {
      window.reloadConcreteReceipts();
    } else if (typeof loadConcreteReceiptsTable === 'function') {
      loadConcreteReceiptsTable(1, 10);
    }
  }

  // Bind filter events
  $('#filter_customer_id, #filter_formulas_id').on('change', loadFilteredReceipts);
  $('#filter_location').on('input', loadFilteredReceipts);
  $('#filter_date_from, #filter_date_to').on('change', loadFilteredReceipts);
  
  // Search input with debounce
  let searchTimeout;
  $('#filter_search').on('input', function() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(function() {
      loadFilteredReceipts();
    }, 500); // Wait 500ms after user stops typing
  });

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
    // Clear all filters including search
    $('#filter_search').val('');
    $('#filter_customer_id').val('');
    $('#filter_location').val('');
    $('#filter_formulas_id').val('');
    $('#filter_date_from').val('');
    $('#filter_date_to').val('');
    // Remove highlights
    $('#filter_today').removeClass('active btn-primary').addClass('btn-outline-primary');
    $('#filter_yesterday').removeClass('active btn-secondary').addClass('btn-outline-secondary');
    // Reload table with reset filters
    if (typeof window.reloadConcreteReceiptsFromStart === 'function') {
      window.reloadConcreteReceiptsFromStart();
    } else if (typeof window.reloadConcreteReceipts === 'function') {
      window.reloadConcreteReceipts();
    } else if (typeof loadConcreteReceiptsTable === 'function') {
      loadConcreteReceiptsTable(1, 10);
    }
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
    // This function is now simplified since event handlers are attached directly
    // in the individual files (update_concrete_receipts.js, delete_concrete_receipts.js)
    // We keep this function for backward compatibility but it's no longer needed
    
    // Note: Event handlers are now attached using $(document).on() in the individual files,
    // which means they work for dynamically created elements without needing re-attachment
  }

  // Add print receipt event handler
  $(document).on('click', '.print-receipt', function() {
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

  // Global function to attach event handlers (called from select_concrete_receipts.js)
  window.attachConcreteReceiptsEventHandlers = attachEventHandlers;

  // Function to reload only the summary cards (no filters)
  // This is now handled in ag_grid_concrete_receipts.js, but keep for backward compatibility
  if (typeof window.reloadConcreteReceiptsSummary !== 'function') {
    window.reloadConcreteReceiptsSummary = function() {
      if (typeof window.reloadConcreteReceipts === 'function') {
        window.reloadConcreteReceipts();
      } else if (typeof loadConcreteReceiptsTable === 'function') {
        loadConcreteReceiptsTable(1, 10);
      }
    };
  }
});
