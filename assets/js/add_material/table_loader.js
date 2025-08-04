// Materials Table Loader
function loadMaterialsTable() {
    // Show loading state
    TableController.showLoading('#materialTable', ['#', 'ناوی کاڵا', 'جۆری یەکە', 'بڕی بەردەست', 'جۆری دراو', 'نرخی کڕین بە دۆلار', 'نرخی کڕین بە دینار', 'کردارەکان']);
    
    $.ajax({
        url: '../process/add_material/get_materials.php',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                renderMaterialsTable(response.data);
            } else {
                console.error('Error loading materials:', response.error);
                TableController.render('#materialTable', [], ['#', 'ناوی کاڵا', 'جۆری یەکە', 'بڕی بەردەست', 'جۆری دراو', 'نرخی کڕین بە دۆلار', 'نرخی کڕین بە دینار', 'کردارەکان']);
            }
        },
        error: function(xhr, status, error) {
            console.error('Error loading materials:', error);
            TableController.render('#materialTable', [], ['#', 'ناوی کاڵا', 'جۆری یەکە', 'بڕی بەردەست', 'جۆری دراو', 'نرخی کڕین بە دۆلار', 'نرخی کڕین بە دینار', 'کردارەکان']);
        }
    });
}

function renderMaterialsTable(materials) {
    const columns = ['#', 'ناوی کاڵا', 'جۆری یەکە', 'بڕی بەردەست', 'جۆری دراو', 'نرخی کڕین بە دۆلار', 'نرخی کڕین بە دینار', 'کردارەکان'];
    
    const tableData = materials.map((material, index) => {
        return {
            '#': index + 1,
            'ناوی کاڵا': material.name,
            'جۆری یەکە': material.unit_type,
            'بڕی بەردەست': material.quantity,
            'جۆری دراو': material.currency_type,
            'نرخی کڕین بە دۆلار': material.purchase_price_usd,
            'نرخی کڕین بە دینار': material.purchase_price_iqd,
            'کردارەکان': generateActionButtons(material)
        };
    });
    
    // Use table controller with pagination and search
    TableController.renderWithPagination('#materialTable', tableData, columns, {
        pageSize: 10,
        currentPage: 1
    });
}

function generateActionButtons(material) {
    let buttons = '';
    
    // Edit button
    if (window.hasEditPermission) {
        buttons += `<button class="btn btn-sm btn-primary edit-btn" 
                    data-id="${material.id}" 
                    data-name="${material.name}" 
                    data-unit_type="${material.unit_type}"
                    data-quantity="${material.quantity}" 
                    data-currency_type="${material.currency_type}" 
                    data-purchase_price_usd="${material.purchase_price_usd}" 
                    data-purchase_price_iqd="${material.purchase_price_iqd}"
                    data-pieces_per_carton="${material.pieces_per_carton || ''}"
                    data-buckets_per_barrel="${material.buckets_per_barrel || ''}"
                    data-liters_per_bucket="${material.liters_per_bucket || ''}"
                    data-liters_per_barrel="${material.liters_per_barrel || ''}"
                    aria-label="نوێکردنەوە">
                    <i class="bi bi-pencil"></i>
                </button> `;
    }
    
    // Delete button
    if (window.hasDeletePermission) {
        buttons += `<button class="btn btn-sm btn-danger delete-btn" 
                    data-id="${material.id}" 
                    aria-label="سڕینەوە">
                    <i class="bi bi-trash"></i>
                </button>`;
    }
    
    return buttons;
}

// Initialize when document is ready
$(document).ready(function() {
    // Load materials table on page load
    loadMaterialsTable();
    
    // Refresh table after successful operations
    $(document).on('materialAdded', function() {
        loadMaterialsTable();
    });
    
    $(document).on('materialUpdated', function() {
        loadMaterialsTable();
    });
    
    $(document).on('materialDeleted', function() {
        loadMaterialsTable();
    });
}); 