<?php
// ============================================
// RECUPERAR SENHA - ETAPA 1
// Usuário informa o username
// ============================================
session_start();

// Se já estiver logado, redireciona
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header('Location: dashboard.php');
    exit;
}

// Pega mensagens
$error = isset($_SESSION['error']) ? $_SESSION['error'] : '';
$success = isset($_SESSION['success']) ? $_SESSION['success'] : '';

// Limpa mensagens
unset($_SESSION['error']);
unset($_SESSION['success']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Senha - NatuPath</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/login.css">
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <i class="fas fa-key"></i>
            <h1>Recuperar Senha</h1>
            <p>Digite seu usuário para continuar</p>
        </div>

        <!-- Mensagem de ERRO -->
        <?php if ($error): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i>
            <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>

        <!-- Mensagem de SUCESSO -->
        <?php if ($success): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <?php echo htmlspecialchars($success); ?>
        </div>
        <?php endif; ?>

        <!-- FORMULÁRIO -->
        <form class="login-form" method="POST" action="../../config/validacao/process_forgot_password.php">
            
            <div class="form-group">
                <label for="username">Nome de Usuário</label>
                <div class="input-wrapper">
                    <input type="text" 
                           id="username" 
                           name="username" 
                           placeholder="Digite seu usuário" 
                           required 
                           autofocus>
                    <i class="fas fa-user"></i>
                </div>
            </div>

            <button type="submit" class="login-btn">
                <i class="fas fa-arrow-right"></i> Continuar
            </button>
        </form>

        <!-- Voltar ao login -->
        <div class="login-footer" style="border-top: none;">
            <a href="login.php" style="color: var(--primary-color); text-decoration: none; font-weight: 500;">
                <i class="fas fa-arrow-left"></i> Voltar ao login
            </a>
        </div>

        <div class="login-footer">
            <p>Desenvolvido com <i class="fas fa-heart"></i> para um futuro sustentável</p>
        </div>
    </div>

    <script src="../../assets/js/login-script.js"></script>
</body>
</html>