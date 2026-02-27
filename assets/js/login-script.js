// ============================================
// SCRIPT PARA O LOGIN
// ============================================

document.addEventListener('DOMContentLoaded', function() {
    
    // ============================================
    // 1. TOGGLE MOSTRAR/OCULTAR SENHA
    // ============================================
    const togglePassword = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('password');

    if (togglePassword && passwordInput) {
        togglePassword.addEventListener('click', function() {
            // Alterna entre 'password' e 'text'
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            
            // Alterna o ícone entre olho aberto e fechado
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });
    }

    // ============================================
    // 2. VALIDAÇÃO DO FORMULÁRIO
    // ============================================
    const loginForm = document.getElementById('loginForm');
    
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            let isValid = true;
            
            // Valida o campo de usuário
            const username = document.getElementById('username');
            if (username.value.trim() === '') {
                showError(username);
                isValid = false;
            } else {
                removeError(username);
            }
            
            // Valida o campo de senha
            const password = document.getElementById('password');
            if (password.value.trim() === '') {
                showError(password);
                isValid = false;
            } else {
                removeError(password);
            }
            
            // Se não for válido, impede o envio
            if (!isValid) {
                e.preventDefault();
            }
        });
    }

    // ============================================
    // 3. REMOVE ERRO QUANDO USUÁRIO DIGITA
    // ============================================
    const inputs = document.querySelectorAll('input[required]');
    inputs.forEach(input => {
        input.addEventListener('input', function() {
            if (this.value.trim() !== '') {
                removeError(this);
            }
        });
    });

    // ============================================
    // 4. FUNÇÕES AUXILIARES
    // ============================================
    
    function showError(input) {
        const formGroup = input.closest('.form-group');
        formGroup.classList.add('error');
        input.focus();
    }
    
    function removeError(input) {
        const formGroup = input.closest('.form-group');
        formGroup.classList.remove('error');
    }

    // ============================================
    // 5. AUTO-FECHAR ALERTAS APÓS 5 SEGUNDOS
    // ============================================
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-10px)';
            setTimeout(() => {
                alert.remove();
            }, 300);
        }, 5000);
    });

    // ============================================
    // 6. CONTROLE DO MODAL DE TERMOS DE USO
    // ============================================
    
    const termsModal = document.getElementById('termsModal');
    const btnAccept = document.getElementById('btnAcceptTerms');
    const btnCancel = document.getElementById('btnCancelTerms');

    // Verifica se o usuário já aceitou os termos
    function checkTermsAcceptance() {
        const termsAccepted = localStorage.getItem('natupath_terms_accepted');
        
        if (!termsAccepted) {
            // Se não aceitou, mostra o modal e desabilita o formulário
            termsModal.classList.add('active');
            disableForm();
        } else {
            // Se já aceitou, libera o formulário
            enableForm();
        }
    }

    // Desabilita o formulário
    function disableForm() {
        const inputs = loginForm.querySelectorAll('input, button');
        inputs.forEach(input => input.disabled = true);
        loginForm.style.opacity = '0.5';
        loginForm.style.pointerEvents = 'none';
    }

    // Habilita o formulário
    function enableForm() {
        const inputs = loginForm.querySelectorAll('input, button');
        inputs.forEach(input => input.disabled = false);
        loginForm.style.opacity = '1';
        loginForm.style.pointerEvents = 'auto';
    }

    // Botão ACEITO
    if (btnAccept) {
        btnAccept.addEventListener('click', function() {
            localStorage.setItem('natupath_terms_accepted', 'true');
            localStorage.setItem('natupath_terms_date', new Date().toISOString());
            
            termsModal.classList.remove('active');
            enableForm();
            
            // Foca no campo de usuário
            document.getElementById('username').focus();
        });
    }

    // Botão CANCELAR
    if (btnCancel) {
        btnCancel.addEventListener('click', function() {
            alert('Você precisa aceitar os termos de uso para acessar o sistema.');
            // Opcional: redirecionar para outra página
            // window.location.href = 'https://seusite.com';
        });
    }

    // Impede fechar o modal clicando fora
    if (termsModal) {
        termsModal.addEventListener('click', function(e) {
            if (e.target === termsModal) {
                alert('Você precisa aceitar ou cancelar os termos de uso.');
            }
        });
    }

    // Verifica os termos ao carregar a página
    if (termsModal) {
        checkTermsAcceptance();
    }
});