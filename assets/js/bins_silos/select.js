function loadBins() {
    $.get('../process/bins_silos/select.php', function(res) {
        $('#binsTable tbody').html(res);
        if ($('#binsTable tbody tr').length === 0 ||
            ($('#binsTable tbody tr').length === 1 && $('#binsTable tbody tr td').text().trim() === '')) {
            $('#binsTable tbody').html('<tr><td colspan="8" style="text-align:center">هیچ داتایەک نییە</td></tr>');
        }
    });
}

$(function() {
    loadBins();
    window.loadBins = loadBins; // Make available globally for add/update/delete
});
