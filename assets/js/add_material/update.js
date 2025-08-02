$(function() {
  $('#editMaterialForm').on('submit', function(e) {
    e.preventDefault();
    var $btn = $(this).find('button[type="submit"]');
    $btn.prop('disabled', true);
    var formData = $(this).serialize();
    
    $.ajax({
      url: '../process/add_material/update.php',
      type: 'POST',
      data: formData,
      dataType: 'json',
      success: function(jsonResponse) {
        if (jsonResponse.success === true) {
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
        $btn.prop('disabled', false);
      },
      error: function(xhr, status, error) {
        var errorMessage = 'هەڵە لە پەیوەندی بە سێرڤەر: ' + error + ' (Status: ' + xhr.status + ')';
        try {
          var errorResponse = JSON.parse(xhr.responseText);
          if (errorResponse.message) {
            errorMessage = errorResponse.message;
          }
        } catch (e) {
          if (xhr.responseText) {
            errorMessage = xhr.responseText;
          }
        }
        
        Swal.fire({
          icon: 'error',
          title: 'هەڵە',
          text: errorMessage,
          confirmButtonText: 'باشە'
        });
        $btn.prop('disabled', false);
      }
    });
  });

  // Fill edit modal with data
  $(document).on('click', '.edit-btn', function() {
    var $btn = $(this);
    
    // Populate basic fields
    $('#edit_id').val($btn.data('id'));
    $('#edit_name').val($btn.data('name'));
    $('#edit_quantity').val($btn.data('quantity'));
    $('#edit_currency_type').val($btn.data('currency_type'));
    $('#edit_purchase_price_usd').val($btn.data('purchase_price_usd'));
    $('#edit_purchase_price_iqd').val($btn.data('purchase_price_iqd'));
    
    // Populate unit type and unit-specific fields
    var unitType = $btn.data('unit_type');
    $('#edit_unit_type').val(unitType);
    
    // Hide all unit-specific fields first
    $('.edit-unit-field').hide();
    
    // Show and populate relevant unit-specific fields
    switch(unitType) {
      case 'carton':
        $('#edit_carton_fields').show();
        $('#edit_pieces_per_carton').val($btn.data('pieces_per_carton'));
        break;
      case 'barrel':
        $('#edit_barrel_fields').show();
        $('#edit_bags_per_barrel').val($btn.data('bags_per_barrel'));
        $('#edit_liters_per_bag').val($btn.data('liters_per_bag'));
        break;
      case 'bag':
        $('#edit_bag_fields').show();
        $('#edit_liters_per_bag_single').val($btn.data('liters_per_bag'));
        break;
    }
    
    // Show the modal
    $('#editMaterialModal').modal('show');
    
    // Trigger price field visibility and calculation after modal is shown
    setTimeout(function() {
      toggleEditPriceFields();
      calculateEditUnitPrice();
    }, 100);
  });
});
