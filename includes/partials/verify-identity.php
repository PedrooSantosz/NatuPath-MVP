<?php
// ============================================
// RECUPERAR SENHA - ETAPA 2
// Verifica identidade com email
// ============================================
session_start();
require_once __DIR__ . '/../../config/config.php';

// Verifica se tem o token na sessão
if (!isset($_SESSION['reset_token']) || !isset($_SESSION['reset_user_id'])) {
    $_SESSION['error'] = "Sessão expirada. Por favor, inicie o processo novamente.";
    header('Location: forgot-password.php');
    exit;
}

// Verifica se o token não expirou
if (isset($_SESSION['reset_expires']) && strtotime($_SESSION['reset_expires']) < time()) {
    $_SESSION['error'] = "Tempo expirado! Por favor, solicite a recuperação novamente.";
    unset($_SESSION['reset_token']);
    unset($_SESSION['reset_user_id']);
    unset($_SESSION['reset_expires']);
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

// Calcula tempo restante
$tempo_restante = strtotime($_SESSION['reset_expires']) - time();
$minutos_restantes = floor($tempo_restante / 60);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificar Identidade - NatuPath</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/login.css">
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <i class="fas fa-shield-alt"></i>
            <h1>Verificar Identidade</h1>
            <p>Olá, <?php echo htmlspecialchars($user['nome']); ?>!</p>
        </div>

        <!-- Info sobre tempo -->
        <div class="alert" style="background-color: #fef3c7; color: #92400e; border-left: 4px solid #f59e0b; margin: 20px 30px 0;">
            <i class="fas fa-clock"></i>
            <span>Este link expira em <?php echo $minutos_restantes; ?> minutos</span>
        </div>

        <!-- Mensagem de ERRO -->
        <?php if ($error): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i>
            <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>

        <!-- FORMULÁRIO -->
        <form class="login-form" method="POST" action="../../config/validacao/process_verify_identity.php">
            
            <p style="margin-bottom: 20px; color: var(--text-color); text-align: center;">
                Para confirmar sua identidade, digite o <strong>email</strong> cadastrado na sua conta:
            </p>

            <div class="form-group">
                <label for="email">Email Cadastrado</label>
                <div class="input-wrapper">
                    <input type="email" 
                           id="email" 
                           name="email" 
                           placeholder="Digite seu email" 
                           required 
                           autofocus>
                </div>
                <span style="font-size: 12px; color: var(--text-light); display: block; margin-top: 5px;">
                    Digite o email da sua conta.
                </span>
            </div>

            <button type="submit" class="login-btn">
                <i class="fas fa-check"></i> Verificar
            </button>
        </form>

        <!-- Voltar -->
        <div class="login-footer" style="border-top: none;">
            <a href="forgot-password.php" style="color: var(--primary-color); text-decoration: none; font-weight: 500;">
                <i class="fas fa-arrow-left"></i> Voltar
            </a>
        </div>

        <div class="login-footer">
            <p>Desenvolvido com <i class="fas fa-heart"></i> para um futuro sustentável</p>
        </div>
    </div>

    <script src="../../assets/js/login-script.js"></script>
</body>
</html>