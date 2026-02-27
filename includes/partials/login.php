<?php
// ============================================
// 🔐 PÁGINA DE LOGIN 
// ============================================

// IMPORTANTE: Verificar "Lembrar-me" ANTES de tudo
require_once __DIR__ . '/../../config/validacao/check_remember.php';

// Inicia a sessão (se ainda não foi iniciada)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Se já estiver logado (pelo remember ou login normal), vai direto pro dashboard
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header('Location: dashboard.php');
    exit;
}

// Pega as mensagens de erro ou sucesso (se existirem)
$error = isset($_SESSION['error']) ? $_SESSION['error'] : '';
$success = isset($_SESSION['success']) ? $_SESSION['success'] : '';

// Limpa as mensagens da sessão (pra não aparecer de novo)
unset($_SESSION['error']);
unset($_SESSION['success']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - NatuPath</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/login.css">
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <i class="fas fa-leaf"></i>
            <h1>Bem-vindo ao NatuPath!</h1>
            <p>Sistema de Práticas Sustentáveis</p>
        </div>

        <!-- Mostra mensagem de ERRO se existir -->
        <?php if ($error): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i>
            <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>

        <!-- Mostra mensagem de SUCESSO se existir -->
        <?php if ($success): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <?php echo htmlspecialchars($success); ?>
        </div>
        <?php endif; ?>

        <!-- FORMULÁRIO - Envia os dados pro process_login.php -->
        <form class="login-form" id="loginForm" method="POST" action="../../config/validacao/process_login.php">
            
            <!-- Campo USUÁRIO -->
            <div class="form-group">
                <label for="username">Usuário</label>
                <div class="input-wrapper">
                    <input type="text" 
                           id="username" 
                           name="username" 
                           placeholder="Digite seu usuário" 
                           required 
                           autofocus>
                    <i class="fas fa-user"></i>
                </div>
                <span class="error-message">Por favor, insira seu usuário</span>
            </div>

            <!-- Campo SENHA -->
            <div class="form-group">
                <label for="password">Senha</label>
                <div class="input-wrapper">
                    <input type="password" 
                           id="password" 
                           name="password" 
                           placeholder="Digite sua senha" 
                           required>
                    <i class="fas fa-lock"></i>
                    <i class="fas fa-eye toggle-password" id="togglePassword"></i>
                </div>
                <span class="error-message">Por favor, insira sua senha</span>
            </div>

            <!-- Opções: Lembrar-me e Esqueci senha -->
            <div class="form-options">
                <label class="remember-me">
                    <input type="checkbox" name="remember" id="remember" value="1">
                    <span>Lembrar de mim</span>
                </label>
                <a href="forgot-password.php" class="forgot-password">Esqueci minha senha</a>
            </div>

            <!-- Botão de LOGIN -->
            <button type="submit" name="login" class="login-btn">
                <i class="fas fa-sign-in-alt"></i> Entrar
            </button>
        </form>

        <!-- Rodapé -->
        <div class="login-footer">
            <p>Desenvolvido com <i class="fas fa-heart"></i> para um futuro sustentável</p>
        </div>
    </div>

    <!-- Modal de Termos de Uso -->
    <div class="modal-overlay" id="termsModal">
        <div class="modal-container">
            <div class="modal-header">
                <i class="fas fa-file-contract"></i>
                <h2>TERMOS DE USO E PRIVACIDADE</h2>
            </div>
            <div class="modal-body">
                <p class="modal-intro">Ao acessar o sistema Natupath, você concorda com os seguintes termos:</p>
                <ul class="terms-list">
                    <li>
                        <i class="fas fa-shield-alt"></i>
                        <span>Seus dados pessoais serão tratados com confidencialidade e utilizados apenas para os fins do sistema.</span>
                    </li>
                    <li>
                        <i class="fas fa-key"></i>
                        <span>Você é responsável por manter a segurança de sua senha e credenciais de acesso.</span>
                    </li>
                    <li>
                        <i class="fas fa-check-circle"></i>
                        <span>O uso do sistema deve ser feito de forma ética e em conformidade com sua finalidade.</span>
                    </li>
                    <li>
                        <i class="fas fa-exclamation-triangle"></i>
                        <span>Nos reservamos o direito de suspender o acesso em caso de uso indevido.</span>
                    </li>
                    <li>
                        <i class="fas fa-chart-line"></i>
                        <span>Este sistema pode coletar dados de navegação para melhorias e segurança.</span>
                    </li>
                </ul>
                <p class="modal-footer-text">Ao clicar em <strong>"Aceito"</strong>, você confirma que leu e concorda com estes termos.</p>
            </div>
            <div class="modal-footer">
                <button class="btn-cancel" id="btnCancelTerms">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button class="btn-accept" id="btnAcceptTerms">
                    <i class="fas fa-check"></i> Aceito
                </button>
            </div>
        </div>
    </div>

    <script src="../../assets/js/login-script.js"></script>
</body>
</html>