<?php
// ============================================
// PROCESSA CONFIGURAÇÕES DE PERFIL
// ============================================

session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';

// Verifica se está logado
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    $_SESSION['error'] = "Você precisa estar logado!";
    header('Location: ../../login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';

// ============================================
// AÇÃO: EDITAR PERFIL
// ============================================
if ($action === 'editar_perfil') {
    
    $nome = trim($_POST['nome'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    
    // Validações
    if (empty($nome) || empty($username)) {
        $_SESSION['error'] = "Nome e username são obrigatórios!";
        header('Location: ../../includes/partials/configuracoes.php');
        exit;
    }
    
    if (strlen($nome) < 3) {
        $_SESSION['error'] = "O nome deve ter pelo menos 3 caracteres!";
        header('Location: ../../includes/partials/configuracoes.php');
        exit;
    }
    
    if (strlen($username) < 3) {
        $_SESSION['error'] = "O username deve ter pelo menos 3 caracteres!";
        header('Location: ../../includes/partials/configuracoes.php');
        exit;
    }
    
    // Valida username (apenas letras, números, ponto, hífen e underscore)
    if (!preg_match('/^[a-zA-Z0-9._-]+$/', $username)) {
        $_SESSION['error'] = "Username inválido! Use apenas letras, números, ponto, hífen e underscore.";
        header('Location: ../../includes/partials/configuracoes.php');
        exit;
    }
    
    // Valida email se fornecido
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "Email inválido!";
        header('Location: ../../includes/partials/configuracoes.php');
        exit;
    }
    
    try {
        // Verifica se username já existe em outro usuário
        $stmt = $conn->prepare("SELECT id FROM usuarios WHERE username = :username AND id != :user_id");
        $stmt->execute(['username' => $username, 'user_id' => $user_id]);
        
        if ($stmt->fetch()) {
            $_SESSION['error'] = "Este username já está em uso por outro usuário!";
            header('Location: ../../includes/partials/configuracoes.php');
            exit;
        }
        
        // Verifica se email já existe em outro usuário (se fornecido)
        if (!empty($email)) {
            $stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = :email AND id != :user_id");
            $stmt->execute(['email' => $email, 'user_id' => $user_id]);
            
            if ($stmt->fetch()) {
                $_SESSION['error'] = "Este email já está em uso por outro usuário!";
                header('Location: ../../includes/partials/configuracoes.php');
                exit;
            }
        }
        
        // Atualiza perfil
        $stmt = $conn->prepare("
            UPDATE usuarios 
            SET nome = :nome, 
                username = :username, 
                email = :email
            WHERE id = :id
        ");
        
        $stmt->execute([
            'nome' => $nome,
            'username' => $username,
            'email' => $email ?: null,
            'id' => $user_id
        ]);
        
        // Atualiza sessão
        $_SESSION['nome'] = $nome;
        $_SESSION['username'] = $username;
        
        $_SESSION['success'] = "Perfil atualizado com sucesso!";
        header('Location: ../../includes/partials/configuracoes.php');
        exit;
        
    } catch(PDOException $e) {
        error_log("Erro ao editar perfil: " . $e->getMessage());
        $_SESSION['error'] = "Erro ao atualizar perfil. Tente novamente.";
        header('Location: ../../includes/partials/configuracoes.php');
        exit;
    }
}

// ============================================
// AÇÃO: ALTERAR SENHA
// ============================================
else if ($action === 'alterar_senha') {
    
    $senha_atual = $_POST['senha_atual'] ?? '';
    $nova_senha = $_POST['nova_senha'] ?? '';
    $confirma_senha = $_POST['confirma_senha'] ?? '';
    
    // Validações
    if (empty($senha_atual) || empty($nova_senha) || empty($confirma_senha)) {
        $_SESSION['error'] = "Todos os campos de senha são obrigatórios!";
        header('Location: ../../includes/partials/configuracoes.php');
        exit;
    }
    
    if (strlen($nova_senha) < 6) {
        $_SESSION['error'] = "A nova senha deve ter pelo menos 6 caracteres!";
        header('Location: ../../includes/partials/configuracoes.php');
        exit;
    }
    
    if ($nova_senha !== $confirma_senha) {
        $_SESSION['error'] = "As senhas não conferem!";
        header('Location: ../../includes/partials/configuracoes.php');
        exit;
    }
    
    if ($senha_atual === $nova_senha) {
        $_SESSION['error'] = "A nova senha deve ser diferente da senha atual!";
        header('Location: ../../includes/partials/configuracoes.php');
        exit;
    }
    
    try {
        // Busca senha atual do banco
        $stmt = $conn->prepare("SELECT password FROM usuarios WHERE id = :id");
        $stmt->execute(['id' => $user_id]);
        $usuario = $stmt->fetch();
        
        if (!$usuario) {
            $_SESSION['error'] = "Usuário não encontrado!";
            header('Location: ../../includes/partials/configuracoes.php');
            exit;
        }
        
        // Verifica se a senha atual está correta
        if (!password_verify($senha_atual, $usuario['password'])) {
            $_SESSION['error'] = "Senha atual incorreta!";
            header('Location: ../../includes/partials/configuracoes.php');
            exit;
        }
        
        // Hash da nova senha
        $nova_senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
        
        // Atualiza senha
        $stmt = $conn->prepare("UPDATE usuarios SET password = :password WHERE id = :id");
        $stmt->execute([
            'password' => $nova_senha_hash,
            'id' => $user_id
        ]);
        
        $_SESSION['success'] = "Senha alterada com sucesso!";
        header('Location: ../../includes/partials/configuracoes.php');
        exit;
        
    } catch(PDOException $e) {
        error_log("Erro ao alterar senha: " . $e->getMessage());
        $_SESSION['error'] = "Erro ao alterar senha. Tente novamente.";
        header('Location: ../../includes/partials/configuracoes.php');
        exit;
    }
}

// ============================================
// AÇÃO INVÁLIDA
// ============================================
else {
    $_SESSION['error'] = "Ação inválida!";
    header('Location: ../../includes/partials/configuracoes.php');
    exit;
}
?>