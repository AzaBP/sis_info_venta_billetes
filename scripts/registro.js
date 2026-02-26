// Step navigation
const prevBtn = document.getElementById('prevBtn');
const nextBtn = document.getElementById('nextBtn');
const registerForm = document.getElementById('registerForm');
const formSteps = document.querySelectorAll('.form-step');
const steps = document.querySelectorAll('.step');
const progressFill = document.querySelector('.progress-fill');

let currentStep = 1;

// Update progress bar and steps
function updateProgress() {
    const progress = (currentStep / 4) * 100;
    progressFill.style.width = progress + '%';

    steps.forEach((step, index) => {
        const stepNum = index + 1;
        step.classList.remove('active', 'completed');

        if (stepNum < currentStep) {
            step.classList.add('completed');
        } else if (stepNum === currentStep) {
            step.classList.add('active');
        }
    });

    // Update button visibility
    prevBtn.style.display = currentStep === 1 ? 'none' : 'flex';

    // Change next button text on last step
    if (currentStep === 4) {
        nextBtn.textContent = 'Crear Cuenta';
        nextBtn.innerHTML = 'Crear Cuenta';
        nextBtn.type = 'button';
    } else {
        nextBtn.innerHTML = 'Siguiente <i class="fa-solid fa-chevron-right"></i>';
        nextBtn.type = 'button';
    }
}

// Show specific step
function showStep(n) {
    formSteps.forEach(step => step.classList.remove('active'));
    if (formSteps[n - 1]) {
        formSteps[n - 1].classList.add('active');
    }
    updateProgress();
}

// Next button
nextBtn.addEventListener('click', (e) => {

    if (currentStep < 4) {
        e.preventDefault();

        if (validateStep(currentStep)) {
            currentStep++;
            showStep(currentStep);
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

    } else {

        const password = document.getElementById('contrasena').value;
        const confirmPassword = document.getElementById('confirmar_contraseña').value;

        if (password !== confirmPassword) {
            alert('Las contraseñas no coinciden');
            return;
        }

        const terminos = document.querySelector('input[name="terminos"]').checked;
        const privacidad = document.querySelector('input[name="privacidad"]').checked;

        if (!terminos || !privacidad) {
            alert('Debes aceptar los Términos y Condiciones y la Política de Privacidad');
            return;
        }

        // Si todo está correcto → enviar al PHP
        registerForm.submit();
   }
});

// Previous button
prevBtn.addEventListener('click', () => {
    if (currentStep > 1) {
        currentStep--;
        showStep(currentStep);
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
});

// Validate step fields
function validateStep(step) {
    const stepElement = formSteps[step - 1];
    const inputs = stepElement.querySelectorAll('input[required], select[required]');
    let isValid = true;

    inputs.forEach(input => {
        if (!input.value.trim()) {
            input.style.borderColor = '#ff6b6b';
            isValid = false;
        } else {
            input.style.borderColor = '#e8ecf1';
        }
    });

    return isValid;
}

// Password visibility toggle
const togglePasswordButtons = document.querySelectorAll('.toggle-password');

togglePasswordButtons.forEach(btn => {
    btn.addEventListener('click', (e) => {
        e.preventDefault();
        const input = btn.previousElementSibling;
        const icon = btn.querySelector('i');

        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    });
});

// Password strength indicator
const passwordInput = document.getElementById('contrasena');
const strengthBar = document.querySelector('.strength-bar');
const strengthText = document.querySelector('.strength-text strong');

if (passwordInput) {
    passwordInput.addEventListener('input', () => {
        const password = passwordInput.value;
        let strength = 0;
        let strengthLabel = 'Débil';

        // Check length
        if (password.length >= 8) strength += 25;
        if (password.length >= 12) strength += 15;

        // Check for uppercase
        if (/[A-Z]/.test(password)) strength += 20;

        // Check for lowercase
        if (/[a-z]/.test(password)) strength += 20;

        // Check for numbers
        if (/[0-9]/.test(password)) strength += 15;

        // Check for special characters
        if (/[!@#$%^&*]/.test(password)) strength += 15;

        // Determine strength level
        if (strength < 33) {
            strengthLabel = 'Débil';
        } else if (strength < 66) {
            strengthLabel = 'Media';
        } else if (strength < 100) {
            strengthLabel = 'Fuerte';
        } else {
            strengthLabel = 'Muy Fuerte';
        }

        // Update bar width
        const barAfter = strengthBar.style.cssText;
        strengthBar.style.cssText = `width: ${strength}%;`;
        
        // Change bar color
        if (strength < 33) {
            strengthBar.style.background = '#ff6b6b';
        } else if (strength < 66) {
            strengthBar.style.background = '#ffa500';
        } else if (strength < 100) {
            strengthBar.style.background = '#4caf50';
        } else {
            strengthBar.style.background = '#2196f3';
        }

        strengthText.textContent = strengthLabel;

        // Update password tips
        const tips = {
            length: strength >= 25,
            upper: /[A-Z]/.test(password),
            lower: /[a-z]/.test(password),
            number: /[0-9]/.test(password),
            special: /[!@#$%^&*]/.test(password)
        };

        updateTips(tips);
    });
}

// Update password tips visual feedback
function updateTips(tips) {
    const tipIds = [
        { id: 'tip-length', check: tips.length },
        { id: 'tip-upper', check: tips.upper },
        { id: 'tip-lower', check: tips.lower },
        { id: 'tip-number', check: tips.number },
        { id: 'tip-special', check: tips.special }
    ];

    tipIds.forEach(({ id, check }) => {
        const tip = document.getElementById(id);
        if (tip) {
            if (check) {
                tip.classList.add('completed');
                tip.innerHTML = tip.innerHTML.replace('fa-circle-xmark', 'fa-circle-check');
            } else {
                tip.classList.remove('completed');
                tip.innerHTML = tip.innerHTML.replace('fa-circle-check', 'fa-circle-xmark');
            }
        }
    });
}

// Form submission
function submitForm() {
    const password = document.getElementById('contrasena').value;
    const confirmPassword = document.getElementById('confirmar_contraseña').value;

    // Validate passwords match
    if (password !== confirmPassword) {
        alert('Las contraseñas no coinciden');
        return;
    }

    // Validate terms
    const terminos = document.querySelector('input[name="terminos"]').checked;
    const privacidad = document.querySelector('input[name="privacidad"]').checked;

    if (!terminos || !privacidad) {
        alert('Debes aceptar los Términos y Condiciones y la Política de Privacidad');
        return;
    }

    // Success
    alert('¡Cuenta creada exitosamente! Serás redirigido al inicio de sesión');
    window.location.href = 'inicio_sesion.html';
}

// Initialize
showStep(1);

// Real-time validation
document.querySelectorAll('.form-step input, .form-step select').forEach(input => {
    input.addEventListener('change', () => {
        if (input.value.trim()) {
            input.style.borderColor = '#e8ecf1';
        }
    });
});