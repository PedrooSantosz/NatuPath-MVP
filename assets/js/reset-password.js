// ============================================
// SCRIPT PARA REDEFINIR SENHA
// ============================================

document.addEventListener('DOMContentLoaded', function() {
    
    // ============================================
    // 1. TOGGLE MOSTRAR/OCULTAR SENHA
    // ============================================
    
    // Toggle nova senha
    const toggleNovaSenha = document.getElementById('toggleNovaSenha');
    const novaSenhaInput = document.getElementById('nova_senha');
    
    if (toggleNovaSenha && novaSenhaInput) {
        toggleNovaSenha.addEventListener('click', function() {
            const tipo = novaSenhaInput.getAttribute('type');
            
            if (tipo === 'password') {
                novaSenhaInput.setAttribute('type', 'text');
                toggleNovaSenha.classList.remove('fa-eye');
                toggleNovaSenha.classList.add('fa-eye-slash');
            } else {
                novaSenhaInput.setAttribute('type', 'password');
                toggleNovaSenha.classList.remove('fa-eye-slash');
                toggleNovaSenha.classList.add('fa-eye');
            }
        });
    }
    
    // Toggle confirmar senha
    const toggleConfirmar = document.getElementById('toggleConfirmar');
    const confirmarInput = document.getElementById('confirmar_senha');
    
    if (toggleConfirmar && confirmarInput) {
        toggleConfirmar.addEventListener('click', function() {
            const tipo = confirmarInput.getAttribute('type');
            
            if (tipo === 'password') {
                confirmarInput.setAttribute('type', 'text');
                toggleConfirmar.classList.remove('fa-eye');
                toggleConfirmar.classList.add('fa-eye-slash');
            } else {
                confirmarInput.setAttribute('type', 'password');
                toggleConfirmar.classList.remove('fa-eye-slash');
                toggleConfirmar.classList.add('fa-eye');
            }
        });
    }

    // ============================================
    // 2. VALIDAÇÃO EM TEMPO REAL
    // ============================================
    
    const resetForm = document.getElementById('resetForm');
    const senhaErro = document.getElementById('senhaErro');
    
    // Valida quando o usuário digita na confirmação
    if (confirmarInput && novaSenhaInput) {
        confirmarInput.addEventListener('input', function() {
            const novaSenha = novaSenhaInput.value;
            const confirmarSenha = confirmarInput.value;
            
            if (confirmarSenha.length > 0) {
                if (novaSenha !== confirmarSenha) {
                    senhaErro.style.display = 'block';
                    confirmarInput.style.borderColor = '#ef4444';
                } else {
                    senhaErro.style.display = 'none';
                    confirmarInput.style.borderColor = '#10b981';
                }
            } else {
                senhaErro.style.display = 'none';
                confirmarInput.style.borderColor = '';
            }
        });
    }

    // ============================================
    // 3. VALIDAÇÃO NO ENVIO DO FORMULÁRIO
    // ============================================
    
    if (resetForm) {
        resetForm.addEventListener('submit', function(e) {
            const novaSenha = novaSenhaInput.value;
            const confirmarSenha = confirmarInput.value;
            
            // Verifica se todos os campos estão preenchidos
            if (!novaSenha || !confirmarSenha) {
                e.preventDefault();
                alert('Por favor, preencha todos os campos!');
                return false;
            }
            
            // Verifica se as senhas coincidem
            if (novaSenha !== confirmarSenha) {
                e.preventDefault();
                senhaErro.style.display = 'block';
                confirmarInput.style.borderColor = '#ef4444';
                confirmarInput.focus();
                alert('As senhas não coincidem!');
                return false;
            }
            
            // Verifica o tamanho da senha
            if (novaSenha.length < 6) {
                e.preventDefault();
                alert('A senha deve ter no mínimo 6 caracteres!');
                novaSenhaInput.focus();
                return false;
            }
            
            // Se chegou aqui, tudo OK!
            return true;
        });
    }

    // ============================================
    // 4. INDICADOR DE FORÇA DA SENHA
    // ============================================
    
    if (novaSenhaInput) {
        novaSenhaInput.addEventListener('input', function() {
            const senha = this.value;
            
            if (senha.length > 0) {
                const forca = calcularForcaSenha(senha);
                
                // Muda a cor da borda baseado na força
                if (forca < 30) {
                    this.style.borderColor = '#ef4444'; // Vermelho - Fraca
                } else if (forca < 60) {
                    this.style.borderColor = '#f59e0b'; // Amarelo - Média
                } else {
                    this.style.borderColor = '#10b981'; // Verde - Forte
                }
            } else {
                this.style.borderColor = '';
            }
        });
    }
    
    // Função para calcular força da senha
    function calcularForcaSenha(senha) {
        let forca = 0;
        
        // Tamanho
        forca += senha.length * 4;
        
        // Letras maiúsculas
        if (/[A-Z]/.test(senha)) forca += 10;
        
        // Letras minúsculas
        if (/[a-z]/.test(senha)) forca += 10;
        
        // Números
        if (/[0-9]/.test(senha)) forca += 10;
        
        // Caracteres especiais
        if (/[^A-Za-z0-9]/.test(senha)) forca += 20;
        
        return Math.min(forca, 100);
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
});