<?php
// ============================================
// PROCESSA ETAPA 2 - VERIFICAÇÃO DE IDENTIDADE
// Valida o email informado
// ============================================

session_start();
require_once __DIR__ . '/../config.php';

// Verifica se foi POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../includes/partials/forgot-password.php');
    exit;
}

// Verifica se tem token na sessão
if (!isset($_SESSION['reset_token']) || !isset($_SESSION['reset_user_id'])) {
    $_SESSION['error'] = "Sessão expirada. Por favor, inicie o processo novamente.";
    header('Location: ../../includes/partials/forgot-password.php');
    exit;
}

// Verifica expiração
if (isset($_SESSION['reset_expires']) && strtotime($_SESSION['reset_expires']) < time()) {
    $_SESSION['error'] = "Tempo expirado! Por favor, solicite a recuperação novamente.";
    unset($_SESSION['reset_token']);
    unset($_SESSION['reset_user_id']);
    unset($_SESSION['reset_expires']);
    header('Location: ../../includes/partials/forgot-password.php');
    exit;
}

// Pega o email do formulário
$email = isset($_POST['email']) ? trim($_POST['email']) : '';

// Validação básica
if (empty($email)) {
    $_SESSION['error'] = "Por favor, informe o email!";
    header('Location: ../../includes/partials/verify-identity.php');
    exit;
}

// Valida formato de email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error'] = "Email inválido!";
    header('Location: ../../includes/partials/verify-identity.php');
    exit;
}

try {
    // Busca o usuário e verifica se o email bate
    $stmt = $conn->prepare("
        SELECT id, email 
        FROM usuarios 
        WHERE id = :id AND status = 'ativo'
    ");
    
    $stmt->execute(['id' => $_SESSION['reset_user_id']]);
    $user = $stmt->fetch();

    if (!$user) {
        $_SESSION['error'] = "Erro ao processar solicitação.";
        header('Location: ../../includes/partials/forgot-password.php');
        exit;
    }

    // Compara emails (case-insensitive)
    if (strtolower($email) !== strtolower($user['email'])) {
        $_SESSION['error'] = "Email incorreto! Tente novamente.";
        header('Location: ../../includes/partials/verify-identity.php');
        exit;
    }

    // ✅ EMAIL CORRETO! Marca como verificado
    $_SESSION['identity_verified'] = true;

    // Redireciona para definir nova senha
    header('Location: ../../includes/partials/reset-password.php');
    exit;

} catch(PDOException $e) {
    error_log("Erro na verificação: " . $e->getMessage());
    $_SESSION['error'] = "Erro ao processar verificação. Tente novamente.";
    header('Location: ../../includes/partials/verify-identity.php');
    exit;
}
?>