$(function() {
  $('#addBinForm').on('submit', function(e) {
    e.preventDefault();
    var $btn = $(this).find('button[type="submit"]');
    $btn.prop('disabled', true);
    var formData = $(this).serialize();
    $.post('../process/bins_silos/add.php', formData, function(res) {
      if (res.trim() === 'success') {
        Swal.fire({
          icon: 'success',
          title: 'سەرکەوتوو',
          text: 'بین/سایلۆ بە سەرکەوتوویی زیادکرا!',
          confirmButtonText: 'باشە'
        }).then(() => {
          $('#addBinModal').modal('hide');
          if (typeof loadBins === 'function') loadBins();
          $('#addBinForm')[0].reset();
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
