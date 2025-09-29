// Load dropdown data via AJAX for better performance
async function loadDropdownData() {
    try {
        const response = await fetch('../process/purchase/get_dropdown_data.php');
        const result = await response.json();
        
        if (result.success) {
            const data = result.data;
            
            // Load companies
            const companySelects = ['#company_id', '#edit_company_id', '#filter_company'];
            companySelects.forEach(selector => {
                const select = document.querySelector(selector);
                if (select) {
                    // Clear existing options except the first one
                    select.innerHTML = select.querySelector('option[value=""]').outerHTML;
                    
                    // Add new options
                    data.companies.forEach(company => {
                        const option = document.createElement('option');
                        option.value = company.id;
                        option.textContent = company.name;
                        select.appendChild(option);
                    });
                }
            });
            
            // Load drivers
            const driverSelects = ['#driver_id', '#edit_driver_id', '#filter_driver'];
            driverSelects.forEach(selector => {
                const select = document.querySelector(selector);
                if (select) {
                    // Clear existing options except the first one
                    select.innerHTML = select.querySelector('option[value=""]').outerHTML;
                    
                    // Add new options
                    data.drivers.forEach(driver => {
                        const option = document.createElement('option');
                        option.value = driver.id;
                        option.textContent = driver.name;
                        select.appendChild(option);
                    });
                }
            });
            
            // Load locations
            const locationSelects = ['#location_id', '#edit_location_id', '#filter_location'];
            locationSelects.forEach(selector => {
                const select = document.querySelector(selector);
                if (select) {
                    // Clear existing options except the first one
                    select.innerHTML = select.querySelector('option[value=""]').outerHTML;
                    
                    // Add new options
                    data.locations.forEach(location => {
                        const option = document.createElement('option');
                        option.value = location.id;
                        option.textContent = location.name;
                        select.appendChild(option);
                    });
                }
            });
            
            // Load materials
            const materialSelects = ['#material_id', '#edit_material_id', '#filter_material'];
            materialSelects.forEach(selector => {
                const select = document.querySelector(selector);
                if (select) {
                    // Clear existing options except the first one
                    select.innerHTML = select.querySelector('option[value=""]').outerHTML;
                    
                    // Add new options
                    data.materials.forEach(material => {
                        const option = document.createElement('option');
                        option.value = material.id;
                        option.textContent = material.name;
                        select.appendChild(option);
                    });
                }
            });
            
            // Load bins
            const binSelects = ['#bin_id', '#edit_bin_id'];
            binSelects.forEach(selector => {
                const select = document.querySelector(selector);
                if (select) {
                    // Clear existing options except the first one
                    select.innerHTML = select.querySelector('option[value=""]').outerHTML;
                    
                    // Add new options
                    data.bins.forEach(bin => {
                        const option = document.createElement('option');
                        option.value = bin.id;
                        option.textContent = bin.name;
                        select.appendChild(option);
                    });
                }
            });
            
            // Reinitialize Select2 for select2 elements
            $('.select2').select2({
                theme: 'bootstrap-5',
                dir: 'rtl',
                language: 'ar'
            });
            
            console.log('Dropdown data loaded successfully');
        } else {
            console.error('Failed to load dropdown data:', result.msg);
        }
    } catch (error) {
        console.error('Error loading dropdown data:', error);
    }
}

// Load dropdown data when page loads
document.addEventListener('DOMContentLoaded', function() {
    loadDropdownData();
});
