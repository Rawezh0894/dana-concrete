$(function() {
  $('#editMaterialForm').on('submit', function(e) {
    e.preventDefault();
    var $btn = $(this).find('button[type="submit"]');
    $btn.prop('disabled', true);
    
    // Get form data
    var formData = new FormData(this);
    
    // Calculate prices based on unit type
    var unitType = $('#edit_unit_type').val();
    var priceUsd = parseFloat($('#edit_purchase_price_usd').val()) || 0;
    var priceIqd = parseFloat($('#edit_purchase_price_iqd').val()) || 0;
    
    // Calculate prices for different units
    if (unitType === 'کارتۆن') {
      var piecesPerCarton = parseInt($('#edit_pieces_per_carton').val()) || 1;
      var pricePerPieceUsd = priceUsd / piecesPerCarton;
      var pricePerPieceIqd = priceIqd / piecesPerCarton;
      
      formData.append('price_per_piece_usd', pricePerPieceUsd.toFixed(2));
      formData.append('price_per_piece_iqd', pricePerPieceIqd.toFixed(2));
    } else if (unitType === 'بەرمیل') {
      var bucketsPerBarrel = parseInt($('#edit_buckets_per_barrel').val()) || 1;
      var litersPerBucket = parseFloat($('#edit_liters_per_bucket').val()) || 1;
      var litersPerBarrel = parseFloat($('#edit_liters_per_barrel').val()) || (bucketsPerBarrel * litersPerBucket);
      
      var pricePerBucketUsd = priceUsd / bucketsPerBarrel;
      var pricePerBucketIqd = priceIqd / bucketsPerBarrel;
      var pricePerLiterUsd = priceUsd / litersPerBarrel;
      var pricePerLiterIqd = priceIqd / litersPerBarrel;
      
      formData.append('price_per_bucket_usd', pricePerBucketUsd.toFixed(2));
      formData.append('price_per_bucket_iqd', pricePerBucketIqd.toFixed(2));
      formData.append('price_per_liter_usd', pricePerLiterUsd.toFixed(2));
      formData.append('price_per_liter_iqd', pricePerLiterIqd.toFixed(2));
    } else if (unitType === 'دەبە') {
      var litersPerBucket = parseFloat($('#edit_liters_per_bucket').val()) || 1;
      var pricePerLiterUsd = priceUsd / litersPerBucket;
      var pricePerLiterIqd = priceIqd / litersPerBucket;
      
      formData.append('price_per_liter_usd', pricePerLiterUsd.toFixed(2));
      formData.append('price_per_liter_iqd', pricePerLiterIqd.toFixed(2));
    } else if (unitType === 'لیتر') {
      formData.append('price_per_liter_usd', priceUsd.toFixed(2));
      formData.append('price_per_liter_iqd', priceIqd.toFixed(2));
    } else if (unitType === 'دانە') {
      formData.append('price_per_piece_usd', priceUsd.toFixed(2));
      formData.append('price_per_piece_iqd', priceIqd.toFixed(2));
    }
    
    $.ajax({
      url: '../process/add_material/update.php',
      type: 'POST',
      data: formData,
      processData: false,
      contentType: false,
      success: function(res) {
        if (res.trim() === 'success') {
          Swal.fire({
            icon: 'success',
            title: 'سەرکەوتوو',
            text: 'کاڵا بە سەرکەوتوویی نوێکرایەوە!',
            confirmButtonText: 'باشە'
          }).then(() => {
            $('#editMaterialModal').modal('hide');
            if (typeof loadMaterials === 'function') loadMaterials();
            // Reset calculated prices display
            $('#edit_calculated_prices').hide();
            // Trigger custom event for summary cards update
            $(document).trigger('materialUpdated');
          });
        } else {
          Swal.fire({
            icon: 'error',
            title: 'هەڵە',
            text: 'نوێکردنەوە سەرکەوتوو نەبوو!',
            confirmButtonText: 'باشە'
          });
        }
        $btn.prop('disabled', false);
      },
      error: function() {
        $btn.prop('disabled', false);
      }
    });
  });

  // Fill edit modal with data
  $(document).on('click', '.edit-btn', function() {
    $('#edit_id').val($(this).data('id'));
    $('#edit_name').val($(this).data('name'));
    $('#edit_unit_type').val($(this).data('unit_type'));
    $('#edit_quantity').val($(this).data('quantity'));
    $('#edit_currency_type').val($(this).data('currency_type'));
    $('#edit_purchase_price_usd').val($(this).data('purchase_price_usd'));
    $('#edit_purchase_price_iqd').val($(this).data('purchase_price_iqd'));
    $('#edit_pieces_per_carton').val($(this).data('pieces_per_carton'));
    $('#edit_buckets_per_barrel').val($(this).data('buckets_per_barrel'));
    $('#edit_liters_per_bucket').val($(this).data('liters_per_bucket'));
    $('#edit_liters_per_barrel').val($(this).data('liters_per_barrel'));
    $('#editMaterialModal').modal('show');
  });
});
