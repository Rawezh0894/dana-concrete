$(function() {
    function formatNumber(n) {
        if (n === null || n === undefined || n === '') return '';
        return n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    }
    function formatUSD(n) {
        if (!n || isNaN(n)) return '';
        return formatNumber(Number(n).toFixed(2)) + ' $';
    }
    function formatIQD(n) {
        if (!n || isNaN(n)) return '';
        return formatNumber(Number(n).toFixed(0)) + ' د.ع';
    }
    function getFilters() {
        return {
            month: $('#monthFilter').val(),
            year: $('#yearFilter').val()
        };
    }
    function loadYearsFromData(data) {
        // Find all years in data
        const years = new Set();
        if (Array.isArray(data)) {
            data.forEach(car => {
                if (car.expense_years && Array.isArray(car.expense_years)) {
                    car.expense_years.forEach(y => years.add(y));
                }
            });
        }
        if (years.size > 0) {
            const yearFilter = $('#yearFilter');
            const current = yearFilter.val();
            yearFilter.empty();
            yearFilter.append('<option value="">هەموو ساڵەکان</option>');
            Array.from(years).sort().forEach(y => {
                yearFilter.append(`<option value="${y}">${y}</option>`);
            });
            if (current) yearFilter.val(current);
        }
    }
    function loadCarExpenses() {
        const filters = getFilters();
        let url = '../process/cars_expenses/select.php';
        const params = [];
        if (filters.month) params.push('month=' + encodeURIComponent(filters.month));
        if (filters.year) params.push('year=' + encodeURIComponent(filters.year));
        if (params.length) url += '?' + params.join('&');
        $.get(url, function(res) {
            if (!res || !res.data) return;
            let total_usd = 0, total_iqd = 0, car_count = 0;
            const columns = ['#', 'car_name', 'total_iqd', 'total_usd', 'expense_count'];
            const mapped = res.data.map(function(car, idx) {
                total_usd += car.total_usd;
                total_iqd += car.total_iqd;
                car_count++;
                return {
                    '#': idx + 1,
                    car_name: car.car_name,
                    total_iqd: formatIQD(car.total_iqd),
                    total_usd: formatUSD(car.total_usd),
                    expense_count: car.expense_count
                };
            });
            TableController.renderWithPagination('#carExpensesTable', mapped, columns, { pageSize: 10 });
            $('#summary_total_usd').text('$' + total_usd.toLocaleString('en-US'));
            $('#summary_total_iqd').text(total_iqd.toLocaleString('en-US') + ' د.ع');
            $('#summary_count').text(car_count);
            // Populate year filter if data includes years
            if (res.years) {
                const yearFilter = $('#yearFilter');
                const current = yearFilter.val();
                yearFilter.empty();
                yearFilter.append('<option value="">هەموو ساڵەکان</option>');
                res.years.forEach(y => yearFilter.append(`<option value="${y}">${y}</option>`));
                if (current) yearFilter.val(current);
            }
        }, 'json');
    }
    // Filter events
    $('#monthFilter, #yearFilter').on('change', loadCarExpenses);
    $('#clearFilterBtn').on('click', function() {
        $('#monthFilter').val('');
        $('#yearFilter').val('');
        loadCarExpenses();
    });
    loadCarExpenses();
});
