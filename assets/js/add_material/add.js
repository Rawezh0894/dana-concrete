$(function() {
  $('#addMaterialForm').on('submit', function(e) {
    e.preventDefault();
    var $btn = $(this).find('button[type="submit"]');
    $btn.prop('disabled', true);
    
    // Get form data
    var formData = new FormData(this);
    
    // Calculate prices based on unit type
    var unitType = $('#unit_type').val();
    var priceUsd = parseFloat($('#purchase_price_usd').val()) || 0;
    var priceIqd = parseFloat($('#purchase_price_iqd').val()) || 0;
    
    // Calculate prices for different units
    if (unitType === 'کارتۆن') {
      var piecesPerCarton = parseInt($('#pieces_per_carton').val()) || 1;
      var pricePerPieceUsd = priceUsd / piecesPerCarton;
      var pricePerPieceIqd = priceIqd / piecesPerCarton;
      
      formData.append('price_per_piece_usd', pricePerPieceUsd.toFixed(2));
      formData.append('price_per_piece_iqd', pricePerPieceIqd.toFixed(2));
    } else if (unitType === 'بەرمیل') {
      var bucketsPerBarrel = parseInt($('#buckets_per_barrel').val()) || 1;
      var litersPerBucket = parseFloat($('#liters_per_bucket').val()) || 1;
      var litersPerBarrel = parseFloat($('#liters_per_barrel').val()) || (bucketsPerBarrel * litersPerBucket);
      
      var pricePerBucketUsd = priceUsd / bucketsPerBarrel;
      var pricePerBucketIqd = priceIqd / bucketsPerBarrel;
      var pricePerLiterUsd = priceUsd / litersPerBarrel;
      var pricePerLiterIqd = priceIqd / litersPerBarrel;
      
      formData.append('price_per_bucket_usd', pricePerBucketUsd.toFixed(2));
      formData.append('price_per_bucket_iqd', pricePerBucketIqd.toFixed(2));
      formData.append('price_per_liter_usd', pricePerLiterUsd.toFixed(2));
      formData.append('price_per_liter_iqd', pricePerLiterIqd.toFixed(2));
    } else if (unitType === 'دەبە') {
      var litersPerBucket = parseFloat($('#liters_per_bucket').val()) || 1;
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
    
    console.log('Submitting form data with unit calculations');
    
    $.ajax({
      url: '../process/add_material/add.php',
      type: 'POST',
      data: formData,
      processData: false,
      contentType: false,
      success: function(res) {
        console.log('Server response:', res);
        
        try {
          // Try to parse as JSON first
          var jsonResponse = JSON.parse(res);
          console.log('Parsed JSON response:', jsonResponse);
          
          if (jsonResponse.status === 'success') {
            Swal.fire({
              icon: 'success',
              title: 'سەرکەوتوو',
              text: jsonResponse.message || 'کاڵا بە سەرکەوتوویی زیادکرا!',
              confirmButtonText: 'باشە'
            }).then(() => {
              $('#addMaterialModal').modal('hide');
              if (typeof loadMaterials === 'function') loadMaterials();
              $('#addMaterialForm')[0].reset();
              // Reset calculated prices display
              $('#calculated_prices').hide();
              // Trigger custom event for summary cards update
              $(document).trigger('materialAdded');
            });
          } else {
            Swal.fire({
              icon: 'error',
              title: 'هەڵە',
              text: jsonResponse.message || 'زیادکردن سەرکەوتوو نەبوو!',
              confirmButtonText: 'باشە'
            });
          }
        } catch (e) {
          // If not JSON, treat as plain text
          console.log('Response is not JSON, treating as plain text');
          
          if (res.trim() === 'success') {
            Swal.fire({
              icon: 'success',
              title: 'سەرکەوتوو',
              text: 'کاڵا بە سەرکەوتوویی زیادکرا!',
              confirmButtonText: 'باشە'
            }).then(() => {
              $('#addMaterialModal').modal('hide');
              if (typeof loadMaterials === 'function') loadMaterials();
              $('#addMaterialForm')[0].reset();
              // Reset calculated prices display
              $('#calculated_prices').hide();
              // Trigger custom event for summary cards update
              $(document).trigger('materialAdded');
            });
          } else {
            Swal.fire({
              icon: 'error',
              title: 'هەڵە',
              text: 'زیادکردن سەرکەوتوو نەبوو! Response: ' + res,
              confirmButtonText: 'باشە'
            });
          }
        }
        
        $btn.prop('disabled', false);
      },
      error: function(xhr, status, error) {
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
      }
    });
  });
});
