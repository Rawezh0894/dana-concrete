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

  // Debounce function for filter inputs
  function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
      const later = () => {
        clearTimeout(timeout);
        func(...args);
      };
      clearTimeout(timeout);
      timeout = setTimeout(later, wait);
    };
  }

  function loadFilteredReceipts() {
    // Reload grid with server-side pagination (page 1)
    if (typeof window.loadConcreteReceiptsGrid === 'function') {
      // Get current search text if exists
      const searchText = $('#quickSearchInput').val() || '';
      window.loadConcreteReceiptsGrid(1, 25, searchText);
    } else if (typeof window.reloadConcreteReceipts === 'function') {
      window.reloadConcreteReceipts();
    }
  }

  // Debounced version for text inputs
  const debouncedLoadFilteredReceipts = debounce(loadFilteredReceipts, 400);

  // Bind filter events
  $('#filter_customer_id, #filter_formulas_id').on('change', loadFilteredReceipts);
  $('#filter_location').on('input', debouncedLoadFilteredReceipts);
  $('#filter_date_from, #filter_date_to').on('change', loadFilteredReceipts);

  // Today/Yesterday filter buttons
  function setDateFilter(from, to) {
    $('#filter_date_from').val(from);
    $('#filter_date_to').val(to);
    loadFilteredReceipts();
  }
  
  function formatDateInput(d) {
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

  // Reset filters
  $('#filter_reset').on('click', function() {
    // Clear all filters
    $('#filter_customer_id').val('');
    $('#filter_location').val('');
    $('#filter_formulas_id').val('');
    $('#filter_date_from').val('');
    $('#filter_date_to').val('');
    // Clear quick search
    $('#quickSearchInput').val('');
    // Remove highlights
    $('#filter_today').removeClass('active btn-primary').addClass('btn-outline-primary');
    $('#filter_yesterday').removeClass('active btn-secondary').addClass('btn-outline-secondary');
    // Reload grid with reset filters
    if (typeof window.loadConcreteReceiptsGrid === 'function') {
      window.loadConcreteReceiptsGrid(1, 25, '');
    } else if (typeof window.reloadConcreteReceipts === 'function') {
      window.reloadConcreteReceipts();
    }
  });

  function updateSummaryCards(summary) {
    function formatNumber(n) {
      if (n === null || n === undefined || n === '') return '0';
      return n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    }
    
    const totalReceipts = summary && summary.total_receipts !== undefined ? summary.total_receipts : 0;
    $('#summary_total_receipts').text(totalReceipts);
    
    const totalMeter = summary && summary.total_meter !== undefined ? summary.total_meter : 0;
    $('#summary_total_meter').text(formatNumber(totalMeter) + ' m³');
    
    const totalCustomers = summary && summary.total_customers !== undefined ? summary.total_customers : 0;
    $('#summary_total_customers').text(totalCustomers);
  }

  // Print receipt event handler
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

  // Function to reload summary cards
  window.reloadConcreteReceiptsSummary = function() {
    if (typeof window.loadConcreteReceiptsGrid === 'function') {
      const searchText = $('#quickSearchInput').val() || '';
      window.loadConcreteReceiptsGrid(1, 25, searchText);
    } else if (typeof window.reloadConcreteReceipts === 'function') {
      window.reloadConcreteReceipts();
    }
  };
});
