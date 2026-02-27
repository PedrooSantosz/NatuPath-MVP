<?php
// ============================================
// PROCESSA CRUD DE USUÁRIOS
// ✅ CORRIGIDO: Sincroniza gestor_id na tabela setores
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

// Verifica se é Super Admin OU Gestor
$userType = $_SESSION['tipo'] ?? '';
$isGestor = ($userType === 'gestor');
$isSuperAdmin = ($userType === 'super_admin');

if (!$isSuperAdmin && !$isGestor) {
    $_SESSION['error'] = "Acesso negado! Apenas Super Admins e Gestores podem gerenciar usuários.";
    header('Location: ../../includes/partials/dashboard.php');
    exit;
}

// Pega o setor do gestor (se aplicável)
$userSetorId = null;
if ($isGestor) {
    $stmt_setor = $conn->prepare("SELECT setor_id FROM usuarios WHERE id = :id");
    $stmt_setor->execute(['id' => $_SESSION['user_id']]);
    $userData = $stmt_setor->fetch();
    $userSetorId = $userData['setor_id'] ?? null;
}

// Verifica método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = "Método de requisição inválido!";
    header('Location: ../../includes/partials/usuarios.php');
    exit;
}

$action = $_POST['action'] ?? '';

// ============================================
// AÇÃO: CRIAR NOVO USUÁRIO
// ============================================
if ($action === 'create') {
    $nome = trim($_POST['nome'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $tipo = $_POST['tipo'] ?? 'usuario';
    $setor_id = !empty($_POST['setor_id']) ? intval($_POST['setor_id']) : null;
    $status = $_POST['status'] ?? 'ativo';
    
    // Validações específicas para gestor
    if ($isGestor) {
        // Gestor só pode criar usuários comuns
        if ($tipo !== 'usuario') {
            $_SESSION['error'] = "Gestores só podem criar usuários comuns, não gestores ou administradores!";
            header('Location: ../../includes/partials/usuarios.php');
            exit;
        }
        
        // Gestor só pode criar usuários do seu setor
        if ($setor_id !== $userSetorId) {
            $_SESSION['error'] = "Você só pode criar usuários para o seu setor!";
            header('Location: ../../includes/partials/usuarios.php');
            exit;
        }
    }
    
    // Validações gerais
    if (empty($nome) || empty($username) || empty($password)) {
        $_SESSION['error'] = "Nome, username e senha são obrigatórios!";
        header('Location: ../../includes/partials/usuarios.php');
        exit;
    }
    
    if (strlen($nome) < 3) {
        $_SESSION['error'] = "O nome deve ter pelo menos 3 caracteres!";
        header('Location: ../../includes/partials/usuarios.php');
        exit;
    }
    
    if (strlen($username) < 3) {
        $_SESSION['error'] = "O username deve ter pelo menos 3 caracteres!";
        header('Location: ../../includes/partials/usuarios.php');
        exit;
    }
    
    if (strlen($password) < 6) {
        $_SESSION['error'] = "A senha deve ter pelo menos 6 caracteres!";
        header('Location: ../../includes/partials/usuarios.php');
        exit;
    }
    
    // Valida username (apenas letras, números, ponto, hífen e underscore)
    if (!preg_match('/^[a-zA-Z0-9._-]+$/', $username)) {
        $_SESSION['error'] = "Username inválido! Use apenas letras, números, ponto, hífen e underscore.";
        header('Location: ../../includes/partials/usuarios.php');
        exit;
    }
    
    // Valida email se fornecido
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "Email inválido!";
        header('Location: ../../includes/partials/usuarios.php');
        exit;
    }
    
    // Valida tipo (Super Admin pode criar qualquer tipo, Gestor só 'usuario')
    $tipos_permitidos = $isSuperAdmin ? ['super_admin', 'gestor', 'usuario'] : ['usuario'];
    if (!in_array($tipo, $tipos_permitidos)) {
        $_SESSION['error'] = "Tipo de usuário inválido!";
        header('Location: ../../includes/partials/usuarios.php');
        exit;
    }
    
    try {
        // Verifica se username já existe
        $stmt = $conn->prepare("SELECT id FROM usuarios WHERE username = :username");
        $stmt->execute(['username' => $username]);
        
        if ($stmt->fetch()) {
            $_SESSION['error'] = "Este username já está em uso!";
            header('Location: ../../includes/partials/usuarios.php');
            exit;
        }
        
        // Verifica se email já existe (se fornecido)
        if (!empty($email)) {
            $stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = :email");
            $stmt->execute(['email' => $email]);
            
            if ($stmt->fetch()) {
                $_SESSION['error'] = "Este email já está em uso!";
                header('Location: ../../includes/partials/usuarios.php');
                exit;
            }
        }
        
        // Hash da senha
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        // Insere usuário
        $stmt = $conn->prepare("
            INSERT INTO usuarios (nome, username, email, password, tipo, setor_id, status) 
            VALUES (:nome, :username, :email, :password, :tipo, :setor_id, :status)
        ");
        
        $stmt->execute([
            'nome' => $nome,
            'username' => $username,
            'email' => $email ?: null,
            'password' => $password_hash,
            'tipo' => $tipo,
            'setor_id' => $setor_id,
            'status' => $status
        ]);
        
        $novo_usuario_id = $conn->lastInsertId();
        
        // ✅ CORREÇÃO: Se o usuário é GESTOR e tem setor, atualiza o setor para vincular este gestor
        if ($tipo === 'gestor' && $setor_id) {
            $stmt = $conn->prepare("UPDATE setores SET gestor_id = :gestor_id WHERE id = :setor_id");
            $stmt->execute([
                'gestor_id' => $novo_usuario_id,
                'setor_id' => $setor_id
            ]);
        }
        
        $mensagem = $isGestor ? "Colaborador '{$nome}' criado com sucesso!" : "Usuário '{$nome}' criado com sucesso!";
        $_SESSION['success'] = $mensagem;
        header('Location: ../../includes/partials/usuarios.php');
        exit;
        
    } catch(PDOException $e) {
        error_log("Erro ao criar usuário: " . $e->getMessage());
        $_SESSION['error'] = "Erro ao criar usuário. Tente novamente.";
        header('Location: ../../includes/partials/usuarios.php');
        exit;
    }
}

// ============================================
// AÇÃO: EDITAR USUÁRIO
// ============================================
else if ($action === 'edit') {
    $usuario_id = intval($_POST['usuario_id'] ?? 0);
    $nome = trim($_POST['nome'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $tipo = $_POST['tipo'] ?? 'usuario';
    $setor_id = !empty($_POST['setor_id']) ? intval($_POST['setor_id']) : null;
    $status = $_POST['status'] ?? 'ativo';
    
    // Validações
    if ($usuario_id <= 0) {
        $_SESSION['error'] = "ID do usuário inválido!";
        header('Location: ../../includes/partials/usuarios.php');
        exit;
    }
    
    // Busca o usuário atual
    $stmt = $conn->prepare("SELECT * FROM usuarios WHERE id = :id");
    $stmt->execute(['id' => $usuario_id]);
    $usuarioAtual = $stmt->fetch();
    
    if (!$usuarioAtual) {
        $_SESSION['error'] = "Usuário não encontrado!";
        header('Location: ../../includes/partials/usuarios.php');
        exit;
    }
    
    // Guarda dados antigos para comparação
    $tipo_antigo = $usuarioAtual['tipo'];
    $setor_antigo = $usuarioAtual['setor_id'];
    
    // Validações específicas para gestor
    if ($isGestor) {
        // Gestor só pode editar usuários do seu setor
        if ($usuarioAtual['setor_id'] !== $userSetorId) {
            $_SESSION['error'] = "Você só pode editar usuários do seu setor!";
            header('Location: ../../includes/partials/usuarios.php');
            exit;
        }
        
        // Gestor não pode editar gestores ou admins
        if ($usuarioAtual['tipo'] !== 'usuario') {
            $_SESSION['error'] = "Você não pode editar gestores ou administradores!";
            header('Location: ../../includes/partials/usuarios.php');
            exit;
        }
        
        // Gestor não pode mudar o tipo do usuário
        if ($tipo !== 'usuario') {
            $_SESSION['error'] = "Você não pode alterar o tipo do usuário!";
            header('Location: ../../includes/partials/usuarios.php');
            exit;
        }
        
        // Gestor não pode mudar o setor do usuário
        if ($setor_id !== $userSetorId) {
            $_SESSION['error'] = "Você não pode transferir usuários para outro setor!";
            header('Location: ../../includes/partials/usuarios.php');
            exit;
        }
    }
    
    if (empty($nome) || empty($username)) {
        $_SESSION['error'] = "Nome e username são obrigatórios!";
        header('Location: ../../includes/partials/usuarios.php');
        exit;
    }
    
    if (strlen($nome) < 3) {
        $_SESSION['error'] = "O nome deve ter pelo menos 3 caracteres!";
        header('Location: ../../includes/partials/usuarios.php');
        exit;
    }
    
    if (strlen($username) < 3) {
        $_SESSION['error'] = "O username deve ter pelo menos 3 caracteres!";
        header('Location: ../../includes/partials/usuarios.php');
        exit;
    }
    
    // Se senha foi fornecida, valida
    if (!empty($password) && strlen($password) < 6) {
        $_SESSION['error'] = "A senha deve ter pelo menos 6 caracteres!";
        header('Location: ../../includes/partials/usuarios.php');
        exit;
    }
    
    // Valida username
    if (!preg_match('/^[a-zA-Z0-9._-]+$/', $username)) {
        $_SESSION['error'] = "Username inválido! Use apenas letras, números, ponto, hífen e underscore.";
        header('Location: ../../includes/partials/usuarios.php');
        exit;
    }
    
    // Valida email se fornecido
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "Email inválido!";
        header('Location: ../../includes/partials/usuarios.php');
        exit;
    }
    
    // Valida tipo (Super Admin pode alterar qualquer tipo, Gestor não)
    $tipos_permitidos = $isSuperAdmin ? ['super_admin', 'gestor', 'usuario'] : ['usuario'];
    if (!in_array($tipo, $tipos_permitidos)) {
        $_SESSION['error'] = "Tipo de usuário inválido!";
        header('Location: ../../includes/partials/usuarios.php');
        exit;
    }
    
    try {
        // Verifica se username já existe em outro usuário
        $stmt = $conn->prepare("SELECT id FROM usuarios WHERE username = :username AND id != :usuario_id");
        $stmt->execute(['username' => $username, 'usuario_id' => $usuario_id]);
        
        if ($stmt->fetch()) {
            $_SESSION['error'] = "Este username já está em uso por outro usuário!";
            header('Location: ../../includes/partials/usuarios.php');
            exit;
        }
        
        // Verifica se email já existe em outro usuário (se fornecido)
        if (!empty($email)) {
            $stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = :email AND id != :usuario_id");
            $stmt->execute(['email' => $email, 'usuario_id' => $usuario_id]);
            
            if ($stmt->fetch()) {
                $_SESSION['error'] = "Este email já está em uso por outro usuário!";
                header('Location: ../../includes/partials/usuarios.php');
                exit;
            }
        }
        
        // Monta query de atualização
        if (!empty($password)) {
            // Se senha foi fornecida, atualiza também
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $conn->prepare("
                UPDATE usuarios 
                SET nome = :nome, 
                    username = :username, 
                    email = :email, 
                    password = :password,
                    tipo = :tipo, 
                    setor_id = :setor_id, 
                    status = :status
                WHERE id = :id
            ");
            
            $stmt->execute([
                'nome' => $nome,
                'username' => $username,
                'email' => $email ?: null,
                'password' => $password_hash,
                'tipo' => $tipo,
                'setor_id' => $setor_id,
                'status' => $status,
                'id' => $usuario_id
            ]);
        } else {
            // Não atualiza senha
            $stmt = $conn->prepare("
                UPDATE usuarios 
                SET nome = :nome, 
                    username = :username, 
                    email = :email, 
                    tipo = :tipo, 
                    setor_id = :setor_id, 
                    status = :status
                WHERE id = :id
            ");
            
            $stmt->execute([
                'nome' => $nome,
                'username' => $username,
                'email' => $email ?: null,
                'tipo' => $tipo,
                'setor_id' => $setor_id,
                'status' => $status,
                'id' => $usuario_id
            ]);
        }
        
        // ✅ CORREÇÃO: Sincroniza gestor_id na tabela setores
        
        // Caso 1: Usuário ERA gestor e foi rebaixado para usuário comum
        // Remove ele como gestor do setor antigo
        if ($tipo_antigo === 'gestor' && $tipo !== 'gestor' && $setor_antigo) {
            $stmt = $conn->prepare("UPDATE setores SET gestor_id = NULL WHERE gestor_id = :usuario_id");
            $stmt->execute(['usuario_id' => $usuario_id]);
        }
        
        // Caso 2: Usuário ERA gestor e mudou de setor
        // Remove do setor antigo e adiciona ao novo
        if ($tipo_antigo === 'gestor' && $tipo === 'gestor' && $setor_antigo != $setor_id) {
            // Remove do setor antigo
            if ($setor_antigo) {
                $stmt = $conn->prepare("UPDATE setores SET gestor_id = NULL WHERE id = :setor_id AND gestor_id = :usuario_id");
                $stmt->execute(['setor_id' => $setor_antigo, 'usuario_id' => $usuario_id]);
            }
            // Adiciona ao novo setor
            if ($setor_id) {
                $stmt = $conn->prepare("UPDATE setores SET gestor_id = :usuario_id WHERE id = :setor_id");
                $stmt->execute(['usuario_id' => $usuario_id, 'setor_id' => $setor_id]);
            }
        }
        
        // Caso 3: Usuário foi PROMOVIDO a gestor
        // Vincula ele ao setor como gestor
        if ($tipo_antigo !== 'gestor' && $tipo === 'gestor' && $setor_id) {
            $stmt = $conn->prepare("UPDATE setores SET gestor_id = :usuario_id WHERE id = :setor_id");
            $stmt->execute(['usuario_id' => $usuario_id, 'setor_id' => $setor_id]);
        }
        
        // Caso 4: Usuário JÁ ERA gestor e continua sendo (mesmo setor)
        // Garante que o vínculo está correto
        if ($tipo === 'gestor' && $setor_id) {
            $stmt = $conn->prepare("UPDATE setores SET gestor_id = :usuario_id WHERE id = :setor_id");
            $stmt->execute(['usuario_id' => $usuario_id, 'setor_id' => $setor_id]);
        }
        
        $mensagem = $isGestor ? "Colaborador '{$nome}' atualizado com sucesso!" : "Usuário '{$nome}' atualizado com sucesso!";
        $_SESSION['success'] = $mensagem;
        header('Location: ../../includes/partials/usuarios.php');
        exit;
        
    } catch(PDOException $e) {
        error_log("Erro ao editar usuário: " . $e->getMessage());
        $_SESSION['error'] = "Erro ao editar usuário. Tente novamente.";
        header('Location: ../../includes/partials/usuarios.php');
        exit;
    }
}

// ============================================
// AÇÃO: RESETAR SENHA
// ============================================
else if ($action === 'reset_senha') {
    $usuario_id = intval($_POST['usuario_id'] ?? 0);
    $nova_senha = $_POST['nova_senha'] ?? '';
    $confirma_senha = $_POST['confirma_senha'] ?? '';
    
    // Validações
    if ($usuario_id <= 0) {
        $_SESSION['error'] = "ID do usuário inválido!";
        header('Location: ../../includes/partials/usuarios.php');
        exit;
    }
    
    // Busca o usuário
    $stmt = $conn->prepare("SELECT * FROM usuarios WHERE id = :id");
    $stmt->execute(['id' => $usuario_id]);
    $usuario = $stmt->fetch();
    
    if (!$usuario) {
        $_SESSION['error'] = "Usuário não encontrado!";
        header('Location: ../../includes/partials/usuarios.php');
        exit;
    }
    
    // Validações específicas para gestor
    if ($isGestor) {
        // Gestor só pode resetar senha de usuários do seu setor
        if ($usuario['setor_id'] !== $userSetorId) {
            $_SESSION['error'] = "Você só pode resetar senhas de usuários do seu setor!";
            header('Location: ../../includes/partials/usuarios.php');
            exit;
        }
        
        // Gestor não pode resetar senha de gestores ou admins
        if ($usuario['tipo'] !== 'usuario') {
            $_SESSION['error'] = "Você não pode resetar senhas de gestores ou administradores!";
            header('Location: ../../includes/partials/usuarios.php');
            exit;
        }
    }
    
    if (empty($nova_senha)) {
        $_SESSION['error'] = "A nova senha é obrigatória!";
        header('Location: ../../includes/partials/usuarios.php');
        exit;
    }
    
    if (strlen($nova_senha) < 6) {
        $_SESSION['error'] = "A senha deve ter pelo menos 6 caracteres!";
        header('Location: ../../includes/partials/usuarios.php');
        exit;
    }
    
    if ($nova_senha !== $confirma_senha) {
        $_SESSION['error'] = "As senhas não conferem!";
        header('Location: ../../includes/partials/usuarios.php');
        exit;
    }
    
    try {
        // Hash da nova senha
        $password_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
        
        // Atualiza senha
        $stmt = $conn->prepare("UPDATE usuarios SET password = :password WHERE id = :id");
        $stmt->execute([
            'password' => $password_hash,
            'id' => $usuario_id
        ]);
        
        $mensagem = $isGestor 
            ? "Senha do colaborador '{$usuario['nome']}' alterada com sucesso!" 
            : "Senha do usuário '{$usuario['nome']}' alterada com sucesso!";
        $_SESSION['success'] = $mensagem;
        header('Location: ../../includes/partials/usuarios.php');
        exit;
        
    } catch(PDOException $e) {
        error_log("Erro ao resetar senha: " . $e->getMessage());
        $_SESSION['error'] = "Erro ao resetar senha. Tente novamente.";
        header('Location: ../../includes/partials/usuarios.php');
        exit;
    }
}

// ============================================
// AÇÃO: DELETAR USUÁRIO (APENAS SUPER ADMIN)
// ============================================
else if ($action === 'delete') {
    // Apenas Super Admin pode deletar usuários
    if (!$isSuperAdmin) {
        $_SESSION['error'] = "Apenas Super Admins podem deletar usuários!";
        header('Location: ../../includes/partials/usuarios.php');
        exit;
    }
    
    $usuario_id = intval($_POST['usuario_id'] ?? 0);
    
    if ($usuario_id <= 0) {
        $_SESSION['error'] = "ID do usuário inválido!";
        header('Location: ../../includes/partials/usuarios.php');
        exit;
    }
    
    // Não permite deletar a própria conta
    if ($usuario_id == $_SESSION['user_id']) {
        $_SESSION['error'] = "Você não pode deletar sua própria conta!";
        header('Location: ../../includes/partials/usuarios.php');
        exit;
    }
    
    try {
        // Busca dados do usuário
        $stmt = $conn->prepare("SELECT nome, tipo, setor_id FROM usuarios WHERE id = :id");
        $stmt->execute(['id' => $usuario_id]);
        $usuario = $stmt->fetch();
        
        if (!$usuario) {
            $_SESSION['error'] = "Usuário não encontrado!";
            header('Location: ../../includes/partials/usuarios.php');
            exit;
        }
        
        // ✅ CORREÇÃO: Se era gestor, remove o vínculo do setor
        if ($usuario['tipo'] === 'gestor' && $usuario['setor_id']) {
            $stmt = $conn->prepare("UPDATE setores SET gestor_id = NULL WHERE gestor_id = :usuario_id");
            $stmt->execute(['usuario_id' => $usuario_id]);
        }
        
        // Deleta usuário (registros são mantidos por CASCADE)
        $stmt = $conn->prepare("DELETE FROM usuarios WHERE id = :id");
        $stmt->execute(['id' => $usuario_id]);
        
        $_SESSION['success'] = "Usuário '{$usuario['nome']}' deletado com sucesso!";
        header('Location: ../../includes/partials/usuarios.php');
        exit;
        
    } catch(PDOException $e) {
        error_log("Erro ao deletar usuário: " . $e->getMessage());
        $_SESSION['error'] = "Erro ao deletar usuário. Tente novamente.";
        header('Location: ../../includes/partials/usuarios.php');
        exit;
    }
}

// ============================================
// AÇÃO INVÁLIDA
// ============================================
else {
    $_SESSION['error'] = "Ação inválida!";
    header('Location: ../../includes/partials/usuarios.php');
    exit;
}
?>