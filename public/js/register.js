/**
 * register.js  —  ShareToNeighbour registration page
 *
 * Features:
 *  - DAWA address autocomplete (unchanged logic)
 *  - Strong password live validation with coloured strength bar + checklist
 *  - Green border on valid fields, red on invalid
 *  - On submit: block empty / invalid fields and show inline "Please fill this field" messages
 *  
 */

document.addEventListener('DOMContentLoaded', function () {

    /* =========================================================
       1.  DAWA ADDRESS AUTOCOMPLETE
    ========================================================= */
    const searchInput      = document.getElementById('address_search');
    const suggestionsBox   = document.getElementById('addressSuggestions');
    const postalInput      = document.getElementById('postal_code');
    const streetInput      = document.getElementById('street');
    const houseInput       = document.getElementById('house_number');
    const municipalityInput = document.getElementById('municipality');
    const dawaIdInput      = document.getElementById('dawa_id');
    const latInput         = document.getElementById('latitude');
    const lngInput         = document.getElementById('longitude');

    if (searchInput && suggestionsBox) {
        let debounceTimer = null;

        function clearSuggestions() {
            suggestionsBox.innerHTML = '';
            suggestionsBox.style.display = 'none';
        }

        function resetAddressFields() {
            [postalInput, streetInput, houseInput, municipalityInput,
             dawaIdInput, latInput, lngInput].forEach(el => { if (el) el.value = ''; });
        }

        searchInput.addEventListener('input', function () {
            resetAddressFields();
            const q = searchInput.value.trim();
            if (q.length < 3) { clearSuggestions(); return; }

            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(async function () {
                try {
                    const res  = await fetch('?api=dawa_autocomplete&q=' + encodeURIComponent(q));
                    const data = await res.json();

                    suggestionsBox.innerHTML = '';
                    if (!Array.isArray(data) || data.length === 0) { clearSuggestions(); return; }

                    data.forEach(function (item) {
                        const btn = document.createElement('button');
                        btn.type      = 'button';
                        btn.className = 'list-group-item list-group-item-action';
                        btn.textContent = item.tekst || 'Unknown address';

                        btn.onclick = async function () {
                            try {
                                const href = item?.adresse?.href || '';
                                if (!href) return;

                                const dres   = await fetch('?api=dawa_address&href=' + encodeURIComponent(href));
                                const detail = await dres.json();

                                if (!dres.ok) { resetAddressFields(); return; }

                                if (streetInput)       streetInput.value       = detail.street       || '';
                                if (postalInput)       postalInput.value       = detail.postal_code  || '';
                                if (municipalityInput) municipalityInput.value = detail.municipality || '';
                                if (dawaIdInput)       dawaIdInput.value       = detail.id           || '';

                                const house = (detail.house_number || '').trim();
                                const apt   = (detail.apartment    || '').trim();
                                if (houseInput) houseInput.value = house !== '' ? house : apt;

                                if (latInput) latInput.value = detail.lat ?? '';
                                if (lngInput) lngInput.value = detail.lng ?? '';

                                searchInput.value = item.tekst || '';
                                clearSuggestions();

                                // Re-validate address block after DAWA fill
                                markValid(searchInput);
                            } catch (err) {
                                console.error('Detail fetch failed:', err);
                                resetAddressFields();
                            }
                        };

                        suggestionsBox.appendChild(btn);
                    });

                    suggestionsBox.style.display = 'block';
                } catch (err) {
                    console.error('Autocomplete failed:', err);
                    clearSuggestions();
                }
            }, 250);
        });

        document.addEventListener('click', function (e) {
            if (!suggestionsBox.contains(e.target) && e.target !== searchInput) {
                clearSuggestions();
            }
        });
    }


    /* =========================================================
       2.  HELPERS  — border feedback
    ========================================================= */

    /**
     * Returns the .invalid-feedback div for a field.
     */
    function getFeedbackEl(input) {
        // Determine the element whose next sibling the feedback div should be
        const anchor = input.closest('.input-icon') || input;

        // Check if the very next sibling is already our feedback div
        const next = anchor.nextElementSibling;
        if (next && next.classList.contains('invalid-feedback')) {
            return next;
        }

        // Also check one step further (e.g. suggestions box sits between)
        if (next) {
            const afterNext = next.nextElementSibling;
            if (afterNext && afterNext.classList.contains('invalid-feedback')) {
                return afterNext;
            }
        }

        // Create and insert immediately after the anchor
        const fb = document.createElement('div');
        fb.className = 'invalid-feedback';
        fb.style.display = 'block'; // we control visibility ourselves via textContent
        anchor.insertAdjacentElement('afterend', fb);
        return fb;
    }

    function markInvalid(input, message) {
        input.classList.remove('is-valid');
        input.classList.add('is-invalid');
        const fb = getFeedbackEl(input);
        fb.textContent  = message || 'Please fill this field.';
        fb.style.display = 'block';
    }

    function markValid(input) {
        input.classList.remove('is-invalid');
        input.classList.add('is-valid');
        const fb = getFeedbackEl(input);
        fb.textContent   = '';
        fb.style.display = 'none';
    }

    function clearMark(input) {
        input.classList.remove('is-invalid', 'is-valid');
        const fb = getFeedbackEl(input);
        fb.textContent   = '';
        fb.style.display = 'none';
    }


    /* =========================================================
       3.  FIELD REFERENCES
    ========================================================= */
    const usernameInput = document.getElementById('username');
    const emailInput    = document.getElementById('email');
    const fullNameInput = document.getElementById('full_name');
    const passwordInput = document.getElementById('password');
    const confirmInput  = document.getElementById('confirm_password');
    const form          = document.querySelector('form.auth-form');

    const emailRegex    = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;


    /* =========================================================
       4.  SIMPLE FIELD VALIDATORS  (live + on-submit)
    ========================================================= */

    function validateField(input, testFn, errorMsg, liveMode) {
        const v = (input?.value ?? '').trim();
        if (liveMode && v === '') { clearMark(input); return false; }
        if (!testFn(v)) { markInvalid(input, errorMsg); return false; }
        markValid(input); return true;
    }

    function validateUsername(live) {
        return validateField(usernameInput, v => v.length >= 5,
            'Username must be at least 5 characters.', live);
    }

    function validateEmail(live) {
        return validateField(emailInput, v => emailRegex.test(v),
            'Please enter a valid email address.', live);
    }

    function validateFullName(live) {
        return validateField(fullNameInput, v => v.length >= 10,
            'Full name must be at least 10 characters.', live);
    }

    function validateAddress(live) {
        const dawaOk = (dawaIdInput?.value ?? '').trim() !== '';
        if (live && (searchInput?.value ?? '').trim() === '') { clearMark(searchInput); return false; }
        if (!dawaOk) {
            if (searchInput) markInvalid(searchInput, 'Please select an address from the suggestions.');
            return false;
        }
        if (searchInput) markValid(searchInput);
        return true;
    }

    function validateConfirm(live) {
        const p = passwordInput?.value ?? '';
        const c = confirmInput?.value  ?? '';
        if (live && c === '') { clearMark(confirmInput); return false; }
        if (c !== p) { markInvalid(confirmInput, 'Passwords do not match.'); return false; }
        markValid(confirmInput); return true;
    }


    /* =========================================================
       5.  PASSWORD STRENGTH  (live validation)
    ========================================================= */

    const PASSWORD_RULES = [
        { id: 'len',   label: 'At least 8 characters',         test: p => p.length >= 8 },
        { id: 'upper', label: 'One uppercase letter (A–Z)',     test: p => /[A-Z]/.test(p) },
        { id: 'lower', label: 'One lowercase letter (a–z)',     test: p => /[a-z]/.test(p) },
        { id: 'digit', label: 'One number (0–9)',               test: p => /\d/.test(p) },
        { id: 'spec',  label: 'One special character (!@#$…)',  test: p => /[^A-Za-z0-9]/.test(p) },
    ];

    const STRENGTH_LEVELS = [
        { label: 'Too weak',    color: '#dc3545', width: '20%'  },
        { label: 'Weak',        color: '#fd7e14', width: '40%'  },
        { label: 'Fair',        color: '#ffc107', width: '60%'  },
        { label: 'Strong',      color: '#198754', width: '80%'  },
        { label: 'Very strong', color: '#0d6efd', width: '100%' },
    ];

    /* Build strength UI below the password field */
    function buildPasswordUI() {
        if (!passwordInput) return;

        const col = passwordInput.closest('.col-md-6') || passwordInput.parentElement;

        // Remove old hint text if present (we replace it)
        const oldHint = col.querySelector('.form-text');
        if (oldHint) oldHint.remove();


        // Strength bar
        const bar  = document.createElement('div');
        bar.style.cssText = 'height:5px;border-radius:4px;margin-top:6px;background:#e9ecef;overflow:hidden;';
        const fill = document.createElement('div');
        fill.id = 'pwStrengthFill';
        fill.style.cssText = 'height:100%;width:0%;border-radius:4px;transition:width .3s,background .3s;';
        bar.appendChild(fill);

        // Strength label
        const label = document.createElement('small');
        label.id = 'pwStrengthLabel';
        label.style.cssText = 'display:block;margin-top:4px;font-weight:500;font-size:.78rem;';

        // Checklist
        const list = document.createElement('ul');
        list.style.cssText = 'list-style:none;padding:0;margin:5px 0 0;font-size:.78rem;';
        PASSWORD_RULES.forEach(function (rule) {
            const li = document.createElement('li');
            li.id = 'pwRule-' + rule.id;
            li.style.cssText = 'color:#adb5bd;transition:color .2s;';
            li.innerHTML = '<span class="rule-icon">✗</span> ' + rule.label;
            list.appendChild(li);
        });

        col.appendChild(bar);
        col.appendChild(label);
        col.appendChild(list);
    }

    /** Returns true only when all 5 rules pass */
    function evaluatePassword(live) {
        const p      = passwordInput?.value ?? '';
        const fill   = document.getElementById('pwStrengthFill');
        const lbl    = document.getElementById('pwStrengthLabel');
        const passed = PASSWORD_RULES.map(r => r.test(p));
        const score  = passed.filter(Boolean).length;

        // Update checklist items
        PASSWORD_RULES.forEach(function (rule, i) {
            const li   = document.getElementById('pwRule-' + rule.id);
            if (!li) return;
            const icon = li.querySelector('.rule-icon');
            if (passed[i]) {
                li.style.color    = '#198754';
                icon.textContent  = '✓';
            } else {
                li.style.color    = '#adb5bd';
                icon.textContent  = '✗';
            }
        });

        // Update bar
        if (fill && lbl) {
            if (p.length === 0) {
                fill.style.width  = '0%';
                lbl.textContent   = '';
            } else {
                const lvl         = STRENGTH_LEVELS[Math.max(0, score - 1)];
                fill.style.width      = lvl.width;
                fill.style.background = lvl.color;
                lbl.textContent       = 'Strength: ' + lvl.label;
                lbl.style.color       = lvl.color;
            }
        }

        const allPass = score === PASSWORD_RULES.length;

        // Border feedback
        if (live && p === '') { clearMark(passwordInput); return false; }
        if (!allPass) {
            markInvalid(passwordInput, 'Password does not meet all requirements below.');
            return false;
        }
        markValid(passwordInput);
        return true;
    }

    buildPasswordUI();


    /* =========================================================
       6.  LIVE LISTENERS
    ========================================================= */

    function attachLive(input, fn) {
        if (!input) return;
        input.addEventListener('input', () => fn(true));
        input.addEventListener('blur',  () => fn(false)); // strict on blur
    }

    attachLive(usernameInput, validateUsername);
    attachLive(emailInput,    validateEmail);
    attachLive(fullNameInput, validateFullName);
    if (searchInput) {
        searchInput.addEventListener('blur', () => validateAddress(false));
    }
    attachLive(passwordInput, evaluatePassword);
    attachLive(confirmInput,  validateConfirm);


    /* =========================================================
       7.  SUBMIT — block & show inline errors on every empty / invalid field
    ========================================================= */

    if (form) {
        form.addEventListener('submit', function (e) {

            // Run all validators in strict (non-live) mode so empty = invalid
            const ok = [
                validateUsername(false),
                validateEmail(false),
                validateFullName(false),
                validateAddress(false),
                evaluatePassword(false),
                validateConfirm(false),
            ].every(Boolean);

            if (!ok) {
                e.preventDefault();
                e.stopPropagation();

                // Scroll to first invalid field
                const first = form.querySelector('.is-invalid');
                if (first) first.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        });
    }

});