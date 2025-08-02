$(function() {
  $('#addMaterialForm').on('submit', function(e) {
    e.preventDefault();
    var $btn = $(this).find('button[type="submit"]');
    $btn.prop('disabled', true);
    var formData = $(this).serialize();
    
    console.log('Submitting form data:', formData);
    
    $.ajax({
      url: '../process/add_material/add.php',
      type: 'POST',
      data: formData,
      dataType: 'json',
      success: function(jsonResponse) {
        console.log('Server response:', jsonResponse);
        
        if (jsonResponse.success === true) {
          Swal.fire({
            icon: 'success',
            title: 'سەرکەوتوو',
            text: jsonResponse.message || 'کاڵا بە سەرکەوتوویی زیادکرا!',
            confirmButtonText: 'باشە'
          }).then(() => {
            $('#addMaterialModal').modal('hide');
            if (typeof loadMaterials === 'function') loadMaterials();
            $('#addMaterialForm')[0].reset();
          });
        } else {
          Swal.fire({
            icon: 'error',
            title: 'هەڵە',
            text: jsonResponse.message || 'زیادکردن سەرکەوتوو نەبوو!',
            confirmButtonText: 'باشە'
          });
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
        
        // Try to parse error response as JSON
        var errorMessage = 'هەڵە لە پەیوەندی بە سێرڤەر: ' + error + ' (Status: ' + xhr.status + ')';
        try {
          var errorResponse = JSON.parse(xhr.responseText);
          if (errorResponse.message) {
            errorMessage = errorResponse.message;
          }
        } catch (e) {
          // If not JSON, use the raw response text
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
});
