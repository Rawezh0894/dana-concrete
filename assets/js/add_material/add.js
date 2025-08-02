$(function() {
  $('#addMaterialForm').on('submit', function(e) {
    e.preventDefault();
    var $btn = $(this).find('button[type="submit"]');
    $btn.prop('disabled', true);
    var formData = $(this).serialize();
    
    console.log('Submitting form data:', formData);
    
    $.post('../process/add_material/add.php', formData, function(res) {
      console.log('Server response:', res);
      
      try {
        // Try to parse as JSON first
        var jsonResponse = JSON.parse(res);
        console.log('Parsed JSON response:', jsonResponse);
        
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
      } catch (e) {
        // If not JSON, treat as plain text
        console.log('Response is not JSON, treating as plain text');
        
        if (typeof res === 'string' && res.trim() === 'success') {
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
            text: 'زیادکردن سەرکەوتوو نەبوو! Response: ' + res,
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
});
