document.addEventListener('DOMContentLoaded', function () {
    function setupValidation(config) {
        const form = document.querySelector(config.formSelector);
        if (!form) return;

        const fields = config.fields || [];

        function showFeedback(input, show) {
            const wrapper = input.closest('.form-group, .col-12, .mb-3, .input-group, .position-relative') || input.parentElement;
            const feedback = wrapper ? wrapper.querySelector('.invalid-feedback') : null;
            if (feedback) feedback.style.display = show ? 'block' : 'none';
        }

        function setValid(input) {
            input.classList.add('is-valid');
            input.classList.remove('is-invalid');
        }

        function setInvalid(input) {
            input.classList.add('is-invalid');
            input.classList.remove('is-valid');
        }

        function clearState(input) {
            input.classList.remove('is-valid', 'is-invalid');
            showFeedback(input, false);
        }

        function validateField(fieldConfig, showMessage = false) {
            const input = document.querySelector(fieldConfig.selector);
            if (!input) return true;

            const value = input.value.trim();
            const isValid = fieldConfig.validator(value, input, form);

            if (value === '') {
                clearState(input);
                if (showMessage && fieldConfig.required) {
                    setInvalid(input);
                    showFeedback(input, true);
                    return false;
                }
                return !fieldConfig.required;
            }

            if (isValid) {
                setValid(input);
                showFeedback(input, false);
                return true;
            } else {
                setInvalid(input);
                showFeedback(input, showMessage);
                return false;
            }
        }

        fields.forEach(fieldConfig => {
            const input = document.querySelector(fieldConfig.selector);
            if (!input) return;

            if (input.classList.contains('is-invalid')) {
                showFeedback(input, true);
            }

            input.addEventListener('input', function () {
                const value = input.value.trim();
                showFeedback(input, false);

                if (value === '') {
                    clearState(input);
                } else {
                    const ok = fieldConfig.validator(value, input, form);
                    if (ok) {
                        setValid(input);
                    } else {
                        setInvalid(input);
                    }
                }

                if (typeof fieldConfig.onInput === 'function') {
                    fieldConfig.onInput(input, form);
                }
            });

            input.addEventListener('blur', function () {
                if (input.value.trim() === '') {
                    clearState(input);
                }

                if (typeof fieldConfig.onBlur === 'function') {
                    fieldConfig.onBlur(input, form);
                }
            });
        });

        form.addEventListener('submit', function (e) {
            let formIsValid = true;

            fields.forEach(fieldConfig => {
                const ok = validateField(fieldConfig, true);
                if (!ok) formIsValid = false;
            });

            if (!formIsValid) {
                e.preventDefault();
            }
        });
    }

    window.FormValidator = {
        setupValidation,

        validators: {
            email(value) {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                return emailRegex.test(value);
            },

            password(value) {
                return value.length >= 8;
            },

            confirmPassword(value, input, form) {
                const passwordInput = form.querySelector('[data-match-password]');
                if (!passwordInput) return false;
                return value === passwordInput.value.trim();
            }
        }
    };
});