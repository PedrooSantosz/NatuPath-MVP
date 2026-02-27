<?php
// ============================================
// PROCESSA ETAPA 1 - RECUPERAÇÃO DE SENHA
// Valida o username e cria token temporário
// ============================================

session_start();
require_once __DIR__ . '/../config.php';

// Verifica se foi POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../includes/partials/forgot-password.php');
    exit;
}

// Pega o username
$username = isset($_POST['username']) ? trim($_POST['username']) : '';

// Validação básica
if (empty($username)) {
    $_SESSION['error'] = "Por favor, informe seu usuário!";
    header('Location: ../../includes/partials/forgot-password.php');
    exit;
}

try {
    // Busca o usuário no banco
    $stmt = $conn->prepare("
        SELECT id, username, nome, email 
        FROM usuarios 
        WHERE username = :username AND status = 'ativo'
    ");
    
    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch();

    if (!$user) {
        // Usuário não encontrado
        $_SESSION['error'] = "Usuário não encontrado!";
        header('Location: ../../includes/partials/forgot-password.php');
        exit;
    }

    // Verifica se tem email cadastrado
    if (empty($user['email'])) {
        $_SESSION['error'] = "Este usuário não possui email cadastrado. Entre em contato com o administrador.";
        header('Location: ../../includes/partials/forgot-password.php');
        exit;
    }

    // Gera um token temporário (válido por 30 minutos)
    $reset_token = bin2hex(random_bytes(32));
    $expira = date('Y-m-d H:i:s', strtotime('+30 minutes'));

    // Salva o token no banco
    $stmt = $conn->prepare("
        UPDATE usuarios 
        SET remember_token = :token 
        WHERE id = :id
    ");
    
    $stmt->execute([
        'token' => $reset_token . '|' . $expira, // Token + data de expiração
        'id' => $user['id']
    ]);

    // Salva informações na sessão
    $_SESSION['reset_token'] = $reset_token;
    $_SESSION['reset_user_id'] = $user['id'];
    $_SESSION['reset_expires'] = $expira;

    // Redireciona para a etapa 2 (verificar identidade)
    header('Location: ../../includes/partials/verify-identity.php');
    exit;

} catch(PDOException $e) {
    error_log("Erro na recuperação de senha: " . $e->getMessage());
    $_SESSION['error'] = "Erro ao processar solicitação. Tente novamente.";
    header('Location: ../../includes/partials/forgot-password.php');
    exit;
}
?>