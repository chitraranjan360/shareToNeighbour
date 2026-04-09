document.addEventListener('DOMContentLoaded', function () {
    // Image preview on upload
    const photoInput = document.getElementById('photo');
    if (photoInput) {
        photoInput.addEventListener('change', function () {
            const file = this.files[0];
            if (!file) return;
            if (file.size > 5 * 1024 * 1024) { alert('Max 5 MB.'); this.value = ''; return; }
            const reader = new FileReader();
            reader.onload = function (e) {
                let p = document.getElementById('photo-preview');
                if (!p) { p = document.createElement('img'); p.id = 'photo-preview'; p.className = 'img-fluid rounded mt-2'; p.style.maxHeight = '300px'; photoInput.parentNode.appendChild(p); }
                p.src = e.target.result;
            };
            reader.readAsDataURL(file);
        });
    }

    // Confirm request
    document.querySelectorAll('form[action*="request_item"]').forEach(f => {
        f.addEventListener('submit', e => { if (!confirm('Request this item?')) e.preventDefault(); });
    });

    // Auto-dismiss alerts
    document.querySelectorAll('.alert-dismissible').forEach(a => {
        setTimeout(() => { bootstrap.Alert.getOrCreateInstance(a).close(); }, 5000);
    });
});


