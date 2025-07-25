$(function() {
  $('#editBinForm').on('submit', function(e) {
    e.preventDefault();
    var $btn = $(this).find('button[type="submit"]');
    $btn.prop('disabled', true);
    var formData = $(this).serialize();
    $.post('../process/bins_silos/update.php', formData, function(res) {
      if (res.trim() === 'success') {
        Swal.fire({
          icon: 'success',
          title: 'سەرکەوتوو',
          text: 'بین/سایلۆ بە سەرکەوتوویی نوێکرایەوە!',
          confirmButtonText: 'باشە'
        }).then(() => {
          $('#editBinModal').modal('hide');
          if (typeof loadBins === 'function') loadBins();
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
    }).fail(function() {
      $btn.prop('disabled', false);
    });
  });
});
