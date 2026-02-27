<?php
// ============================================
// PROCESSA ETAPA 3 - REDEFINIÇÃO DE SENHA
// Atualiza a senha no banco de dados
// ============================================

session_start();
require_once __DIR__ . '/../config.php';

// Verifica se foi POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../includes/partials/forgot-password.php');
    exit;
}

// Verifica se passou por todas as etapas
if (!isset($_SESSION['reset_token']) || !isset($_SESSION['reset_user_id']) || !isset($_SESSION['identity_verified'])) {
    $_SESSION['error'] = "Acesso inválido. Por favor, inicie o processo novamente.";
    header('Location: ../../includes/partials/forgot-password.php');
    exit;
}

// Verifica expiração
if (isset($_SESSION['reset_expires']) && strtotime($_SESSION['reset_expires']) < time()) {
    $_SESSION['error'] = "Tempo expirado! Por favor, solicite a recuperação novamente.";
    unset($_SESSION['reset_token']);
    unset($_SESSION['reset_user_id']);
    unset($_SESSION['reset_expires']);
    unset($_SESSION['identity_verified']);
    header('Location: ../../includes/partials/forgot-password.php');
    exit;
}

// Pega os dados do formulário
$nova_senha = isset($_POST['nova_senha']) ? trim($_POST['nova_senha']) : '';
$confirmar_senha = isset($_POST['confirmar_senha']) ? trim($_POST['confirmar_senha']) : '';

// Validações básicas
if (empty($nova_senha) || empty($confirmar_senha)) {
    $_SESSION['error'] = "Por favor, preencha todos os campos!";
    header('Location: ../../includes/partials/reset-password.php');
    exit;
}

// Verifica se as senhas coincidem
if ($nova_senha !== $confirmar_senha) {
    $_SESSION['error'] = "As senhas não coincidem!";
    header('Location: ../../includes/partials/reset-password.php');
    exit;
}

// Verifica o tamanho da senha
if (strlen($nova_senha) < 6) {
    $_SESSION['error'] = "A senha deve ter no mínimo 6 caracteres!";
    header('Location: ../../includes/partials/reset-password.php');
    exit;
}

try {
    // Criptografa a nova senha
    $senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);

    // Atualiza a senha no banco e limpa o token
    $stmt = $conn->prepare("
        UPDATE usuarios 
        SET password = :senha,
            remember_token = NULL
        WHERE id = :id
    ");
    
    $stmt->execute([
        'senha' => $senha_hash,
        'id' => $_SESSION['reset_user_id']
    ]);

    // Busca o username para o log
    $stmt = $conn->prepare("SELECT username FROM usuarios WHERE id = :id");
    $stmt->execute(['id' => $_SESSION['reset_user_id']]);
    $user = $stmt->fetch();

    // Registra no log
    if ($user) {
        error_log("Senha redefinida com sucesso para o usuário: " . $user['username']);
    }

    // Limpa todas as variáveis de sessão da recuperação
    unset($_SESSION['reset_token']);
    unset($_SESSION['reset_user_id']);
    unset($_SESSION['reset_expires']);
    unset($_SESSION['identity_verified']);

    // Mensagem de sucesso
    $_SESSION['success'] = "Senha redefinida com sucesso! Faça login com sua nova senha.";
    header('Location: ../../login.php');
    exit;

} catch(PDOException $e) {
    error_log("Erro ao redefinir senha: " . $e->getMessage());
    $_SESSION['error'] = "Erro ao processar redefinição. Tente novamente.";
    header('Location: ../../includes/partials/reset-password.php');
    exit;
}
?>