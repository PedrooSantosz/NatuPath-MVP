<?php
// ============================================
// RECUPERAR SENHA - ETAPA 3
// Define nova senha
// ============================================
session_start();
require_once __DIR__ . '/../../config/config.php';

// Verifica se passou pelas etapas anteriores
if (!isset($_SESSION['reset_token']) || !isset($_SESSION['reset_user_id']) || !isset($_SESSION['identity_verified'])) {
    $_SESSION['error'] = "Acesso inválido. Por favor, inicie o processo novamente.";
    header('Location: forgot-password.php');
    exit;
}

// Verifica expiração
if (isset($_SESSION['reset_expires']) && strtotime($_SESSION['reset_expires']) < time()) {
    $_SESSION['error'] = "Tempo expirado! Por favor, solicite a recuperação novamente.";
    unset($_SESSION['reset_token']);
    unset($_SESSION['reset_user_id']);
    unset($_SESSION['reset_expires']);
    unset($_SESSION['identity_verified']);
    header('Location: forgot-password.php');
    exit;
}

// Busca dados do usuário
try {
    $stmt = $conn->prepare("SELECT username, nome FROM usuarios WHERE id = :id");
    $stmt->execute(['id' => $_SESSION['reset_user_id']]);
    $user = $stmt->fetch();
    
    if (!$user) {
        $_SESSION['error'] = "Erro ao processar solicitação.";
        header('Location: forgot-password.php');
        exit;
    }
} catch(PDOException $e) {
    error_log("Erro: " . $e->getMessage());
    $_SESSION['error'] = "Erro ao processar solicitação.";
    header('Location: forgot-password.php');
    exit;
}

// Mensagens
$error = isset($_SESSION['error']) ? $_SESSION['error'] : '';
$success = isset($_SESSION['success']) ? $_SESSION['success'] : '';
unset($_SESSION['error']);
unset($_SESSION['success']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redefinir Senha - NatuPath</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/login.css">
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <i class="fas fa-lock"></i>
            <h1>Redefinir Senha</h1>
            <p>Olá, <?php echo htmlspecialchars($user['nome']); ?>!</p>
        </div>

        <!-- Sucesso na verificação -->
        <div class="alert alert-success" style="margin: 20px 30px 0;">
            <i class="fas fa-check-circle"></i>
            <span>Identidade verificada! Agora defina sua nova senha.</span>
        </div>

        <!-- Mensagem de ERRO -->
        <?php if ($error): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i>
            <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>

        <!-- FORMULÁRIO -->
        <form class="login-form" method="POST" action="../../config/validacao/process_reset_password.php" id="resetForm">
            
            <!-- Nova Senha -->
            <div class="form-group">
                <label for="nova_senha">Nova Senha</label>
                <div class="input-wrapper">
                    <input type="password" 
                           id="nova_senha" 
                           name="nova_senha" 
                           placeholder="Digite a nova senha" 
                           required
                           minlength="6"
                           autofocus>
                    <i class="fas fa-lock"></i>
                    <i class="fas fa-eye toggle-password" id="toggleNovaSenha"></i>
                </div>
                <span style="font-size: 12px; color: var(--text-light); display: block; margin-top: 5px;">
                    Mínimo de 6 caracteres
                </span>
            </div>

            <!-- Confirmar Senha -->
            <div class="form-group">
                <label for="confirmar_senha">Confirmar Nova Senha</label>
                <div class="input-wrapper">
                    <input type="password" 
                           id="confirmar_senha" 
                           name="confirmar_senha" 
                           placeholder="Confirme a nova senha" 
                           required
                           minlength="6">
                    <i class="fas fa-lock"></i>
                    <i class="fas fa-eye toggle-password" id="toggleConfirmar"></i>
                </div>
                <span class="error-message" id="senhaErro" style="display:none; color: var(--error-color); font-size: 12px; margin-top: 5px;">
                    As senhas não coincidem!
                </span>
            </div>

            <button type="submit" class="login-btn">
                <i class="fas fa-check"></i> Redefinir Senha
            </button>
        </form>

        <div class="login-footer">
            <p>Desenvolvido com <i class="fas fa-heart"></i> para um futuro sustentável</p>
        </div>
    </div>

    <script src="../../assets/js/login-script.js"></script>
    <script src="../../assets/js/reset-password.js"></script>
</body>
</html>