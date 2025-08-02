$(function() {
  $('#addMaterialForm').on('submit', function(e) {
    e.preventDefault();
    var $btn = $(this).find('button[type="submit"]');
    $btn.prop('disabled', true);
    var formData = $(this).serialize();
    
    console.log('Submitting form data:', formData);
    
    $.post('../process/add_material/add.php', formData, function(res) {
      console.log('Add response:', res);
      console.log('Response type:', typeof res);
      
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
        console.log('JSON parsing failed:', e);
        console.log('Raw response:', res);
        
        // If not JSON, treat as plain text
        var responseText = '';
        if (typeof res === 'string') {
          responseText = res;
        } else if (typeof res === 'object' && res !== null) {
          // If it's an object, try to get a meaningful message
          if (res.message) {
            responseText = res.message;
          } else if (res.error) {
            responseText = res.error;
          } else {
            responseText = 'هەڵەی نەناسراو';
          }
        } else {
          responseText = String(res);
        }
        
        if (responseText.trim() === 'success') {
          Swal.fire({
            icon: 'success',
            title: 'سەرکەوتوو',
            text: 'کاڵا بە سەرکەوتوویی زیادکرا!',
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
            text: 'زیادکردن سەرکەوتوو نەبوو! ' + responseText,
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
      
      var errorMessage = 'هەڵە لە پەیوەندی بە سێرڤەر';
      if (xhr.responseText) {
        try {
          var errorResponse = JSON.parse(xhr.responseText);
          if (errorResponse.message) {
            errorMessage = errorResponse.message;
          }
        } catch (e) {
          errorMessage = xhr.responseText;
        }
      }
      
      Swal.fire({
        icon: 'error',
        title: 'هەڵەی AJAX',
        text: errorMessage,
        confirmButtonText: 'باشە'
      });
      
      $btn.prop('disabled', false);
    });
  });
});
