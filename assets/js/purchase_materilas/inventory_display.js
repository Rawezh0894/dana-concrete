// Inventory Display Functionality for Purchase Materials
// This file handles displaying available inventory quantities for materials

// Function to format inventory quantities with unit badges
function formatInventoryDisplay(material) {
    let inventoryHtml = '';
    let hasInventory = false;
    
    // Check each unit type and create badges
    if (material.carton_quantity > 0) {
        inventoryHtml += `<span class="inventory-badge inventory-carton">کارتۆن: ${material.carton_quantity}</span> `;
        hasInventory = true;
    }
    
    if (material.piece_quantity > 0) {
        inventoryHtml += `<span class="inventory-badge inventory-piece">تەکە: ${material.piece_quantity}</span> `;
        hasInventory = true;
    }
    
    if (material.barrel_quantity > 0) {
        inventoryHtml += `<span class="inventory-badge inventory-barrel">بەرمیل: ${material.barrel_quantity}</span> `;
        hasInventory = true;
    }
    
    if (material.bag_quantity > 0) {
        inventoryHtml += `<span class="inventory-badge inventory-bag">کیسە: ${material.bag_quantity}</span> `;
        hasInventory = true;
    }
    
    if (material.liter_quantity > 0) {
        inventoryHtml += `<span class="inventory-badge inventory-liter">لیتر: ${material.liter_quantity}</span> `;
        hasInventory = true;
    }
    
    // If no inventory, show zero badge
    if (!hasInventory) {
        inventoryHtml = '<span class="inventory-badge inventory-zero">هیچ بڕێک نییە</span>';
    }
    
    return inventoryHtml;
}

// Function to create material option with inventory display
function createMaterialOption(material) {
    const inventoryDisplay = formatInventoryDisplay(material);
    
    return {
        id: material.id,
        text: material.name,
        material: material,
        html: `
            <div class="material-select-option">
                <span class="material-name">${material.name}</span>
                <div class="material-inventory">${inventoryDisplay}</div>
            </div>
        `
    };
}

// Function to update material select options with inventory
function updateMaterialSelectOptions() {
    if (typeof window.initialMaterials === 'undefined') {
        console.warn('Materials data not available');
        return;
    }
    
    // Create options with inventory display
    const options = window.initialMaterials.map(material => createMaterialOption(material));
    
    // Update any existing material selects
    $('.material-select').each(function() {
        const $select = $(this);
        const currentValue = $select.val();
        
        // Clear existing options
        $select.empty();
        
        // Add default option
        $select.append('<option value="">هەڵبژێرە</option>');
        
        // Add material options
        options.forEach(option => {
            $select.append(new Option(option.text, option.id, false, option.id == currentValue));
        });
        
        // Trigger change event to update Select2
        $select.trigger('change');
    });
}

// Function to get inventory display for a specific material
function getInventoryDisplay(materialId) {
    if (typeof window.initialMaterials === 'undefined') {
        return '<span class="inventory-badge inventory-zero">هیچ بڕێک نییە</span>';
    }
    
    const material = window.initialMaterials.find(m => m.id == materialId);
    if (!material) {
        return '<span class="inventory-badge inventory-zero">هیچ بڕێک نییە</span>';
    }
    
    return formatInventoryDisplay(material);
}

// Function to create material row with inventory display
function createMaterialRow(materialId = null) {
    const material = materialId ? window.initialMaterials.find(m => m.id == materialId) : null;
    const inventoryDisplay = material ? formatInventoryDisplay(material) : '<span class="inventory-badge inventory-zero">هیچ بڕێک نییە</span>';
    
    const rowId = 'material_row_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
    
    return `
        <tr id="${rowId}" class="material-row">
            <td>
                <select class="form-select material-select" name="materials[]" required>
                    <option value="">هەڵبژێرە</option>
                    ${window.initialMaterials.map(m => 
                        `<option value="${m.id}" ${materialId == m.id ? 'selected' : ''}>${m.name}</option>`
                    ).join('')}
                </select>
            </td>
            <td class="inventory-display">
                ${inventoryDisplay}
            </td>
            <td>
                <select class="form-select unit-type-select" name="unit_types[]" required>
                    <option value="">هەڵبژێرە</option>
                    <option value="carton" ${material && material.unit_type === 'carton' ? 'selected' : ''}>کارتۆن</option>
                    <option value="piece" ${material && material.unit_type === 'piece' ? 'selected' : ''}>تەکە</option>
                    <option value="barrel" ${material && material.unit_type === 'barrel' ? 'selected' : ''}>بەرمیل</option>
                    <option value="bag" ${material && material.unit_type === 'bag' ? 'selected' : ''}>کیسە</option>
                    <option value="liter" ${material && material.unit_type === 'liter' ? 'selected' : ''}>لیتر</option>
                </select>
            </td>
            <td>
                <input type="number" class="form-control quantity-input" name="quantities[]" min="0" step="0.01" placeholder="0.00" required>
            </td>
            <td>
                <input type="number" class="form-control price-usd-input" name="prices_usd[]" min="0" step="0.01" placeholder="0.00" required>
            </td>
            <td>
                <input type="number" class="form-control price-iqd-input" name="prices_iqd[]" min="0" step="0.01" placeholder="0.00" required>
            </td>
            <td>
                <input type="number" class="form-control total-price-input" name="total_prices[]" min="0" step="0.01" placeholder="0.00" readonly>
            </td>
            <td>
                <input type="number" class="form-control total-usd-input" name="totals_usd[]" min="0" step="0.01" placeholder="0.00" readonly>
            </td>
            <td>
                <input type="number" class="form-control total-iqd-input" name="totals_iqd[]" min="0" step="0.01" placeholder="0.00" readonly>
            </td>
            <td>
                <button type="button" class="btn btn-sm btn-danger remove-material-row">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        </tr>
    `;
}

// Function to update inventory display when material is selected
function updateInventoryDisplay($row) {
    const materialId = $row.find('.material-select').val();
    const $inventoryDisplay = $row.find('.inventory-display');
    
    if (materialId) {
        const inventoryHtml = getInventoryDisplay(materialId);
        $inventoryDisplay.html(inventoryHtml);
    } else {
        $inventoryDisplay.html('<span class="inventory-badge inventory-zero">هیچ بڕێک نییە</span>');
    }
}

// Function to refresh inventory data from server
function refreshInventoryData() {
    $.ajax({
        url: '../process/purchase_materilas/get_materials_with_inventory.php',
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                window.initialMaterials = response.data;
                updateMaterialSelectOptions();
                
                // Update existing rows
                $('.material-row').each(function() {
                    updateInventoryDisplay($(this));
                });
                
                console.log('Inventory data refreshed successfully');
            } else {
                console.error('Failed to refresh inventory data:', response.message);
            }
        },
        error: function(xhr, status, error) {
            console.error('Error refreshing inventory data:', error);
        }
    });
}

// Initialize inventory display functionality
$(document).ready(function() {
    // Update material select options on page load
    updateMaterialSelectOptions();
    
    // Handle material selection change
    $(document).on('change', '.material-select', function() {
        const $row = $(this).closest('.material-row');
        updateInventoryDisplay($row);
        
        // Auto-fill material data if available
        const materialId = $(this).val();
        if (materialId) {
            const material = window.initialMaterials.find(m => m.id == materialId);
            if (material) {
                $row.find('.unit-type-select').val(material.unit_type);
                $row.find('.price-usd-input').val(material.purchase_price_usd || 0);
                $row.find('.price-iqd-input').val(material.purchase_price_iqd || 0);
            }
        }
    });
    
    // Handle add material row button
    $(document).on('click', '#addMaterialRow, #editAddMaterialRow', function() {
        const $tableBody = $(this).closest('.modal-body').find('tbody');
        const newRow = createMaterialRow();
        $tableBody.append(newRow);
        
        // Initialize Select2 for the new row
        const $newRow = $tableBody.find('tr:last');
        $newRow.find('.material-select').select2({
            theme: 'bootstrap-5',
            placeholder: 'هەڵبژێرە',
            allowClear: true
        });
    });
    
    // Handle remove material row
    $(document).on('click', '.remove-material-row', function() {
        $(this).closest('.material-row').remove();
    });
    
    // Auto-refresh inventory data every 5 minutes
    setInterval(refreshInventoryData, 5 * 60 * 1000);
    
    // Add refresh button to summary cards
    $('#summary-cards').append(`
        <div class="col-12 text-center mt-3">
            <button type="button" class="btn btn-sm btn-outline-info" onclick="refreshInventoryData()">
                <i class="fas fa-sync-alt"></i> نوێکردنەوەی بڕی بەردەست
            </button>
        </div>
    `);
});

// Export functions for use in other scripts
window.InventoryDisplay = {
    formatInventoryDisplay,
    getInventoryDisplay,
    createMaterialRow,
    updateInventoryDisplay,
    refreshInventoryData
}; 