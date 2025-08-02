$(function() {
  $('#editMaterialForm').on('submit', function(e) {
    e.preventDefault();
    var $btn = $(this).find('button[type="submit"]');
    $btn.prop('disabled', true);
    var formData = $(this).serialize();
    $.post('../process/add_material/update.php', formData, function(res) {
      if (res.trim() === 'success') {
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
          text: 'نوێکردنەوە سەرکەوتوو نەبوو!',
          confirmButtonText: 'باشە'
        });
      }
      $btn.prop('disabled', false);
    }).fail(function() {
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
