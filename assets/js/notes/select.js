// Global variables for pagination
let currentPage = 1;
let pageSize = 10; // Number of cards per page
let allNotes = [];
let filteredNotes = [];
let hasMoreNotes = true;

async function loadNotes() {
    const from = document.getElementById('filter_from').value;
    const to = document.getElementById('filter_to').value;
    const customer_id = document.getElementById('filter_customer').value;
    const is_read = document.getElementById('filter_read').value;
    
    let url = '../process/notes/select.php';
    const params = [];
    if (from) params.push('from=' + encodeURIComponent(from));
    if (to) params.push('to=' + encodeURIComponent(to));
    if (customer_id) params.push('customer_id=' + encodeURIComponent(customer_id));
    if (is_read !== '') params.push('is_read=' + encodeURIComponent(is_read));
    if (params.length) url += '?' + params.join('&');

    try {
        let res = await fetch(url);
        let text = await res.text();
        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            console.error('Raw response from select.php:', text);
            showAlert('error', 'هەڵەیەک لە وەڵامەکەی سێرڤەر هەیە');
            return;
        }

        if (!data.success) {
            displayEmptyState();
            return;
        }

        allNotes = data.data;
        filteredNotes = [...allNotes];
        currentPage = 1;
        renderNotesCards();
        updateSummary();
        
    } catch (error) {
        console.error('Error loading notes:', error);
        showAlert('error', 'هەڵەیەک لە بارکردنی تێبینیەکان هەیە');
        displayEmptyState();
    }
}

function renderNotesCards() {
    const notesGrid = document.getElementById('notesGrid');
    const loadMoreBtn = document.getElementById('loadMoreBtn');
    
    if (filteredNotes.length === 0) {
        displayEmptyState();
        return;
    }

    // Calculate pagination
    const startIndex = 0;
    const endIndex = currentPage * pageSize;
    const currentNotes = filteredNotes.slice(startIndex, endIndex);

    // Generate cards HTML
    const cardsHTML = currentNotes.map(note => createNoteCard(note)).join('');
    notesGrid.innerHTML = cardsHTML;

    // Check if there are more notes to load
    hasMoreNotes = endIndex < filteredNotes.length;
    
    // Show/hide load more button
    if (hasMoreNotes) {
        loadMoreBtn.style.display = 'flex';
    } else {
        loadMoreBtn.style.display = 'none';
    }
}

function createNoteCard(note) {
    const isRead = note.is_read == 1;
    const readClass = isRead ? 'read' : 'unread';
    const statusText = isRead ? 'خوێندرا' : 'نەخوێندراو';
    
    function formatNumber(n) {
        if (n === null || n === undefined || n === '') return '-';
        return n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    }
    
    function formatTime(timeString) {
        if (!timeString) return '-';
        
        try {
            const [hours, minutes, seconds] = timeString.split(':');
            const hour = parseInt(hours);
            const ampm = hour >= 12 ? 'PM' : 'AM';
            const displayHour = hour % 12 || 12;
            return `${displayHour.toString().padStart(2, '0')}:${minutes}:${seconds} ${ampm}`;
        } catch (error) {
            return timeString;
        }
    }

    return `
        <div class="note-card ${readClass}" data-note-id="${note.id}">
            <div class="note-card-header">
                <div class="note-card-date">${note.date || '-'}</div>
                <div class="note-card-time">${formatTime(note.time)}</div>
                <div class="note-card-customer">${note.customer_name || '-'}</div>
                <div class="note-card-location">${note.location || '-'}</div>
                <div class="note-card-status ${readClass}">${statusText}</div>
            </div>
            
            <div class="note-card-body">
                <div class="note-info-grid">
                    <div class="note-info-item">
                        <div class="note-info-label">وەرگر</div>
                        <div class="note-info-value" data-label="وەرگر:">${note.recipient || '-'}</div>
                    </div>
                    <div class="note-info-item">
                        <div class="note-info-label">بڕ (م³)</div>
                        <div class="note-info-value amount" data-label="بڕ:">${note.meter_amount ? formatNumber(note.meter_amount) + ' م³' : '-'}</div>
                    </div>
                    <div class="note-info-item">
                        <div class="note-info-label">فۆرمولا</div>
                        <div class="note-info-value highlight" data-label="فۆرمولا:">${note.formula_name || '-'}</div>
                    </div>
                </div>
                
                <!-- میکسەر و پەمپ بەشەکان لە تەنیشتی یەک -->
                <div class="mixer-pump-container">
                    <!-- میکسەر بەش -->
                    <div class="note-section">
                        <div class="note-section-title">میکسەر</div>
                        <div class="note-section-content">
                            <div class="note-info-value">${note.mixer_car_name || '-'}</div>
                            <div class="note-info-value">${note.mixer_driver_name || '-'}</div>
                        </div>
                    </div>
                    
                    <!-- پەمپ بەش -->
                    <div class="note-section">
                        <div class="note-section-title">پەمپ</div>
                        <div class="note-section-content">
                            <div class="note-info-value">${note.pump_car_name || '-'}</div>
                            <div class="note-info-value">${note.pump_driver_name || '-'}</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="note-card-footer">
                <div class="note-card-actions">
                    ${window.userPermissions && window.userPermissions.canEdit ? 
                        `<button class='btn-edit edit-note' data-id='${note.id}' title='نوێکردنەوە'>
                            <i class='fa fa-edit'></i> نوێکردنەوە
                        </button>` : ''
                    }
                    ${window.userPermissions && window.userPermissions.canDelete ? 
                        `<button class='btn-delete delete-note' data-id='${note.id}' title='سڕینەوە'>
                            <i class='fa fa-trash'></i> سڕینەوە
                        </button>` : ''
                    }
                    <button class='btn-convert convert-to-receipt ${!isRead ? 'disabled' : ''}' data-id='${note.id}' title='${isRead ? 'گۆڕین بۆ پسووڵە' : 'تێبینیەکە پێویستە خوێندراوەتەوە پێش گۆڕینی بۆ پسووڵە'}'>
                        <i class='fa fa-file-invoice'></i> پسووڵە
                    </button>
                    ${!isRead && window.userPermissions && window.userPermissions.canMarkRead ? 
                        `<button class='btn-mark-read mark-as-read' data-id='${note.id}' title='نیشانەکردن وەک خوێندراو'>
                            <i class='fa fa-check'></i> خوێندن
                        </button>` : ''
                    }
                </div>
            </div>
        </div>
    `;
}



function displayEmptyState() {
    const notesGrid = document.getElementById('notesGrid');
    const loadMoreBtn = document.getElementById('loadMoreBtn');
    
    notesGrid.innerHTML = `
        <div class="empty-state">
            <i class="fas fa-sticky-note"></i>
            <h3>هیچ تێبینیەک نەدۆزرایەوە</h3>
            <p>هیچ تێبینیەک بە پێی فلتەرەکان نەدۆزرایەوە</p>
        </div>
    `;
    
    loadMoreBtn.style.display = 'none';
}

function loadMore() {
    if (hasMoreNotes) {
        currentPage++;
        renderNotesCards();
    }
}

function updateSummary() {
    const totalNotes = allNotes.length;
    const readNotes = allNotes.filter(note => note.is_read == 1).length;
    const unreadNotes = allNotes.filter(note => note.is_read == 0).length;
    
    document.getElementById('summary_total_notes').textContent = totalNotes;
    document.getElementById('summary_read_notes').textContent = readNotes;
    document.getElementById('summary_unread_notes').textContent = unreadNotes;
}

// Load more event listener
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('loadMoreBtn')?.addEventListener('click', loadMore);
    
    // Add event listeners for mark as read buttons
    document.addEventListener('click', function(e) {
        if (e.target.closest('.mark-as-read')) {
            const noteId = e.target.closest('.mark-as-read').getAttribute('data-id');
            markAsRead(noteId);
        }
    });
});

// Filter events
document.getElementById('filter_from')?.addEventListener('input', function() {
    currentPage = 1;
    loadNotes();
});

document.getElementById('filter_to')?.addEventListener('input', function() {
    currentPage = 1;
    loadNotes();
});

document.getElementById('filter_customer')?.addEventListener('change', function() {
    currentPage = 1;
    loadNotes();
});

document.getElementById('filter_read')?.addEventListener('change', function() {
    currentPage = 1;
    loadNotes();
});

// Filter buttons
document.getElementById('filterToday')?.addEventListener('click', function() {
    // Remove active class from all filter buttons
    document.querySelectorAll('#filterToday, #filterTomorrow, #filterYesterday').forEach(btn => {
        btn.classList.remove('active');
    });
    
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('filter_from').value = today;
    document.getElementById('filter_to').value = today;
    document.getElementById('filter_customer').value = '';
    document.getElementById('filter_read').value = '';
    
    // Reset Select2 dropdowns
    $('#filter_customer').val('').trigger('change');
    $('#filter_read').val('').trigger('change');
    
    currentPage = 1;
    
    // Add active class to this button
    this.classList.add('active');
    
    loadNotes();
});

document.getElementById('filterTomorrow')?.addEventListener('click', function() {
    // Remove active class from all filter buttons
    document.querySelectorAll('#filterToday, #filterTomorrow, #filterYesterday').forEach(btn => {
        btn.classList.remove('active');
    });
    
    const tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);
    const tomorrowFormatted = tomorrow.toISOString().split('T')[0];
    document.getElementById('filter_from').value = tomorrowFormatted;
    document.getElementById('filter_to').value = tomorrowFormatted;
    document.getElementById('filter_customer').value = '';
    document.getElementById('filter_read').value = '';
    
    // Reset Select2 dropdowns
    $('#filter_customer').val('').trigger('change');
    $('#filter_read').val('').trigger('change');
    
    currentPage = 1;
    
    // Add active class to this button
    this.classList.add('active');
    
    loadNotes();
});

document.getElementById('filterYesterday')?.addEventListener('click', function() {
    // Remove active class from all filter buttons
    document.querySelectorAll('#filterToday, #filterTomorrow, #filterYesterday').forEach(btn => {
        btn.classList.remove('active');
    });
    
    const yesterday = new Date();
    yesterday.setDate(yesterday.getDate() - 1);
    const yesterdayFormatted = yesterday.toISOString().split('T')[0];
    document.getElementById('filter_from').value = yesterdayFormatted;
    document.getElementById('filter_to').value = yesterdayFormatted;
    document.getElementById('filter_customer').value = '';
    document.getElementById('filter_read').value = '';
    
    // Reset Select2 dropdowns
    $('#filter_customer').val('').trigger('change');
    $('#filter_read').val('').trigger('change');
    
    currentPage = 1;
    
    // Add active class to this button
    this.classList.add('active');
    
    loadNotes();
});

// Clear filter button
document.getElementById('clearFilterBtn')?.addEventListener('click', function() {
    // Remove active class from all filter buttons
    document.querySelectorAll('#filterToday, #filterTomorrow, #filterYesterday').forEach(btn => {
        btn.classList.remove('active');
    });
    
    document.getElementById('filter_from').value = '';
    document.getElementById('filter_to').value = '';
    document.getElementById('filter_customer').value = '';
    document.getElementById('filter_read').value = '';
    
    // Reset Select2 dropdowns
    $('#filter_customer').val('').trigger('change');
    $('#filter_read').val('').trigger('change');
    
    currentPage = 1;
    loadNotes();
});

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Set default filter to tomorrow (بەیانی)
    const tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);
    const tomorrowFormatted = tomorrow.toISOString().split('T')[0];
    document.getElementById('filter_from').value = tomorrowFormatted;
    document.getElementById('filter_to').value = tomorrowFormatted;
    
    // Set tomorrow button as active by default
    document.getElementById('filterTomorrow')?.classList.add('active');
    
    // Initialize Select2 for filters if not already done
    if ($('#filter_customer').length > 0 && !$('#filter_customer').hasClass('select2-hidden-accessible')) {
        enableSelect2('#filter_customer', 'body');
    }
    if ($('#filter_read').length > 0 && !$('#filter_read').hasClass('select2-hidden-accessible')) {
        enableSelect2('#filter_read', 'body');
    }
    
    loadNotes();
    
    // Add event listeners for convert to receipt buttons
    document.addEventListener('click', function(e) {
        if (e.target.closest('.convert-to-receipt')) {
            const button = e.target.closest('.convert-to-receipt');
            
            // Check if button is disabled
            if (button.classList.contains('disabled')) {
                return; // Prevent action if disabled
            }
            
            const noteId = button.getAttribute('data-id');
            convertToReceipt(noteId);
        }
    });
});
window.reloadNotes = loadNotes;

// Convert note to receipt function
function convertToReceipt(noteId) {
    // Find the note data
    const note = allNotes.find(n => n.id == noteId);
    if (!note) {
        showAlert('error', 'تێبینیەکە نەدۆزرایەوە');
        return;
    }
    
    // Check if note is read before allowing conversion
    if (note.is_read != 1) {
        showAlert('warning', 'تێبینیەکە پێویستە خوێندراوەتەوە پێش گۆڕینی بۆ پسووڵە');
        return;
    }
    
    // Prepare data for concrete receipts page
    const params = new URLSearchParams();
    params.append('open_add', '1');
    params.append('customer_id', note.customer_id);
    params.append('location', note.location);
    params.append('receiver_name', note.recipient || '');
    // Note: meter_amount is intentionally not sent to allow manual entry
    // params.append('meter_amount', note.meter_amount);
    params.append('formula_id', note.formula_id);
    params.append('mixer_car_id', note.mixer_car_id || '');
    params.append('mixer_driver_id', note.mixer_driver_id || '');
    params.append('pump_car_id', note.pump_car_id || '');
    params.append('pump_driver_id', note.pump_driver_id || '');
    
    // Redirect to concrete receipts page with data
    window.location.href = `concrete_receipts.php?${params.toString()}`;
}

// Flag to prevent multiple mark as read operations
let isMarkingAsRead = false;

// Mark note as read function
async function markAsRead(noteId) {
    // Prevent multiple mark as read operations
    if (isMarkingAsRead) {
        showAlert('warning', 'تکایە چاوەڕوان بە...');
        return;
    }

    // Set marking as read flag
    isMarkingAsRead = true;

    try {
        const response = await fetch('../process/notes/mark_as_read.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                note_id: noteId
            })
        });

        const result = await response.json();

        if (result.success) {
            // Update the note in our local array
            const noteIndex = allNotes.findIndex(n => n.id == noteId);
            if (noteIndex !== -1) {
                allNotes[noteIndex].is_read = 1;
                // Update filtered notes as well
                const filteredIndex = filteredNotes.findIndex(n => n.id == noteId);
                if (filteredIndex !== -1) {
                    filteredNotes[filteredIndex].is_read = 1;
                }
            }
            
            // Add read class to the specific card
            const cardElement = document.querySelector(`[data-note-id="${noteId}"]`);
            if (cardElement) {
                cardElement.classList.add('read');
                cardElement.classList.remove('unread');
            }
            
            // Re-render cards and update summary
            renderNotesCards();
            updateSummary();
            
            // Dispatch custom event for real-time badge update
            document.dispatchEvent(new CustomEvent('noteMarkedAsRead'));
            
            showAlert('success', 'تێبینیەکە وەک خوێندراو نیشانەکرا');
        } else {
            showAlert('error', result.error || 'هەڵەیەک ڕویدا');
        }
    } catch (error) {
        console.error('Error marking note as read:', error);
        showAlert('error', 'هەڵەیەک لە پەیوەندی بە سێرڤەرەوە هەیە');
    } finally {
        // Reset marking as read flag
        isMarkingAsRead = false;
    }
}
