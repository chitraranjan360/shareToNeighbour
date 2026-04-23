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
        setTimeout(() => { bootstrap.Alert.getOrCreateInstance(a).close(); }, 3000);
    });
});

/*
forget passeword validation 
*/
document.addEventListener('DOMContentLoaded', function () {
    const input    = document.getElementById('emailInput');
    const form     = document.getElementById('email_form');
    if (!input || !form) return;

    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    function validate(val) { return emailRegex.test(val.trim()); }

    // .invalid-feedback is INSIDE .input-icon in this form, so Bootstrap's
    // sibling selector works automatically — we just force display manually too
    const feedback = input.closest('.col-12')?.querySelector('.invalid-feedback');

    function showFeedback(show) {
        if (feedback) feedback.style.display = show ? 'block' : 'none';
    }

    // On page load: if PHP already marked the field invalid, show the message
    if (input.classList.contains('is-invalid')) {
        showFeedback(true);
    }

    // TYPING — red/green border only, never show the text message
    input.addEventListener('input', function () {
        const val = input.value.trim();
        showFeedback(false); // always hide message while typing

        if (val === '') {
            input.classList.remove('is-valid', 'is-invalid');
        } else {
            input.classList.toggle('is-valid',   validate(val));
            input.classList.toggle('is-invalid', !validate(val));
        }
    });

    // BLUR — clear borders if field left empty
    input.addEventListener('blur', function () {
        if (input.value.trim() === '') {
            input.classList.remove('is-valid', 'is-invalid');
            showFeedback(false);
        }
    });

    // SUBMIT — block and show feedback message only here
    form.addEventListener('submit', function (e) {
        if (!validate(input.value)) {
            e.preventDefault();
            input.classList.add('is-invalid');
            input.classList.remove('is-valid');
            showFeedback(true);
        }
    });
});

/*
Client side empty chat section validation, 
*/
document.addEventListener('DOMContentLoaded', function () {
    const chatForm = document.getElementById('chatForm');
    const bodyInput = document.getElementById('bodyInput');
    const submitBtn = chatForm?.querySelector('button[type="submit"]');

    if (!chatForm || !bodyInput) return;

    // Show error and disable button when input is empty
    function validateInput() {
        const isEmpty = bodyInput.value.trim() === '';

        if (isEmpty) {
            
            if (submitBtn) submitBtn.disabled = true;
        } else {
           
            if (submitBtn) submitBtn.disabled = false;
        }

        return !isEmpty;
    }
    // Validate on every keystroke so button re-enables as soon as user types
    bodyInput.addEventListener('input', validateInput);

    // Run once on load so button starts disabled if field is empty
    validateInput();

    chatForm.addEventListener('submit', function (e) {
        if (!validateInput()) {
            e.preventDefault();
        }
    });
});

//customozed conform model


