<?php
// ============================================
// VERIFICAÇÃO AUTOMÁTICA DO "LEMBRAR-ME"
// Este arquivo verifica se existe cookie e faz login automático
// ============================================

// Inicia sessão se ainda não foi iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Se já estiver logado, não precisa verificar
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    return;
}

// Verifica se existe o cookie de "lembrar-me"
if (isset($_COOKIE['remember_token']) && !empty($_COOKIE['remember_token'])) {
    
    // Inclui a conexão com o banco (ajuste o caminho conforme sua estrutura)
    require_once __DIR__ . '/../config.php';
    
    try {
        $cookie_token = $_COOKIE['remember_token'];
        
        // Busca o usuário com este token
        $stmt = $conn->prepare("
            SELECT id, username, nome, tipo, setor_id, email
            FROM usuarios 
            WHERE remember_token = :token 
            AND status = 'ativo'
            LIMIT 1
        ");
        
        $stmt->execute(['token' => $cookie_token]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            // ✅ TOKEN VÁLIDO! Faz login automático
            
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['nome'] = $user['nome'];
            $_SESSION['tipo'] = $user['tipo'];
            $_SESSION['setor_id'] = $user['setor_id'];
            $_SESSION['logged_in'] = true;
            $_SESSION['last_activity'] = time();
            $_SESSION['remember_login'] = true; // Marca que foi login automático
            
            // Atualiza o último login
            $stmt = $conn->prepare("UPDATE usuarios SET ultimo_login = NOW() WHERE id = :id");
            $stmt->execute(['id' => $user['id']]);
            
            // Registra no log
            error_log("Login automático (Lembrar-me): " . $user['username'] . " - ID: " . $user['id']);
            
            return true; // Indica que fez login automático
            
        } else {
            // Token inválido ou expirado - remove o cookie
            setcookie('remember_token', '', time() - 3600, '/', '', isset($_SERVER['HTTPS']), true);
            error_log("Token de remember_me inválido ou expirado");
        }
        
    } catch(PDOException $e) {
        error_log("Erro no check_remember: " . $e->getMessage());
        // Remove cookie com problema
        if (isset($_COOKIE['remember_token'])) {
            setcookie('remember_token', '', time() - 3600, '/', '', isset($_SERVER['HTTPS']), true);
        }
    }
}

return false; // Não fez login automático
?>