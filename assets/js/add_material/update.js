$(function() {
  $('#editMaterialForm').on('submit', function(e) {
    e.preventDefault();
    var $btn = $(this).find('button[type="submit"]');
    $btn.prop('disabled', true);
    var formData = $(this).serialize();
    
    $.post('../process/add_material/update.php', formData, function(res) {
      try {
        // Try to parse as JSON first
        var jsonResponse = JSON.parse(res);
        
        if (jsonResponse.status === 'success') {
          Swal.fire({
            icon: 'success',
            title: 'سەرکەوتوو',
            text: jsonResponse.message || 'کاڵا بە سەرکەوتوویی نوێکرایەوە!',
            confirmButtonText: 'باشە'
          }).then(() => {
            $('#editMaterialModal').modal('hide');
            if (typeof loadMaterials === 'function') loadMaterials();
          });
        } else {
          Swal.fire({
            icon: 'error',
            title: 'هەڵە',
            text: jsonResponse.message || 'نوێکردنەوە سەرکەوتوو نەبوو!',
            confirmButtonText: 'باشە'
          });
        }
      } catch (e) {
        // If not JSON, treat as plain text
        var responseText = typeof res === 'string' ? res : String(res);
        
        if (responseText.trim() === 'success') {
          Swal.fire({
            icon: 'success',
            title: 'سەرکەوتوو',
            text: 'کاڵا بە سەرکەوتوویی نوێکرایەوە!',
            confirmButtonText: 'باشە'
          }).then(() => {
            $('#editMaterialModal').modal('hide');
            if (typeof loadMaterials === 'function') loadMaterials();
          });
        } else {
          Swal.fire({
            icon: 'error',
            title: 'هەڵە',
            text: 'نوێکردنەوە سەرکەوتوو نەبوو! Response: ' + responseText,
            confirmButtonText: 'باشە'
          });
        }
      }
      
      $btn.prop('disabled', false);
    }).fail(function(xhr, status, error) {
      console.error('AJAX Error:', {
        status: status,
        error: error,
        responseText: xhr.responseText,
        statusCode: xhr.status
      });
      
      Swal.fire({
        icon: 'error',
        title: 'هەڵەی AJAX',
        text: 'هەڵە لە پەیوەندی بە سێرڤەر: ' + error + ' (Status: ' + xhr.status + ')',
        confirmButtonText: 'باشە'
      });
      
      $btn.prop('disabled', false);
    });
  });

  // Fill edit modal with data
  $(document).on('click', '.edit-btn', function() {
    $('#edit_id').val($(this).data('id'));
    $('#edit_name').val($(this).data('name'));
    $('#edit_quantity').val($(this).data('quantity'));
    $('#edit_currency_type').val($(this).data('currency_type'));
    $('#edit_purchase_price_usd').val($(this).data('purchase_price_usd'));
    $('#edit_purchase_price_iqd').val($(this).data('purchase_price_iqd'));
    $('#editMaterialModal').modal('show');
  });
});
