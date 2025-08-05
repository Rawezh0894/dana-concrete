$(document).ready(function() {
  // Global pagination state
  let currentPage = 1;
  let pageSize = 10;
  
  function getFilters() {
    return {
      customer_id: $('#filter_customer_id').val(),
      location: $('#filter_location').val(),
      formulas_id: $('#filter_formulas_id').val(),
      date_from: $('#filter_date_from').val(),
      date_to: $('#filter_date_to').val(),
      page: currentPage,
      pageSize: pageSize
    };
  }

  function loadFilteredReceipts(page = 1) {
    currentPage = page;
    const filters = getFilters();
    
    // Show loading
    $('#concreteReceiptsTable tbody').html('<tr><td colspan="13" class="text-center"><span class="spinner-border spinner-border-sm"></span> چاوەڕوان بە...</td></tr>');
    
    $.ajax({
      url: '../process/concrete_receipts/select_concrete_receipts.php',
      method: 'GET',
      data: filters,
      dataType: 'json',
      success: function(response) {
        if (response.success) {
          updateSummaryCards(response.summary);
          if (response.data.length === 0) {
            $('#concreteReceiptsTable tbody').html('<tr><td colspan="13">هیچ پسوڵەیەک نیە</td></tr>');
            renderPagination(response.pagination);
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
            
            const rowNumber = ((response.pagination.currentPage - 1) * response.pagination.pageSize) + idx + 1;
            
            rows += `<tr>
                <td>${rowNumber}</td>
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
          
          // Render pagination
          renderPagination(response.pagination);
          
          // Re-attach event handlers for the new buttons
          attachEventHandlers();
        } else {
          updateSummaryCards({total_receipts: 0, total_meter: 0, total_customers: 0});
          $('#concreteReceiptsTable tbody').html('<tr><td colspan="13">هەڵەیەک روویدا</td></tr>');
        }
      },
      error: function() {
        updateSummaryCards({total_receipts: 0, total_meter: 0, total_customers: 0});
        $('#concreteReceiptsTable tbody').html('<tr><td colspan="13">هەڵەیەک روویدا</td></tr>');
      }
    });
  }
  
  function renderPagination(pagination) {
    let paginationContainer = $('.concrete-receipts-pagination');
    if (paginationContainer.length === 0) {
      paginationContainer = $('<div class="concrete-receipts-pagination d-flex justify-content-between align-items-center mt-3"></div>');
      $('#concreteReceiptsTable').after(paginationContainer);
    }
    
    if (pagination.totalPages <= 1) {
      paginationContainer.hide();
      return;
    }
    
    paginationContainer.show();
    
    const startRecord = ((pagination.currentPage - 1) * pagination.pageSize) + 1;
    const endRecord = Math.min(pagination.currentPage * pagination.pageSize, pagination.totalRecords);
    
    let paginationHtml = `
      <div class="pagination-info">
        <small class="text-muted">
          نیشاندەر ${startRecord} بۆ ${endRecord} لە ${pagination.totalRecords} ڕیکۆرد
        </small>
      </div>
      <div class="pagination-controls">
    `;
    
    // Previous button
    paginationHtml += `
      <button class="btn btn-sm btn-outline-secondary mx-1" ${pagination.currentPage === 1 ? 'disabled' : ''} 
              onclick="loadFilteredReceipts(${pagination.currentPage - 1})">
        <svg width="18" height="18" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M13 15L8 10L13 5" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </button>
    `;
    
    // Page numbers
    for (let i = 1; i <= pagination.totalPages; i++) {
      if (i === 1 || i === pagination.totalPages || Math.abs(i - pagination.currentPage) <= 2) {
        paginationHtml += `
          <button class="btn btn-sm ${i === pagination.currentPage ? 'btn-success active' : 'btn-outline-secondary'} mx-1" 
                  onclick="loadFilteredReceipts(${i})">${i}</button>
        `;
      } else if (i === pagination.currentPage - 3 || i === pagination.currentPage + 3) {
        paginationHtml += '<span class="mx-1">...</span>';
      }
    }
    
    // Next button
    paginationHtml += `
      <button class="btn btn-sm btn-outline-secondary mx-1" ${pagination.currentPage === pagination.totalPages ? 'disabled' : ''} 
              onclick="loadFilteredReceipts(${pagination.currentPage + 1})">
        <svg width="18" height="18" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M7 5L12 10L7 15" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </button>
    </div>`;
    
    paginationContainer.html(paginationHtml);
  }

  // Bind filter events - reset to page 1 when filters change
  $('#filter_customer_id, #filter_formulas_id').on('change', function() {
    loadFilteredReceipts(1);
  });
  $('#filter_location').on('input', function() {
    loadFilteredReceipts(1);
  });
  $('#filter_date_from, #filter_date_to').on('change', function() {
    loadFilteredReceipts(1);
  });

  // Today/Yesterday filter buttons
  function setDateFilter(from, to) {
    $('#filter_date_from').val(from);
    $('#filter_date_to').val(to);
    loadFilteredReceipts(1);
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
    // Reload table with pagination
    loadFilteredReceipts(1);
  });

  function updateSummaryCards(summary) {
    // Update total receipts
    const totalReceipts = summary && summary.total_receipts !== undefined ? summary.total_receipts : 0;
    $('#summary_total_receipts').text(totalReceipts);
    
    // Update total meter
    const totalMeter = summary && summary.total_meter !== undefined ? summary.total_meter : 0;
    $('#summary_total_meter').text(totalMeter + ' m³');
    
    // Update total customers
    const totalCustomers = summary && summary.total_customers !== undefined ? summary.total_customers : 0;
    $('#summary_total_customers').text(totalCustomers);
  }

  // On page load, fetch and show the real summary values
  loadFilteredReceipts(1); // Load data immediately on page load with pagination

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
  
  // Make loadFilteredReceipts globally available
  window.loadFilteredReceipts = loadFilteredReceipts;
});
