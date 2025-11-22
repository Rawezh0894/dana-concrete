// Add Customer AJAX handler for modal
$(document).ready(function() {
  $('#addCustomerForm').on('submit', function(e) {
    e.preventDefault();
    var formData = $(this).serialize();
    $.post('../process/customer/add_customer.php', formData, function(res) {
      console.log('Add customer response:', res);
      if (res.success) {
        Swal.fire('سەرکەوتوو!', res.message || 'کڕیار زیادکرا', 'success');
        $('#addCustomerModal').modal('hide');
        // Add new customer to selects
        var newOption = new Option(res.name, res.id, false, false);
        $('#customer_id').append(newOption).trigger('change');
        $('#edit_customer_id').append(new Option(res.name, res.id, false, false));
        $('#addCustomerForm')[0].reset();
        $('#customer_is_recipient').prop('checked', false);
      } else {
        console.error('Add customer error:', res);
        Swal.fire('هەڵە!', res.message || res.msg || 'هەڵەیەک ڕویدا', 'error');
      }
    }, 'json').fail(function(xhr) {
      console.error('AJAX error:', xhr.responseText);
      Swal.fire('هەڵە!', 'هەڵەیەک ڕویدا', 'error');
    });
  });
}); 