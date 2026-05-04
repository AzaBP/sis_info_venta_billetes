// ==========================
// STEP NAVIGATION
// ==========================

const prevBtn = document.getElementById('prevBtn');
const nextBtn = document.getElementById('nextBtn');
const registerForm = document.getElementById('registerForm');
const formSteps = document.querySelectorAll('.form-step');
const steps = document.querySelectorAll('.step');
const progressFill = document.querySelector('.progress-fill');

let currentStep = 1;

function mostrarErrorTerminos(mensaje) {
    const errorBox = document.getElementById('terms-error');
    if (!errorBox) return;
    errorBox.textContent = mensaje;
    errorBox.style.display = 'block';
}

function limpiarErrorTerminos() {
    const errorBox = document.getElementById('terms-error');
    if (!errorBox) return;
    errorBox.textContent = '';
    errorBox.style.display = 'none';
}

// ==========================
// SESSION STORAGE - PERSIST FORM DATA
// ==========================

const STORAGE_KEY = 'trainweb_registro_datos';

function guardarDatosFormulario() {
    const datos = {};
    const inputs = registerForm.querySelectorAll('input, select');
    inputs.forEach(input => {
        if (input.name) {
            datos[input.name] = input.type === 'checkbox' ? input.checked : input.value;
        }
    });
    sessionStorage.setItem(STORAGE_KEY, JSON.stringify(datos));
}

function recuperarDatosFormulario() {
    const datosGuardados = sessionStorage.getItem(STORAGE_KEY);
    if (datosGuardados) {
        try {
            const datos = JSON.parse(datosGuardados);
            const inputs = registerForm.querySelectorAll('input, select');
            inputs.forEach(input => {
                if (input.name && input.name in datos) {
                    if (input.type === 'checkbox') {
                        input.checked = datos[input.name];
                    } else {
                        input.value = datos[input.name];
                    }
                }
            });
        } catch (e) {
            console.error('Error al recuperar datos del formulario:', e);
        }
    }
}

function limpiarDatosFormulario() {
    sessionStorage.removeItem(STORAGE_KEY);
}

// ==========================
// PROGRESS BAR
// ==========================

function updateProgress() {

    const progress = (currentStep / 4) * 100;
    progressFill.style.width = progress + '%';

    steps.forEach((step,index)=>{
        step.classList.remove('active','completed');

        if(index+1 < currentStep) step.classList.add('completed');
        if(index+1 === currentStep) step.classList.add('active');
    });

    prevBtn.style.display = currentStep === 1 ? 'none' : 'flex';

    const t = (key) => (window.trainwebI18n && window.trainwebI18n.t) ? window.trainwebI18n.t(key) : null;

    if(currentStep === 4){
        nextBtn.innerHTML = t('boton_crear_cuenta') || "Crear Cuenta";
        nextBtn.type = "submit";
    }else{
        nextBtn.innerHTML = `${t('boton_siguiente') || 'Siguiente'} <i class="fa-solid fa-chevron-right"></i>`;
        nextBtn.type = "button";
    }
}

function showStep(n){
    formSteps.forEach(s=>s.classList.remove("active"));
    if(formSteps[n-1]) formSteps[n-1].classList.add("active");
    updateProgress();
}

// ==========================
// VALIDACIONES
// ==========================

const letrasDNI = "TRWAGMYFPDXBNJZSQVHLCKE";

function validarDocumento(valor){

    valor = valor.toUpperCase().trim();

    const dni = /^[0-9]{8}[A-Z]$/;
    const nie = /^[XYZ][0-9]{7}[A-Z]$/;

    if(dni.test(valor)){
        const num = parseInt(valor.substring(0,8));
        return valor[8] === letrasDNI[num % 23];
    }

    if(nie.test(valor)){
        valor = valor.replace("X","0")
                     .replace("Y","1")
                     .replace("Z","2");

        const num = parseInt(valor.substring(0,8));
        return valor[8] === letrasDNI[num % 23];
    }

    return false;
}

// ==========================
// MOSTRAR ERRORES
// ==========================

function showError(input,msg){

    let errorDiv = input.parentNode.querySelector(".error-msg");

    if(!errorDiv){
        errorDiv = document.createElement("div");
        errorDiv.className = "error-msg";
        input.parentNode.appendChild(errorDiv);
    }

    if(msg){
        input.classList.add("error");
        errorDiv.textContent = msg;
    }else{
        input.classList.remove("error");
        errorDiv.textContent = "";
    }
}

// ==========================
// VALIDACIÓN TIEMPO REAL
// ==========================

const allInputs = document.querySelectorAll(
    '.form-step input, .form-step select'
);

allInputs.forEach(input=>{

    input.addEventListener("input",()=>{

        guardarDatosFormulario();

        const t = (key) => (window.trainwebI18n && window.trainwebI18n.t) ? window.trainwebI18n.t(key) : null;
        const value = input.value.trim();
        let error = "";

        switch(input.id){

            case "nombre":
            case "apellido":
                if(!/^[A-Za-zÁÉÍÓÚáéíóúÑñ\s]{2,}$/.test(value)){
                    error = t('solo_letras_error') || "Solo letras (mínimo 2)";
                }
            break;

            case "nacimiento":
                const fecha = new Date(value);
                const hoy = new Date();

                let edad = hoy.getFullYear() - fecha.getFullYear();
                const m = hoy.getMonth() - fecha.getMonth();
                if(m < 0 || (m === 0 && hoy.getDate() < fecha.getDate())) edad--;

                if(edad < 14){
                    error = t('minimo_14_anios_error') || "Debes tener mínimo 14 años";
                }
            break;

            case "email":
                if(!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)){
                    error = t('email_invalido_error') || "Email inválido";
                }
            break;

            case "telefono":
                if(!/^[0-9+\s]{7,15}$/.test(value)){
                    error = t('telefono_invalido_error') || "Teléfono inválido";
                }
            break;

            case "codigo_postal":
                if(!/^[0-9]{4,6}$/.test(value)){
                    error = t('cp_invalido_error') || "Código postal inválido";
                }
            break;

            case "ciudad":
                if(!/^[A-Za-zÁÉÍÓÚáéíóúÑñ\s]{2,}$/.test(value)){
                    error = t('ciudad_invalida_error') || "Ciudad inválida";
                }
            break;

            case "numero_documento":
                if(!validarDocumento(value)){
                    error = t('documento_invalido_error') || "Documento inválido";
                }
            break;

            case "contrasena":
                if(value.length < 8){
                    error = t('min_8_caracteres_error') || "Mínimo 8 caracteres";
                }
                else if(!/[A-Z]/.test(value)){
                    error = t('mayuscula_requerida_error') || "Debe tener mayúscula";
                }
                else if(!/[a-z]/.test(value)){
                    error = t('minuscula_requerida_error') || "Debe tener minúscula";
                }
                else if(!/[0-9]/.test(value)){
                    error = t('numero_requerido_error') || "Debe tener número";
                }
                else if(!/[!@#$%^&*]/.test(value)){
                    error = t('especial_requerido_error') || "Debe tener carácter especial";
                }
            break;

            case "confirmar_contraseña":
                const pass = document.getElementById("contrasena").value;
                if(value !== pass){
                    error = t('contrasenas_no_coinciden_error') || "Las contraseñas no coinciden";
                }
            break;

        }

        showError(input,error);

    });

    input.addEventListener("change",()=>{
        guardarDatosFormulario();
    });

});

// ==========================
// BOTONES
// ==========================

    nextBtn.addEventListener("click",(e)=>{

    const t = (key) => (window.trainwebI18n && window.trainwebI18n.t) ? window.trainwebI18n.t(key) : null;

    if(currentStep < 4){

        e.preventDefault();

        const stepInputs = formSteps[currentStep-1]
            .querySelectorAll("input,select");

        let valid = true;

        stepInputs.forEach(input=>{
            if(input.classList.contains("error") || !input.value.trim()){
                valid = false;
                showError(input, t('campo_invalido_error') || "Campo inválido");
            }
        });

        if(!valid) return;

        guardarDatosFormulario();
        currentStep++;
        showStep(currentStep);
    }

    else{

        e.preventDefault();

        const pass = document.getElementById("contrasena").value;
        const confirm = document.getElementById("confirmar_contraseña").value;

        if(pass !== confirm){
            alert(t('contrasenas_no_coinciden_error') || "Las contraseñas no coinciden");
            return;
        }

        // Validar términos y privacidad en cliente
        const terminos = document.querySelector('input[name="terminos"]').checked;
        const privacidad = document.querySelector('input[name="privacidad"]').checked;

        if(!terminos || !privacidad){
            mostrarErrorTerminos(t('aceptar_terminos_error') || 'Debes aceptar los términos y la política de privacidad para continuar.');
            return;
        }

        limpiarErrorTerminos();

        registerForm.submit();
    }

});

prevBtn.addEventListener("click",()=>{
    if(currentStep > 1){
        guardarDatosFormulario();
        currentStep--;
        showStep(currentStep);
    }
});

document.querySelectorAll('input[name="terminos"], input[name="privacidad"]').forEach(input => {
    input.addEventListener('change', () => {
        const terminos = document.querySelector('input[name="terminos"]').checked;
        const privacidad = document.querySelector('input[name="privacidad"]').checked;
        if (terminos && privacidad) {
            limpiarErrorTerminos();
        }
    });
});

// ==========================
// PASSWORD VISIBILITY TOGGLE
// ==========================

const togglePasswordButtons = document.querySelectorAll('.toggle-password');

togglePasswordButtons.forEach(btn => {

    btn.addEventListener('click',(e)=>{
        e.preventDefault();

        const input = btn.parentNode.querySelector("input");
        const icon = btn.querySelector("i");

        if(input.type === "password"){
            input.type = "text";
            icon.classList.remove("fa-eye");
            icon.classList.add("fa-eye-slash");
        }
        else{
            input.type = "password";
            icon.classList.remove("fa-eye-slash");
            icon.classList.add("fa-eye");
        }

    });

});

const params = new URLSearchParams(window.location.search);
const error = params.get("error");
const requestedStep = parseInt(params.get("step"), 10);
const freshStart = params.get("fresh") === "1";

// Si no viene de navegación interna (step o error en proceso de registro), limpiar
// La navegación interna es cuando: tiene step válido O tiene error (significa que estaba en registro)
const isInternalNavigation = (!isNaN(requestedStep) && requestedStep >= 1 && requestedStep <= 4) || error;

function limpiarCamposVisibles() {
    const inputs = registerForm.querySelectorAll('input, select, textarea');
    inputs.forEach((input) => {
        if (input.type === 'checkbox' || input.type === 'radio') {
            input.checked = false;
            return;
        }
        if (input.tagName === 'SELECT') {
            input.selectedIndex = 0;
            return;
        }
        input.value = '';
    });
}

if (freshStart || !isInternalNavigation) {
    limpiarDatosFormulario();
    registerForm.reset();
    limpiarCamposVisibles();
    currentStep = 1;
    // Limpiar URL si viene con parámetros innecesarios
    if (freshStart) {
        const cleanUrl = window.location.pathname + (window.location.hash || '');
        window.history.replaceState({}, document.title, cleanUrl);
    }
}

if (!Number.isNaN(requestedStep) && requestedStep >= 1 && requestedStep <= 4) {
    currentStep = requestedStep;
}

if (error === "aceptar_politicas") {
    currentStep = 4;
}

const mensajeDiv = document.getElementById("mensaje-error");

if(error){

    mensajeDiv.style.display = "block";
    mensajeDiv.classList.add("error");

    const t = (key) => (window.trainwebI18n && window.trainwebI18n.t) ? window.trainwebI18n.t(key) : null;

    if(error === "usuario_existente"){
        mensajeDiv.textContent = t('correo_ya_registrado_error') || "⚠️ Este correo ya está registrado.";
    }

    if(error === "error_usuario"){
        mensajeDiv.textContent = t('error_crear_usuario') || "⚠️ Error al crear el usuario.";
    }

    if(error === "error_pasajero"){
        mensajeDiv.textContent = t('error_crear_perfil') || "⚠️ Error al crear el perfil del pasajero.";
    }

    if(error === "aceptar_politicas"){
        mensajeDiv.textContent = t('aceptar_terminos_error') || "⚠️ Debes aceptar los términos y la política de privacidad para continuar.";
    }

    if(error === "datos_incompletos"){
        mensajeDiv.textContent = t('datos_incompletos_error') || "⚠️ Faltan datos obligatorios en el formulario de registro.";
    }
}

// Recuperar datos guardados en sessionStorage al cargar la página
if (!freshStart && isInternalNavigation) {
    recuperarDatosFormulario();
}

// Limpiar datos cuando se envía el formulario exitosamente
registerForm.addEventListener('submit', () => {
    limpiarDatosFormulario();
});

// ==========================
// PASSWORD STRENGTH
// ==========================

const passwordInput = document.getElementById("contrasena");
const strengthBar = document.querySelector(".strength-bar");
const strengthTextStrong = document.querySelector(".strength-text strong");

if (passwordInput) {

    passwordInput.addEventListener("input", function () {

        const value = passwordInput.value;

        let strength = 0;

        const hasLength = value.length >= 8;
        const hasUpper = /[A-Z]/.test(value);
        const hasLower = /[a-z]/.test(value);
        const hasNumber = /[0-9]/.test(value);
        const hasSpecial = /[!@#$%^&*]/.test(value);

        if (hasLength) strength += 20;
        if (hasUpper) strength += 20;
        if (hasLower) strength += 20;
        if (hasNumber) strength += 20;
        if (hasSpecial) strength += 20;

        const t = (key) => (window.trainwebI18n && window.trainwebI18n.t) ? window.trainwebI18n.t(key) : null;

        // 🔹 MOVER BARRA
        strengthBar.style.width = strength + "%";

        // 🔹 COLOR + TEXTO
        if (strength < 40) {
            strengthBar.style.background = "#ff4d4d";
            strengthTextStrong.textContent = t('fuerza_debil') || "Débil";
        }
        else if (strength < 80) {
            strengthBar.style.background = "#ffa500";
            strengthTextStrong.textContent = t('fuerza_media') || "Media";
        }
        else {
            strengthBar.style.background = "#17632A";
            strengthTextStrong.textContent = t('fuerza_fuerte') || "Fuerte";
        }

        // 🔹 TICKS VERDES
        actualizarTip("tip-length", hasLength);
        actualizarTip("tip-upper", hasUpper);
        actualizarTip("tip-lower", hasLower);
        actualizarTip("tip-number", hasNumber);
        actualizarTip("tip-special", hasSpecial);
    });
}

function actualizarTip(id, valido) {

    const tip = document.getElementById(id);
    if (!tip) return;

    const icon = tip.querySelector("i");

    if (valido) {
        tip.classList.add("completed");
        icon.classList.remove("fa-circle-xmark");
        icon.classList.add("fa-circle-check");
        icon.style.color = "#17632A";
    } else {
        tip.classList.remove("completed");
        icon.classList.remove("fa-circle-check");
        icon.classList.add("fa-circle-xmark");
        icon.style.color = "#ff4d4d";
    }
}

// INIT
showStep(currentStep);