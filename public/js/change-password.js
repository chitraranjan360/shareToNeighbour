
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('change_password_form');
    if (!form) return;

    const oldPassword = document.getElementById('oldPassword');
    const newPassword = document.getElementById('newPassword');
    const confirmPassword = document.getElementById('confirmPassword');

    const strengthBar = document.getElementById('passwordStrengthBar');
    const strengthText = document.getElementById('passwordStrengthText');
    const rulesList = document.getElementById('passwordRules');

    const PASSWORD_RULES = [
        { id: 'len',   label: 'At least 8 characters',         test: p => p.length >= 8 },
        { id: 'upper', label: 'One uppercase letter (A–Z)',    test: p => /[A-Z]/.test(p) },
        { id: 'lower', label: 'One lowercase letter (a–z)',    test: p => /[a-z]/.test(p) },
        { id: 'digit', label: 'One number (0–9)',              test: p => /\d/.test(p) },
        { id: 'spec',  label: 'One special character (!@#$…)', test: p => /[^A-Za-z0-9]/.test(p) },
    ];

    const STRENGTH_LEVELS = [
        { label: 'Too weak',    color: '#dc3545', width: '20%'  },
        { label: 'Weak',        color: '#fd7e14', width: '40%'  },
        { label: 'Fair',        color: '#ffc107', width: '60%'  },
        { label: 'Strong',      color: '#198754', width: '80%'  },
        { label: 'Very strong', color: '#0d6efd', width: '100%' }
    ];

    function getFeedback(input) {
        return input.closest('.input-icon')?.querySelector('.invalid-feedback');
    }

    function showFeedback(input, show, message = '') {
        const feedback = getFeedback(input);
        if (!feedback) return;
        if (message) feedback.textContent = message;
        feedback.style.display = show ? 'block' : 'none';
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

    function setInvalid(input, showMessage = false, message = '') {
        input.classList.add('is-invalid');
        input.classList.remove('is-valid');
        showFeedback(input, showMessage, message);
    }

    function getPassedRules(password) {
        return PASSWORD_RULES.filter(rule => rule.test(password));
    }

    function updatePasswordRules(password) {
        PASSWORD_RULES.forEach(rule => {
            const item = rulesList.querySelector(`[data-rule="${rule.id}"]`);
            if (!item) return;

            const passed = rule.test(password);
            item.textContent = `${passed ? '✓' : '✗'} ${rule.label}`;
            item.classList.toggle('rule-ok', passed);
            item.classList.toggle('rule-bad', !passed && password.length > 0);
        });
    }

    function updatePasswordStrength(password) {
        const passedCount = getPassedRules(password).length;
        const index = password.length === 0 ? 0 : Math.max(0, passedCount - 1);
        const level = STRENGTH_LEVELS[index];

        strengthBar.style.width = password.length === 0 ? '0%' : level.width;
        strengthBar.style.backgroundColor = level.color;
        strengthText.textContent = password.length === 0 ? 'Too weak' : level.label;
        strengthText.style.color = password.length === 0 ? '#6c757d' : level.color;
    }

    function isStrongPassword(password) {
        return PASSWORD_RULES.every(rule => rule.test(password));
    }

    function validateOldPassword(showMessage = false) {
        const value = oldPassword.value.trim();

        if (value === '') {
            if (showMessage) {
                setInvalid(oldPassword, true, 'Please fill in this field');
            } else {
                clearState(oldPassword);
            }
            return false;
        }

        setValid(oldPassword);
        return true;
    }

    function validateNewPassword(showMessage = false) {
        const value = newPassword.value;

        updatePasswordRules(value);
        updatePasswordStrength(value);

        if (value.trim() === '') {
            if (showMessage) {
                setInvalid(newPassword, true, 'Please fill in this field');
            } else {
                clearState(newPassword);
            }
            return false;
        }

        if (!isStrongPassword(value)) {
            setInvalid(newPassword, showMessage, 'Password must meet all required rules');
            return false;
        }

        setValid(newPassword);
        return true;
    }

    function validateConfirmPassword(showMessage = false) {
        const confirmValue = confirmPassword.value;
        const newValue = newPassword.value;

        if (confirmValue.trim() === '') {
            if (showMessage) {
                setInvalid(confirmPassword, true, 'Please fill in this field');
            } else {
                clearState(confirmPassword);
            }
            return false;
        }

        if (confirmValue !== newValue) {
            setInvalid(confirmPassword, showMessage, 'Passwords do not match');
            return false;
        }

        setValid(confirmPassword);
        return true;
    }

    oldPassword.addEventListener('input', function () {
        showFeedback(oldPassword, false);

        if (oldPassword.value.trim() === '') {
            clearState(oldPassword);
        } else {
            validateOldPassword(false);
        }
    });

    oldPassword.addEventListener('blur', function () {
        if (oldPassword.value.trim() === '') {
            clearState(oldPassword);
        }
    });

    newPassword.addEventListener('input', function () {
        showFeedback(newPassword, false);

        if (newPassword.value.trim() === '') {
            clearState(newPassword);
            updatePasswordRules('');
            updatePasswordStrength('');
        } else {
            validateNewPassword(false);
        }

        if (confirmPassword.value.trim() !== '') {
            validateConfirmPassword(false);
        }
    });

    newPassword.addEventListener('blur', function () {
        if (newPassword.value.trim() === '') {
            clearState(newPassword);
        }
    });

    confirmPassword.addEventListener('input', function () {
        showFeedback(confirmPassword, false);

        if (confirmPassword.value.trim() === '') {
            clearState(confirmPassword);
        } else {
            validateConfirmPassword(false);
        }
    });

    confirmPassword.addEventListener('blur', function () {
        if (confirmPassword.value.trim() === '') {
            clearState(confirmPassword);
        }
    });

    updatePasswordRules('');
    updatePasswordStrength('');

    form.addEventListener('submit', function (e) {
        const isOldPasswordValid = validateOldPassword(true);
        const isNewPasswordValid = validateNewPassword(true);
        const isConfirmPasswordValid = validateConfirmPassword(true);

        if (!isOldPasswordValid || !isNewPasswordValid || !isConfirmPasswordValid) {
            e.preventDefault();
        }
    });
});
