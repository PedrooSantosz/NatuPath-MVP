<?php
// ============================================
// ARQUIVO QUE PROCESSA O LOGIN
// Este arquivo recebe os dados do formulário
// e faz a validação no banco de dados
// ============================================

// Inicia a sessão
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Inclui o arquivo de configuração (conexão com banco)
require_once __DIR__ . '/../config.php';

// ============================================
// 1. VERIFICA SE O FORMULÁRIO FOI ENVIADO
// ============================================
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Se alguém tentar acessar este arquivo diretamente, volta pro login
    header('Location: ../../login.php');
    exit;
}

// ============================================
// 2. PEGA OS DADOS DO FORMULÁRIO
// ============================================
$username = isset($_POST['username']) ? trim($_POST['username']) : '';
$password = isset($_POST['password']) ? trim($_POST['password']) : '';
$remember = isset($_POST['remember']) && $_POST['remember'] == '1';

// ============================================
// 3. VALIDAÇÃO BÁSICA
// ============================================
if (empty($username) || empty($password)) {
    $_SESSION['error'] = "Por favor, preencha todos os campos!";
    header('Location: ../../login.php');
    exit;
}

// ============================================
// 4. BUSCA O USUÁRIO NO BANCO DE DADOS
// ============================================
try {
    // Prepara a consulta SQL (isso previne SQL Injection)
    $stmt = $conn->prepare("
        SELECT id, username, password, nome, tipo, setor_id, email, status 
        FROM usuarios 
        WHERE username = :username 
        LIMIT 1
    ");
    
    // Executa a consulta
    $stmt->execute(['username' => $username]);
    
    // Pega o resultado
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // ============================================
    // 5. VERIFICA SE O USUÁRIO EXISTE E ESTÁ ATIVO
    // ============================================
    if (!$user) {
        $_SESSION['error'] = "Usuário ou senha incorretos!";
        header('Location: ../../login.php');
        exit;
    }

    // Verifica se o usuário está ativo
    if ($user['status'] !== 'ativo') {
        $_SESSION['error'] = "Sua conta está inativa. Entre em contato com o administrador.";
        header('Location: ../../login.php');
        exit;
    }

    // ============================================
    // 6. VERIFICA SE A SENHA ESTÁ CORRETA
    // ============================================
    if (!password_verify($password, $user['password'])) {
        $_SESSION['error'] = "Usuário ou senha incorretos!";
        header('Location: ../../login.php');
        exit;
    }

    // ============================================
    // ✅ LOGIN CORRETO!
    // ============================================
    
    // Salva as informações do usuário na sessão
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['nome'] = $user['nome'];
    $_SESSION['tipo'] = $user['tipo'];
    $_SESSION['setor_id'] = $user['setor_id'];
    $_SESSION['logged_in'] = true;
    $_SESSION['last_activity'] = time();
    $_SESSION['remember_login'] = false; // Marca como login manual

    // Atualiza a data do último login no banco
    $stmt = $conn->prepare("UPDATE usuarios SET ultimo_login = NOW() WHERE id = :id");
    $stmt->execute(['id' => $user['id']]);

    // ============================================
    // 7. LEMBRAR-ME (se o usuário marcou a opção)
    // ============================================
    if ($remember) {
        // Gera um token aleatório e seguro
        $token = bin2hex(random_bytes(32));
        
        // Configurações do cookie (30 dias)
        $expiry = time() + (30 * 24 * 60 * 60); // 30 dias
        
        // Detecta se está usando HTTPS
        $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
        
        // Cria o cookie com configurações de segurança
        setcookie(
            'remember_token',      // Nome do cookie
            $token,                // Valor (token)
            $expiry,              // Expiração (30 dias)
            '/',                  // Path (todo o site)
            '',                   // Domain (vazio = domínio atual)
            $secure,              // Secure (true se HTTPS)
            true                  // HttpOnly (não acessível via JavaScript)
        );
        
        // Salva o token no banco de dados
        $stmt = $conn->prepare("UPDATE usuarios SET remember_token = :token WHERE id = :id");
        $stmt->execute([
            'token' => $token, 
            'id' => $user['id']
        ]);
        
        // Marca na sessão que o lembrar-me está ativo
        $_SESSION['remember_active'] = true;
        
        // Log de sucesso
        error_log("Login com Remember Me ativado - Usuário: " . $user['username'] . " - Token criado");
        
    } else {
        // ============================================
        // 8. SE NÃO MARCOU "LEMBRAR-ME"
        // ============================================
        
        // Limpa qualquer token existente no banco
        $stmt = $conn->prepare("UPDATE usuarios SET remember_token = NULL WHERE id = :id");
        $stmt->execute(['id' => $user['id']]);
        
        // Remove cookie se existir
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
        
        $_SESSION['remember_active'] = false;
        
        error_log("Login sem Remember Me - Usuário: " . $user['username']);
    }

    // ============================================
    // 9. REDIRECIONA PARA O DASHBOARD
    // ============================================
    $_SESSION['success'] = "Login realizado com sucesso! Bem-vindo(a), " . $user['nome'] . "!";
    header('Location: ../../includes/partials/dashboard.php');
    exit;

} catch(PDOException $e) {
    // ============================================
    // ❌ ERRO NO BANCO DE DADOS
    // ============================================
    
    // Salva o erro num arquivo de log (para você ver depois)
    error_log("Erro no login - Usuário: " . $username . " - Erro: " . $e->getMessage());
    
    // Mostra mensagem genérica pro usuário
    $_SESSION['error'] = "Erro ao processar login. Tente novamente em instantes.";
    header('Location: ../../login.php');
    exit;
}
?>