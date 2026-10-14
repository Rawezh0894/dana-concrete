// Add Customer AJAX handler for modal
$(document).ready(function () {
  $('#addCustomerForm').on('submit', function (e) {
    e.preventDefault();
    const form = this;
    const isCompany = form.querySelector('#customer_is_company')?.checked ? '1' : '0';
    const isRecipient = form.querySelector('#customer_is_recipient')?.checked ? '1' : '0';
    let formData = $(form).serialize();
    // serialize omits unchecked boxes — force both flags
    formData = formData
      .replace(/(^|&)is_company=[^&]*/g, '')
      .replace(/(^|&)is_recipient=[^&]*/g, '');
    formData += '&is_company=' + encodeURIComponent(isCompany);
    formData += '&is_recipient=' + encodeURIComponent(isRecipient);

    $.post('../process/customer/add_customer.php', formData, function (res) {
      if (res.success) {
        Swal.fire('سەرکەوتوو!', res.message || 'کڕیار زیادکرا', 'success');
        $('#addCustomerModal').modal('hide');
        var newOption = new Option(res.name, res.id, false, false);
        $('#customer_id').append(newOption).trigger('change');
        $('#edit_customer_id').append(new Option(res.name, res.id, false, false));
        form.reset();
        $('#customer_is_recipient, #customer_is_company').prop('checked', false);
      } else {
        Swal.fire('هەڵە!', res.message || res.msg || 'هەڵەیەک ڕویدا', 'error');
      }
    }, 'json').fail(function (xhr) {
      let msg = 'هەڵەیەک ڕویدا';
      try {
        const parsed = JSON.parse(xhr.responseText);
        if (parsed.message) msg = parsed.message;
      } catch (err) { /* ignore */ }
      Swal.fire('هەڵە!', msg, 'error');
    });
  });
});
