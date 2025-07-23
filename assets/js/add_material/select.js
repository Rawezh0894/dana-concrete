// This file can be used for dynamic table reloads if needed in the future.

function loadMaterials() {
    $.get('../process/add_material/select.php', function(res) {
        $('#materialTable tbody').html(res);
        if ($('#materialTable tbody tr').length === 0 ||
            ($('#materialTable tbody tr').length === 1 && $('#materialTable tbody tr td').text().trim() === '')) {
            $('#materialTable tbody').html('<tr><td colspan="7" style="text-align:center">هیچ داتایەک نییە</td></tr>');
        }
    });
}

$(function() {
    loadMaterials();
});
