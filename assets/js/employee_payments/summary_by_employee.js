function loadEmployeeSummary(employee_id, month = '') {
    if (!employee_id) {
        $("#employee-detail-summary").hide();
        return;
    }
    $.get('../process/employee_payments/summary_by_employee.php', { employee_id, month }, function(res) {
        if (res.error) {
            $("#employee-detail-summary").hide();
            return;
        }
        $("#card-emp-salary").text(res.salary.toLocaleString('en-US') + " د.ع");
        $("#card-emp-paid").text(res.total_paid.toLocaleString('en-US') + " د.ع");
        $("#card-emp-balance").text(res.balance.toLocaleString('en-US') + " د.ع");
        $("#employee-detail-summary").show();
    }, 'json');
}

$("#employee-filter, #month-filter").on('change', function() {
    let empID = $("#employee-filter").val();
    let month = $("#month-filter").val();
    loadEmployeeSummary(empID, month);
});

$(function(){
    let empID = $("#employee-filter").val();
    let month = $("#month-filter").val();
    loadEmployeeSummary(empID, month);
});
