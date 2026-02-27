<?php
// ============================================
// LOGOUT - ENCERRA SESSÃO E REMOVE COOKIE
// ============================================

// Inicia a sessão se ainda não foi iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Inclui configuração do banco se precisar limpar o token
require_once __DIR__ . '/../../config/config.php';

try {
    // Se existe um user_id, limpa o remember_token do banco
    if (isset($_SESSION['user_id'])) {
        $stmt = $conn->prepare("UPDATE usuarios SET remember_token = NULL WHERE id = :id");
        $stmt->execute(['id' => $_SESSION['user_id']]);
        
        error_log("Logout - Usuário: " . ($_SESSION['username'] ?? 'desconhecido') . " - Token removido");
    }
} catch(PDOException $e) {
    error_log("Erro ao limpar token no logout: " . $e->getMessage());
}

// Limpar todas as variáveis de sessão
$_SESSION = array();

// Destruir o cookie de sessão se existir
if (isset($_COOKIE[session_name()])) {
    setcookie(
        session_name(), 
        '', 
        time() - 3600, 
        '/',
        '',
        isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        true
    );
}

// Destruir cookie de remember_token se existir
if (isset($_COOKIE['remember_token'])) {
    setcookie(
        'remember_token', 
        '', 
        time() - 3600, 
        '/',
        '',
        isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        true
    );
}

// Destruir sessão
session_destroy();

// Redirecionar para login com mensagem
session_start(); // Reinicia para guardar a mensagem
$_SESSION['success'] = "Logout realizado com sucesso!";

header('Location: ../../login.php');
exit;
?>