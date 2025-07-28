// Handle delete button clicks
document.addEventListener('click', function(e) {
    if (e.target.closest('.delete-note')) {
        const noteId = e.target.closest('.delete-note').getAttribute('data-id');
        deleteNote(noteId);
    }
});

// Function to delete a note
async function deleteNote(noteId) {
    // Show confirmation dialog
    const result = await Swal.fire({
        title: 'دڵنیای لە سڕینەوە؟',
        text: "ئەم کردارە ناتوانرێت گەڕێنرێتەوە!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'بەڵێ، بسڕەوە!',
        cancelButtonText: 'پاشگەزبوونەوە'
    });

    if (result.isConfirmed) {
        try {
            const formData = new FormData();
            formData.append('id', noteId);

            const response = await fetch('../process/notes/delete.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                showAlert('success', result.message);
                
                // Reload notes table
                if (window.reloadNotes) {
                    window.reloadNotes();
                }
            } else {
                showAlert('error', result.error || 'هەڵەیەک ڕویدا');
            }
        } catch (error) {
            console.error('Error deleting note:', error);
            showAlert('error', 'هەڵەیەک لە پەیوەندی بە سێرڤەرەوە هەیە');
        }
    }
}
