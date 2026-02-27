// ============================================
// NATUPATH - SCRIPT 
// ============================================

document.addEventListener('DOMContentLoaded', function() {
    
    'use strict';
    
    // ============================================
    // 1. SIDEBAR TOGGLE
    // ============================================
    
    const sidebarToggle = document.querySelector('.sidebar-toggle');
    const nav = document.querySelector('nav');
    const body = document.body;
    
    const sidebarClosed = localStorage.getItem('sidebar-status') === 'close';
    if (sidebarClosed && nav) {
        nav.classList.add('close');
    }
    
    if (sidebarToggle && nav) {
        sidebarToggle.addEventListener('click', function() {
            nav.classList.toggle('close');
            localStorage.setItem('sidebar-status', 
                nav.classList.contains('close') ? 'close' : 'open'
            );
        });
    }
    
    // ============================================
    // 2. DARK MODE
    // ============================================
    
    const modeText = document.querySelector('.mode .link-name');
    const modeIcon = document.querySelector('.mode i');
    
    // Carrega preferência do modo escuro ao iniciar
    const darkModeEnabled = localStorage.getItem('darkMode') === 'enabled';
    if (darkModeEnabled) {
        body.classList.add('dark');
        if (modeText) modeText.textContent = 'Modo Claro';
        if (modeIcon) {
            // Suporta Font Awesome
            modeIcon.classList.remove('fa-moon');
            modeIcon.classList.add('fa-sun');
            // Suporta Unicons
            modeIcon.classList.remove('uil-moon');
            modeIcon.classList.add('uil-sun');
        }
    }
    
    /**
     * Alterna entre modo escuro e claro
     * Função global chamada via onclick no HTML
     * Suporta Font Awesome (fas) e Unicons (uil)
     */
    window.toggleDarkMode = function() {
        body.classList.toggle('dark');
        
        const modeText = document.querySelector('.mode .link-name');
        const modeIcon = document.querySelector('.mode i');
        
        // Atualiza texto e ícone
        if (body.classList.contains('dark')) {
            if (modeText) modeText.textContent = 'Modo Claro';
            if (modeIcon) {
                // Suporta Font Awesome
                modeIcon.classList.remove('fa-moon');
                modeIcon.classList.add('fa-sun');
                // Suporta Unicons
                modeIcon.classList.remove('uil-moon');
                modeIcon.classList.add('uil-sun');
            }
            localStorage.setItem('darkMode', 'enabled');
        } else {
            if (modeText) modeText.textContent = 'Modo Escuro';
            if (modeIcon) {
                // Suporta Font Awesome
                modeIcon.classList.remove('fa-sun');
                modeIcon.classList.add('fa-moon');
                // Suporta Unicons
                modeIcon.classList.remove('uil-sun');
                modeIcon.classList.add('uil-moon');
            }
            localStorage.setItem('darkMode', 'disabled');
        }
        
        body.style.transition = 'background-color 0.3s ease';
    };
    
    
    // ============================================
    // 3. DESTACAR PÁGINA ATIVA
    // ============================================
    
    const currentPage = window.location.pathname.split('/').pop();
    const menuLinks = document.querySelectorAll('.nav-links li a');
    
    menuLinks.forEach(link => {
        const href = link.getAttribute('href');
        if (href && (href === currentPage || href.includes(currentPage))) {
            link.classList.add('active');
        }
    });
    
    // ============================================
    // 4. FUNÇÕES DOS MODAIS (SEM FECHAR CLICANDO FORA)
    // ============================================
    
    /**
     * Abre um modal pelo ID
     */
    window.openModal = function(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.style.display = 'block';
            document.body.style.overflow = 'hidden';
            
            // Foca no primeiro input
            setTimeout(() => {
                const firstInput = modal.querySelector('input:not([type="hidden"]), textarea, select');
                if (firstInput) firstInput.focus();
            }, 100);
        }
    };
    
    /**
     * Fecha um modal pelo ID
     */
    window.closeModal = function(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
            
            // Limpa o formulário se existir
            const form = modal.querySelector('form');
            if (form) {
                form.reset();
                // Remove preview de imagem se existir
                const preview = form.querySelector('.image-preview');
                if (preview) preview.remove();
            }
        }
    };
    
    // Fechar modal com tecla ESC (opcional - pode remover se quiser)
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const modals = document.querySelectorAll('.modal');
            modals.forEach(modal => {
                if (modal.style.display === 'block') {
                    const modalId = modal.getAttribute('id');
                    if (modalId) closeModal(modalId);
                }
            });
        }
    });
    
    // ============================================
    // 5. VALIDAÇÃO DOS FORMULÁRIOS
    // ============================================
    
    function showFieldError(field, message) {
        const formGroup = field.closest('.form-group');
        if (formGroup) {
            formGroup.classList.add('error');
            
            const existingError = formGroup.querySelector('.error-message');
            if (existingError) existingError.remove();
            
            const errorMsg = document.createElement('small');
            errorMsg.className = 'error-message';
            errorMsg.style.color = 'var(--error-color)';
            errorMsg.style.marginTop = '5px';
            errorMsg.textContent = message;
            formGroup.appendChild(errorMsg);
            
            field.style.borderColor = 'var(--error-color)';
        }
    }
    
    function clearFieldError(field) {
        const formGroup = field.closest('.form-group');
        if (formGroup) {
            formGroup.classList.remove('error');
            const errorMsg = formGroup.querySelector('.error-message');
            if (errorMsg) errorMsg.remove();
            field.style.borderColor = '';
        }
    }
    
    // Remove erro quando usuário digita
    const allInputs = document.querySelectorAll('input, textarea, select');
    allInputs.forEach(input => {
        input.addEventListener('input', function() {
            clearFieldError(this);
        });
    });
    
    // Formulário de Boas Práticas
    const formBoasPraticas = document.getElementById('formBoasPraticas');
    if (formBoasPraticas) {
        formBoasPraticas.addEventListener('submit', function(e) {
            const titulo = document.getElementById('titulo_bp');
            const descricao = document.getElementById('descricao_bp');
            
            if (titulo && titulo.value.trim().length < 5) {
                e.preventDefault();
                showFieldError(titulo, 'O título deve ter pelo menos 5 caracteres');
                return false;
            }
            
            if (descricao && descricao.value.trim().length < 20) {
                e.preventDefault();
                showFieldError(descricao, 'A descrição deve ter pelo menos 20 caracteres');
                return false;
            }
            
            // Desabilita botão
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';
            }
        });
    }
    
    // Formulário de Não Conformidades
    const formNaoConformidades = document.getElementById('formNaoConformidades');
    if (formNaoConformidades) {
        formNaoConformidades.addEventListener('submit', function(e) {
            const titulo = document.getElementById('titulo_nc');
            const descricao = document.getElementById('descricao_nc');
            
            if (titulo && titulo.value.trim().length < 5) {
                e.preventDefault();
                showFieldError(titulo, 'O título deve ter pelo menos 5 caracteres');
                return false;
            }
            
            if (descricao && descricao.value.trim().length < 20) {
                e.preventDefault();
                showFieldError(descricao, 'A descrição deve ter pelo menos 20 caracteres');
                return false;
            }
            
            // Desabilita botão
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';
            }
        });
    }
    
    // ============================================
    // 6. DATA DE HOJE COMO PADRÃO
    // ============================================
    
    const dateInputs = {
        'data_pratica': document.getElementById('data_pratica'),
        'data_ocorrencia': document.getElementById('data_ocorrencia')
    };
    
    Object.values(dateInputs).forEach(input => {
        if (input && !input.value) {
            input.valueAsDate = new Date();
        }
    });
    
    // ============================================
    // 7. VALIDAÇÃO DE UPLOAD DE IMAGEM
    // ============================================
    
    const fileInputs = document.querySelectorAll('input[type="file"][accept*="image"]');
    
    fileInputs.forEach(input => {
        input.addEventListener('change', function(e) {
            const file = e.target.files[0];
            
            if (file) {
                const maxSize = 5 * 1024 * 1024;
                if (file.size > maxSize) {
                    showNotification('A imagem não pode ser maior que 5MB!', 'error');
                    this.value = '';
                    return;
                }
                
                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
                if (!allowedTypes.includes(file.type)) {
                    showNotification('Formato não permitido! Use JPG, PNG, GIF ou WEBP.', 'error');
                    this.value = '';
                    return;
                }
                
                // Preview
                createImagePreview(file, this);
            }
        });
    });
    
    function createImagePreview(file, input) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const formGroup = input.closest('.form-group');
            if (formGroup) {
                let preview = formGroup.querySelector('.image-preview');
                if (!preview) {
                    preview = document.createElement('div');
                    preview.className = 'image-preview';
                    preview.style.cssText = 'margin-top: 10px; max-width: 200px;';
                    formGroup.appendChild(preview);
                }
                preview.innerHTML = `<img src="${e.target.result}" style="width: 100%; border-radius: 8px; border: 2px solid var(--border-color);">`;
            }
        };
        reader.readAsDataURL(file);
    }
    
    // ============================================
    // 8. AUTO-FECHAR ALERTAS
    // ============================================
    
    const alerts = document.querySelectorAll('.alert');
    
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-10px)';
            setTimeout(() => alert.remove(), 500);
        }, 5000);
    });
    
    window.closeAlert = function(alertId) {
        const alert = document.getElementById(alertId);
        if (alert) {
            alert.style.transition = 'opacity 0.3s ease';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 300);
        }
    };
    
    // ============================================
    // 9. AUTO-AJUSTE DE TEXTAREA
    // ============================================
    
    const textareas = document.querySelectorAll('textarea');
    
    textareas.forEach(textarea => {
        adjustTextareaHeight(textarea);
        textarea.addEventListener('input', function() {
            adjustTextareaHeight(this);
        });
    });
    
    function adjustTextareaHeight(textarea) {
        textarea.style.height = 'auto';
        textarea.style.height = (textarea.scrollHeight) + 'px';
    }
    
    // ============================================
    // 10. CONTADOR DE CARACTERES
    // ============================================
    
    function addCharCounter(element) {
        const maxLength = element.getAttribute('maxlength');
        if (!maxLength) return;
        
        const counter = document.createElement('small');
        counter.className = 'char-counter';
        counter.style.cssText = 'display: block; text-align: right; margin-top: 5px; color: var(--text-light); font-size: 12px;';
        counter.textContent = `0/${maxLength} caracteres`;
        
        element.parentNode.appendChild(counter);
        
        element.addEventListener('input', function() {
            const length = this.value.length;
            counter.textContent = `${length}/${maxLength} caracteres`;
            
            if (length > maxLength * 0.9) {
                counter.style.color = 'var(--error-color)';
            } else {
                counter.style.color = 'var(--text-light)';
            }
        });
    }
    
    document.querySelectorAll('input[maxlength], textarea[maxlength]').forEach(el => {
        if (!el.parentNode.querySelector('.char-counter')) {
            addCharCounter(el);
        }
    });
    
    // ============================================
    // 11. NOTIFICAÇÕES TOAST
    // ============================================
    
    window.showNotification = function(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.style.cssText = `
            position: fixed;
            top: 80px;
            right: 20px;
            padding: 15px 20px;
            background-color: var(--panel-color);
            border-left: 4px solid var(--${type === 'error' ? 'error' : type === 'success' ? 'success' : type === 'warning' ? 'warning' : 'info'}-color);
            border-radius: 8px;
            box-shadow: var(--shadow-lg);
            z-index: 9999;
            animation: slideInRight 0.3s ease;
            max-width: 350px;
            color: var(--text-color);
        `;
        
        const icon = {
            success: 'fa-check-circle',
            error: 'fa-exclamation-circle',
            warning: 'fa-exclamation-triangle',
            info: 'fa-info-circle'
        };
        
        toast.innerHTML = `
            <i class="fas ${icon[type]}" style="margin-right: 10px; color: var(--${type === 'error' ? 'error' : type === 'success' ? 'success' : type === 'warning' ? 'warning' : 'info'}-color);"></i>
            ${message}
        `;
        
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.style.animation = 'slideOutRight 0.3s ease';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    };
    
    // Adiciona animações CSS
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideInRight {
            from {
                transform: translateX(400px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        @keyframes slideOutRight {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(400px);
                opacity: 0;
            }
        }
    `;
    document.head.appendChild(style);
    
    // ============================================
    // 12. ANIMAÇÃO DOS BOXES
    // ============================================
    
    const boxes = document.querySelectorAll('.boxes .box');
    
    boxes.forEach((box, index) => {
        box.style.opacity = '0';
        box.style.transform = 'translateY(20px)';
        
        setTimeout(() => {
            box.style.transition = 'all 0.5s ease';
            box.style.opacity = '1';
            box.style.transform = 'translateY(0)';
        }, index * 100);
    });
    
    // ============================================
    // 13. CONFIRMAÇÃO DE LOGOUT
    // ============================================
    
    const logoutLink = document.querySelector('a[href="logout.php"]');
    
    if (logoutLink) {
        logoutLink.addEventListener('click', function(e) {
            if (!confirm('Tem certeza que deseja sair?')) {
                e.preventDefault();
            }
        });
    }
    
    // ============================================
    // 14. LOG DE INICIALIZAÇÃO
    // ============================================
    
    console.log('%c🌱 NatuPath', 'color: #10b981; font-size: 20px; font-weight: bold;');
    console.log('%cSistema carregado com sucesso!', 'color: #059669; font-size: 14px;');
    console.log('%cVersão: 2.1.0 (Atualizado)', 'color: #666; font-size: 12px;');
    
});

// ============================================
// FUNÇÕES AUXILIARES GLOBAIS
// ============================================

function formatarData(data) {
    const d = new Date(data);
    if (isNaN(d)) return '-';
    const dia = String(d.getDate()).padStart(2, '0');
    const mes = String(d.getMonth() + 1).padStart(2, '0');
    const ano = d.getFullYear();
    return `${dia}/${mes}/${ano}`;
}

function formatarDataHora(data) {
    const d = new Date(data);
    if (isNaN(d)) return '-';
    const dia = String(d.getDate()).padStart(2, '0');
    const mes = String(d.getMonth() + 1).padStart(2, '0');
    const ano = d.getFullYear();
    const hora = String(d.getHours()).padStart(2, '0');
    const minuto = String(d.getMinutes()).padStart(2, '0');
    return `${dia}/${mes}/${ano} ${hora}:${minuto}`;
}

function formatarNumero(numero) {
    if (isNaN(numero)) return '0';
    return numero.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
}

function formatarMoeda(valor) {
    if (isNaN(valor)) return 'R$ 0,00';
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL'
    }).format(valor);
}

// ============================================
// FIM DO SCRIPT
// ============================================
/* ============================================
   MODAIS - FUNÇÕES JAVASCRIPT
   ============================================ */

// Lista de ícones disponíveis (Font Awesome)
const iconesDisponiveis = [
    // Natureza e Ambiente
    'fa-leaf', 'fa-tree', 'fa-seedling', 'fa-globe', 'fa-mountain', 'fa-water',
    'fa-sun', 'fa-cloud', 'fa-wind', 'fa-snowflake', 'fa-fire', 'fa-bolt',
    
    // Energia e Sustentabilidade
    'fa-solar-panel', 'fa-plug', 'fa-battery-full', 'fa-lightbulb', 'fa-charging-station',
    
    // Reciclagem e Resíduos
    'fa-recycle', 'fa-trash', 'fa-trash-alt', 'fa-dumpster', 'fa-boxes',
    
    // Água e Líquidos
    'fa-droplet', 'fa-faucet', 'fa-shower', 'fa-toilet', 'fa-sink',
    
    // Alertas e Avisos
    'fa-exclamation-triangle', 'fa-exclamation-circle', 'fa-radiation', 
    'fa-biohazard', 'fa-skull-crossbones', 'fa-fire-extinguisher',
    
    // Verificação e Aprovação
    'fa-check-circle', 'fa-check', 'fa-check-double', 'fa-thumbs-up',
    'fa-heart', 'fa-star', 'fa-award', 'fa-medal', 'fa-trophy',
    
    // Ferramentas e Manutenção
    'fa-wrench', 'fa-screwdriver', 'fa-hammer', 'fa-tools', 'fa-cog',
    
    // Construção e Estrutura
    'fa-building', 'fa-home', 'fa-warehouse', 'fa-industry', 'fa-factory',
    
    // Transporte
    'fa-car', 'fa-truck', 'fa-bicycle', 'fa-bus', 'fa-train',
    
    // Documentos e Organização
    'fa-clipboard', 'fa-clipboard-list', 'fa-file-alt', 'fa-folder',
    'fa-book', 'fa-bookmark', 'fa-tags', 'fa-tag',
    
    // Pessoas e Grupos
    'fa-user', 'fa-users', 'fa-user-tie', 'fa-user-shield', 'fa-hands-helping',
    
    // Comunicação
    'fa-bell', 'fa-comment', 'fa-comments', 'fa-bullhorn', 'fa-envelope',
    
    // Tempo e Calendário
    'fa-clock', 'fa-calendar', 'fa-calendar-check', 'fa-hourglass',
    
    // Saúde e Segurança
    'fa-shield-alt', 'fa-first-aid', 'fa-heartbeat', 'fa-hospital',
    'fa-hard-hat', 'fa-vest',
    
    // Ciência e Tecnologia
    'fa-flask', 'fa-microscope', 'fa-atom', 'fa-robot', 'fa-microchip',
    
    // Comida e Consumo
    'fa-utensils', 'fa-coffee', 'fa-mug-hot', 'fa-wine-bottle', 'fa-apple-alt',
    
    // Diversos
    'fa-chart-line', 'fa-chart-bar', 'fa-chart-pie', 'fa-percentage',
    'fa-dollar-sign', 'fa-coins', 'fa-box', 'fa-gift', 'fa-shopping-cart'
];

/**
 * Abre um modal
 * @param {string} modalId - ID do modal a ser aberto
 */
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'block';
        document.body.style.overflow = 'hidden'; // Impede scroll da página
    }
}

/**
 * Fecha um modal
 * @param {string} modalId - ID do modal a ser fechado
 */
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = ''; // Restaura scroll da página
        
        // Limpa o formulário se existir
        const form = modal.querySelector('form');
        if (form) {
            form.reset();
        }
    }
}

/**
 * Inicializa os modais ao carregar a página
 * Desabilita o clique fora do modal para fechá-lo
 */
document.addEventListener('DOMContentLoaded', function() {
    // Remove comportamento de fechar ao clicar fora
    const modais = document.querySelectorAll('.modal');
    modais.forEach(modal => {
        modal.addEventListener('click', function(e) {
            // Não faz nada se clicar fora - usuário DEVE usar o botão X
            e.stopPropagation();
        });
    });
    
    // Permite fechar com ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const modaisVisiveis = document.querySelectorAll('.modal[style*="display: block"]');
            modaisVisiveis.forEach(modal => {
                modal.style.display = 'none';
                document.body.style.overflow = '';
            });
        }
    });
});

/**
 * Cria o seletor de ícones visual
 * @param {string} inputId - ID do input onde o ícone será salvo
 * @param {string} previewId - ID do elemento de preview
 * @param {string} cor - Cor para o preview (opcional)
 */
function criarSeletorIcones(inputId, previewId, cor = '#10b981') {
    const container = document.createElement('div');
    container.className = 'icon-selector';
    
    iconesDisponiveis.forEach(icone => {
        const option = document.createElement('div');
        option.className = 'icon-option';
        option.innerHTML = `<i class="fas ${icone}"></i>`;
        option.title = icone;
        
        option.addEventListener('click', function() {
            // Remove seleção anterior
            container.querySelectorAll('.icon-option').forEach(opt => {
                opt.classList.remove('selected');
            });
            
            // Adiciona seleção atual
            this.classList.add('selected');
            
            // Atualiza input hidden
            const input = document.getElementById(inputId);
            if (input) {
                input.value = icone;
            }
            
            // Atualiza preview
            atualizarPreviewIcone(previewId, icone, cor);
        });
        
        container.appendChild(option);
    });
    
    return container;
}

/**
 * Atualiza o preview do ícone selecionado
 * @param {string} previewId - ID do elemento de preview
 * @param {string} icone - Classe do ícone
 * @param {string} cor - Cor de fundo
 */
function atualizarPreviewIcone(previewId, icone, cor) {
    const preview = document.getElementById(previewId);
    if (preview) {
        preview.innerHTML = `
            <div class="icon-preview-box" style="background-color: ${cor}">
                <i class="fas ${icone}"></i>
            </div>
            <div class="icon-preview-text">
                <strong>Ícone Selecionado</strong>
                <span>${icone}</span>
            </div>
        `;
    }
}

/**
 * Substitui o input de texto do ícone por um seletor visual
 * Deve ser chamado quando o formulário de categoria for aberto
 */
function inicializarSeletorIcone() {
    const inputIcone = document.getElementById('icone');
    const inputCor = document.getElementById('cor');
    
    if (!inputIcone) return;
    
    // Cria container para o seletor
    const wrapper = document.createElement('div');
    wrapper.style.marginBottom = '15px';
    
    // Cria preview
    const preview = document.createElement('div');
    preview.id = 'icone-preview';
    preview.className = 'icon-preview';
    
    // Define ícone e cor iniciais
    const iconeInicial = inputIcone.value || 'fa-leaf';
    const corInicial = inputCor ? inputCor.value : '#10b981';
    
    atualizarPreviewIcone('icone-preview', iconeInicial, corInicial);
    
    // Adiciona preview
    wrapper.appendChild(preview);
    
    // Cria seletor
    const seletor = criarSeletorIcones('icone', 'icone-preview', corInicial);
    wrapper.appendChild(seletor);
    
    // Esconde o input original
    inputIcone.type = 'hidden';
    
    // Remove o label e link antigos
    const label = inputIcone.parentElement.querySelector('label');
    const small = inputIcone.parentElement.querySelector('small');
    if (label) label.remove();
    if (small) small.remove();
    
    // Adiciona novo label
    const novoLabel = document.createElement('label');
    novoLabel.innerHTML = '<strong>Selecione um Ícone *</strong>';
    
    // Insere tudo no lugar certo
    inputIcone.parentElement.insertBefore(novoLabel, inputIcone);
    inputIcone.parentElement.insertBefore(wrapper, inputIcone);
    
    // Marca o ícone atual como selecionado
    setTimeout(() => {
        const opcoes = seletor.querySelectorAll('.icon-option');
        opcoes.forEach(opt => {
            const iconeOpt = opt.querySelector('i').className;
            if (iconeOpt.includes(iconeInicial)) {
                opt.classList.add('selected');
            }
        });
    }, 100);
    
    // Atualiza preview quando cor mudar
    if (inputCor) {
        inputCor.addEventListener('change', function() {
            const iconeAtual = inputIcone.value || 'fa-leaf';
            atualizarPreviewIcone('icone-preview', iconeAtual, this.value);
            
            // Atualiza cor do preview box
            const previewBox = document.querySelector('.icon-preview-box');
            if (previewBox) {
                previewBox.style.backgroundColor = this.value;
            }
        });
    }
}

/**
 * Validação de formulários
 */
function validarFormulario(formId) {
    const form = document.getElementById(formId);
    if (!form) return false;
    
    let valido = true;
    const campos = form.querySelectorAll('[required]');
    
    campos.forEach(campo => {
        if (!campo.value.trim()) {
            campo.style.borderColor = '#ef4444';
            valido = false;
            
            // Remove erro ao digitar
            campo.addEventListener('input', function() {
                this.style.borderColor = '';
            });
        }
    });
    
    if (!valido) {
        alert('Por favor, preencha todos os campos obrigatórios!');
    }
    
    return valido;
}

/**
 * Preview de imagem antes de fazer upload
 */
function previewImagem(input, previewId) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            const preview = document.getElementById(previewId);
            if (preview) {
                if (preview.tagName === 'IMG') {
                    preview.src = e.target.result;
                } else {
                    preview.style.backgroundImage = `url(${e.target.result})`;
                }
            }
        };
        
        reader.readAsDataURL(input.files[0]);
    }
}

/**
 * Confirma exclusão
 */
function confirmarExclusao(mensagem) {
    return confirm(mensagem || 'Tem certeza que deseja excluir este item? Esta ação não pode ser desfeita!');
}

/**
 * Mostra/esconde senha
 */
function togglePasswordVisibility(inputId, iconId) {
    const input = document.getElementById(inputId);
    const icon = document.getElementById(iconId);
    
    if (!input || !icon) return;
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

/**
 * Medidor de força da senha
 */
function verificarForcaSenha(senha) {
    let forca = 0;
    
    // Comprimento
    if (senha.length >= 6) forca += 20;
    if (senha.length >= 8) forca += 20;
    if (senha.length >= 12) forca += 10;
    
    // Caracteres
    if (/[a-z]/.test(senha)) forca += 10;
    if (/[A-Z]/.test(senha)) forca += 15;
    if (/[0-9]/.test(senha)) forca += 15;
    if (/[^a-zA-Z0-9]/.test(senha)) forca += 10;
    
    let texto = '';
    let cor = '';
    
    if (forca <= 30) {
        texto = 'Fraca';
        cor = '#ef4444';
    } else if (forca <= 50) {
        texto = 'Média';
        cor = '#f59e0b';
    } else if (forca <= 75) {
        texto = 'Boa';
        cor = '#3b82f6';
    } else {
        texto = 'Forte';
        cor = '#10b981';
    }
    
    return { forca, texto, cor };
}



// Export para uso global
window.openModal = openModal;
window.closeModal = closeModal;
window.criarSeletorIcones = criarSeletorIcones;
window.inicializarSeletorIcone = inicializarSeletorIcone;
window.validarFormulario = validarFormulario;
window.previewImagem = previewImagem;
window.confirmarExclusao = confirmarExclusao;
window.togglePasswordVisibility = togglePasswordVisibility;
window.verificarForcaSenha = verificarForcaSenha;
// ============================================
// 6. APROVAÇÃO EM MASSA
// ============================================

/**
 * Atualiza o contador de registros selecionados
 */
function atualizarContador() {
    const checkboxes = document.querySelectorAll('.checkbox-bp:checked');
    const contador = checkboxes.length;
    const painel = document.getElementById('painelAprovacaoMassa');
    const spanContador = document.getElementById('contadorSelecionados');
    
    if (!painel || !spanContador) return;
    
    spanContador.textContent = contador;
    
    if (contador > 0) {
        painel.style.display = 'block';
    } else {
        painel.style.display = 'none';
    }
}

/**
 * Seleciona/deseleciona todos os checkboxes
 */
/**
 * Seleciona/deseleciona todos os checkboxes
 */
function selecionarTodos(checkbox, tipo) {
    // Mapeia os tipos para as classes corretas
    let classe;
    if (tipo === 'boa_pratica') {
        classe = '.checkbox-bp';
    } else if (tipo === 'nao_conformidade') {
        classe = '.checkbox-nc';
    } else {
        return; // Tipo inválido
    }
    
    const checkboxes = document.querySelectorAll(classe);
    
    checkboxes.forEach(cb => {
        cb.checked = checkbox.checked;
    });
    
    atualizarContador();
}

/**
 * Limpa todas as seleções
 */
function limparSelecao() {
    const checkboxes = document.querySelectorAll('.checkbox-bp:checked');
    
    checkboxes.forEach(cb => {
        cb.checked = false;
    });
    
    const selecionarTodos = document.getElementById('selecionarTodosBP');
    if (selecionarTodos) {
        selecionarTodos.checked = false;
    }
    
    atualizarContador();
}

/**
 * Aprova múltiplos registros de uma vez
 */
function aprovarEmMassa() {
    const checkboxes = document.querySelectorAll('.checkbox-bp:checked');
    const ids = Array.from(checkboxes).map(cb => cb.value);
    
    if (ids.length === 0) {
        alert('Nenhum registro selecionado!');
        return;
    }
    
    if (!confirm(`Confirma a aprovação de ${ids.length} boa(s) prática(s)?\n\nTodos os registros selecionados serão aprovados.`)) {
        return;
    }
    
    // Criar formulário e enviar
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '../../config/validacao/process_aprovacao_massa.php';
    
    // Adicionar IDs
    ids.forEach(id => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'ids[]';
        input.value = id;
        form.appendChild(input);
    });
    
    // Adicionar tipo
    const inputTipo = document.createElement('input');
    inputTipo.type = 'hidden';
    inputTipo.name = 'tipo';
    inputTipo.value = 'boa_pratica';
    form.appendChild(inputTipo);
    
    // Adicionar ação
    const inputAcao = document.createElement('input');
    inputAcao.type = 'hidden';
    inputAcao.name = 'acao';
    inputAcao.value = 'aprovar_massa';
    form.appendChild(inputAcao);
    
    document.body.appendChild(form);
    form.submit();
}

/**
 * Rejeita múltiplos registros de uma vez
 */
function rejeitarEmMassa() {
    const checkboxes = document.querySelectorAll('.checkbox-bp:checked');
    const ids = Array.from(checkboxes).map(cb => cb.value);
    
    if (ids.length === 0) {
        alert('Nenhum registro selecionado!');
        return;
    }
    
    const motivo = prompt(`Você está prestes a rejeitar ${ids.length} registro(s).\n\nInforme o motivo da rejeição em massa (mínimo 10 caracteres):`);
    
    if (!motivo) {
        return;
    }
    
    if (motivo.trim().length < 10) {
        alert('O motivo deve ter pelo menos 10 caracteres!');
        return;
    }
    
    if (!confirm(`Confirma a rejeição de ${ids.length} boa(s) prática(s)?\n\nOs colaboradores serão notificados.`)) {
        return;
    }
    
    // Criar formulário e enviar
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '../../config/validacao/process_aprovacao_massa.php';
    
    // Adicionar IDs
    ids.forEach(id => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'ids[]';
        input.value = id;
        form.appendChild(input);
    });
    
    // Adicionar tipo
    const inputTipo = document.createElement('input');
    inputTipo.type = 'hidden';
    inputTipo.name = 'tipo';
    inputTipo.value = 'boa_pratica';
    form.appendChild(inputTipo);
    
    // Adicionar ação
    const inputAcao = document.createElement('input');
    inputAcao.type = 'hidden';
    inputAcao.name = 'acao';
    inputAcao.value = 'rejeitar_massa';
    form.appendChild(inputAcao);
    
    document.body.appendChild(form);
    form.submit();
}

// Adicionar exports para uso global
window.atualizarContador = atualizarContador;
window.selecionarTodos = selecionarTodos;
window.limparSelecao = limparSelecao;
window.aprovarEmMassa = aprovarEmMassa;
window.rejeitarEmMassa = rejeitarEmMassa;

/**
 * Atualiza o contador de não conformidades selecionadas
 */
function atualizarContadorNC() {
    const checkboxes = document.querySelectorAll('.checkbox-nc:checked');
    const contador = checkboxes.length;
    const painel = document.getElementById('painelAprovacaoMassaNC');
    const spanContador = document.getElementById('contadorSelecionadosNC');
    
    if (!painel || !spanContador) return;
    
    spanContador.textContent = contador;
    
    if (contador > 0) {
        painel.style.display = 'block';
    } else {
        painel.style.display = 'none';
    }
}

/**
 * Limpa todas as seleções de NC
 */
function limparSelecaoNC() {
    const checkboxes = document.querySelectorAll('.checkbox-nc:checked');
    
    checkboxes.forEach(cb => {
        cb.checked = false;
    });
    
    const selecionarTodos = document.getElementById('selecionarTodosNC');
    if (selecionarTodos) {
        selecionarTodos.checked = false;
    }
    
    atualizarContadorNC();
}

/**
 * Marca múltiplas NCs como "Em Análise"
 */
function analisarEmMassa() {
    const checkboxes = document.querySelectorAll('.checkbox-nc:checked');
    const ids = Array.from(checkboxes).map(cb => cb.value);
    
    if (ids.length === 0) {
        alert('Nenhum registro selecionado!');
        return;
    }
    
    if (!confirm(`Confirma marcar ${ids.length} não conformidade(s) como "Em Análise"?`)) {
        return;
    }
    
    // Criar formulário e enviar
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '../../config/validacao/process_aprovacao_massa.php';
    
    // Adicionar IDs
    ids.forEach(id => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'ids[]';
        input.value = id;
        form.appendChild(input);
    });
    
    // Adicionar tipo
    const inputTipo = document.createElement('input');
    inputTipo.type = 'hidden';
    inputTipo.name = 'tipo';
    inputTipo.value = 'nao_conformidade';
    form.appendChild(inputTipo);
    
    // Adicionar ação
    const inputAcao = document.createElement('input');
    inputAcao.type = 'hidden';
    inputAcao.name = 'acao';
    inputAcao.value = 'aprovar_massa';
    form.appendChild(inputAcao);
    
    document.body.appendChild(form);
    form.submit();
}

// Adicionar exports
window.atualizarContadorNC = atualizarContadorNC;
window.limparSelecaoNC = limparSelecaoNC;
window.analisarEmMassa = analisarEmMassa;