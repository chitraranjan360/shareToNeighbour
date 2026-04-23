
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('login_form');
    if (!form) return;

    const usernameInput = document.getElementById('login_username');
    const passwordInput = document.getElementById('login_password');

    const fields = [
        {
            input: usernameInput,
            validate: (value) => value.trim() !== '' && (value.length >= 4 && value.length <= 20)
        },
        {
            input: passwordInput,
            validate: (value) => value.trim() !== '' && value.length >= 8 && value.length <= 20
        }
    ];

    function getFeedback(input) {
        return input.closest('.col-12')?.querySelector('.invalid-feedback');
    }

    function showFeedback(input, show) {
        const feedback = getFeedback(input);
        if (feedback) feedback.style.display = show ? 'block' : 'none';
    }

    function clearState(input) {
        input.classList.remove('is-valid', 'is-invalid');
        showFeedback(input, false);
    }

    function setValid(input) {
        input.classList.add('is-valid');
        input.classList.remove('is-invalid');
        showFeedback(input, false);
    }

    function setInvalid(input, showMessage = false) {
        input.classList.add('is-invalid');
        input.classList.remove('is-valid');
        showFeedback(input, showMessage);
    }

    function validateField(input, validator, showMessage = false) {
        const value = input.value.trim();

        if (value === '') {
            if (showMessage) {
                setInvalid(input, true);
                return false;
            } else {
                clearState(input);
                return false;
            }
        }

        if (validator(value)) {
            setValid(input);
            return true;
        } else {
            setInvalid(input, showMessage);
            return false;
        }
    }

    fields.forEach(field => {
        if (!field.input) return;

        if (field.input.classList.contains('is-invalid')) {
            showFeedback(field.input, true);
        }

        field.input.addEventListener('input', function () {
            showFeedback(field.input, false);

            if (field.input.value.trim() === '') {
                clearState(field.input);
            } else {
                const ok = field.validate(field.input.value);
                ok ? setValid(field.input) : setInvalid(field.input, false);
            }
        });

        field.input.addEventListener('blur', function () {
            if (field.input.value.trim() === '') {
                clearState(field.input);
            }
        });
    });

    form.addEventListener('submit', function (e) {
        let formIsValid = true;

        fields.forEach(field => {
            const ok = validateField(field.input, field.validate, true);
            if (!ok) formIsValid = false;
        });

        if (!formIsValid) {
            e.preventDefault();
        }
    });
});
