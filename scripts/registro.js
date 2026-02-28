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

    if(currentStep === 4){
        nextBtn.innerHTML = "Crear Cuenta";
        nextBtn.type = "submit";
    }else{
        nextBtn.innerHTML = 'Siguiente <i class="fa-solid fa-chevron-right"></i>';
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

        const value = input.value.trim();
        let error = "";

        switch(input.id){

            case "nombre":
            case "apellido":
                if(!/^[A-Za-zÁÉÍÓÚáéíóúÑñ\s]{2,}$/.test(value)){
                    error = "Solo letras (mínimo 2)";
                }
            break;

            case "nacimiento":
                const fecha = new Date(value);
                const hoy = new Date();

                let edad = hoy.getFullYear() - fecha.getFullYear();
                const m = hoy.getMonth() - fecha.getMonth();
                if(m < 0 || (m === 0 && hoy.getDate() < fecha.getDate())) edad--;

                if(edad < 14){
                    error = "Debes tener mínimo 14 años";
                }
            break;

            case "email":
                if(!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)){
                    error = "Email inválido";
                }
            break;

            case "telefono":
                if(!/^[0-9+\s]{7,15}$/.test(value)){
                    error = "Teléfono inválido";
                }
            break;

            case "codigo_postal":
                if(!/^[0-9]{4,6}$/.test(value)){
                    error = "Código postal inválido";
                }
            break;

            case "ciudad":
                if(!/^[A-Za-zÁÉÍÓÚáéíóúÑñ\s]{2,}$/.test(value)){
                    error = "Ciudad inválida";
                }
            break;

            case "numero_documento":
                if(!validarDocumento(value)){
                    error = "Documento inválido";
                }
            break;

            case "contrasena":
                if(value.length < 8){
                    error = "Mínimo 8 caracteres";
                }
                else if(!/[A-Z]/.test(value)){
                    error = "Debe tener mayúscula";
                }
                else if(!/[a-z]/.test(value)){
                    error = "Debe tener minúscula";
                }
                else if(!/[0-9]/.test(value)){
                    error = "Debe tener número";
                }
                else if(!/[!@#$%^&*]/.test(value)){
                    error = "Debe tener carácter especial";
                }
            break;

            case "confirmar_contraseña":
                const pass = document.getElementById("contrasena").value;
                if(value !== pass){
                    error = "Las contraseñas no coinciden";
                }
            break;

        }

        showError(input,error);

    });

});

// ==========================
// BOTONES
// ==========================

nextBtn.addEventListener("click",(e)=>{

    if(currentStep < 4){

        e.preventDefault();

        const stepInputs = formSteps[currentStep-1]
            .querySelectorAll("input,select");

        let valid = true;

        stepInputs.forEach(input=>{
            if(input.classList.contains("error") || !input.value.trim()){
                valid = false;
                showError(input,"Campo inválido");
            }
        });

        if(!valid) return;

        currentStep++;
        showStep(currentStep);
    }

    else{

        const pass = document.getElementById("contrasena").value;
        const confirm = document.getElementById("confirmar_contraseña").value;

        if(pass !== confirm){
            alert("Las contraseñas no coinciden");
            return;
        }

        registerForm.submit();
    }

});

prevBtn.addEventListener("click",()=>{
    if(currentStep > 1){
        currentStep--;
        showStep(currentStep);
    }
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

const mensajeDiv = document.getElementById("mensaje-error");

if(error){

    mensajeDiv.style.display = "block";
    mensajeDiv.classList.add("error");

    if(error === "usuario_existente"){
        mensajeDiv.textContent = "⚠️ Este correo ya está registrado.";
    }

    if(error === "error_usuario"){
        mensajeDiv.textContent = "⚠️ Error al crear el usuario.";
    }

    if(error === "error_pasajero"){
        mensajeDiv.textContent = "⚠️ Error al crear el perfil del pasajero.";
    }
}

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

        // 🔹 MOVER BARRA
        strengthBar.style.width = strength + "%";

        // 🔹 COLOR + TEXTO
        if (strength < 40) {
            strengthBar.style.background = "#ff4d4d";
            strengthTextStrong.textContent = "Débil";
        }
        else if (strength < 80) {
            strengthBar.style.background = "#ffa500";
            strengthTextStrong.textContent = "Media";
        }
        else {
            strengthBar.style.background = "#28a745";
            strengthTextStrong.textContent = "Fuerte";
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
        icon.style.color = "#28a745";
    } else {
        tip.classList.remove("completed");
        icon.classList.remove("fa-circle-check");
        icon.classList.add("fa-circle-xmark");
        icon.style.color = "#ff4d4d";
    }
}

// INIT
showStep(1);