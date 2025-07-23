$(function() {
  $('#addMaterialForm').on('submit', function(e) {
    e.preventDefault();
    var $btn = $(this).find('button[type="submit"]');
    $btn.prop('disabled', true);
    var formData = $(this).serialize();
    $.post('../process/add_material/add.php', formData, function(res) {
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
        });
      } else {
        Swal.fire({
          icon: 'error',
          title: 'هەڵە',
          text: 'زیادکردن سەرکەوتوو نەبوو!',
          confirmButtonText: 'باشە'
        });
      }
      $btn.prop('disabled', false);
    }).fail(function() {
      $btn.prop('disabled', false);
    });
  });
});
