async function loadFormulas() {
    const res = await fetch('../process/concrete_fomulas/select_formulas.php');
    const data = await res.json();
    const tableData = data.map((row, idx) => ({
        '#': idx + 1,
        name: row.name || '',
        type: row.type || '',
        strength_kg: row.strength_kg || '',
        strength_mpa: row.strength_mpa || '',
        black_sand_kg: row.black_sand_kg || 0,
        brown_sand_kg: row.brown_sand_kg || 0,
        gravel_bin3_kg: row.gravel_bin3_kg || 0,
        gravel_bin4_kg: row.gravel_bin4_kg || 0,
        cement_cem1_kg: row.cement_cem1_kg || 0,
        cement_cem2_kg: row.cement_cem2_kg || 0,
        water_kg: row.water_kg || 0,
        additive_kg: row.additive_kg || 0,
        actions: `
            <button class="btn btn-sm btn-primary edit-formula-btn" data-id="${row.id}"><i class="fa fa-edit"></i></button>
            <button class="btn btn-sm btn-danger delete-formula-btn" data-id="${row.id}"><i class="fa fa-trash"></i></button>
        `
    }));
    TableController.renderWithPagination(
        '#formulasTable',
        tableData,
        ['#', 'name', 'type', 'strength_kg', 'strength_mpa', 'black_sand_kg', 'brown_sand_kg', 'gravel_bin3_kg', 'gravel_bin4_kg', 'cement_cem1_kg', 'cement_cem2_kg', 'water_kg', 'additive_kg', 'actions']
    );
}
document.addEventListener('DOMContentLoaded', loadFormulas);
