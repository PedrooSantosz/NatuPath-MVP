<?php
// ============================================
// PROCESSA CRUD DE SETORES
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

// Verifica se é Super Admin
if (!isSuperAdmin()) {
    $_SESSION['error'] = "Acesso negado! Apenas Super Admins podem gerenciar setores.";
    header('Location: ../../includes/partials/dashboard.php');
    exit;
}

// ============================================
// GET: RETORNA COLABORADORES DE UM SETOR (JSON)
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_colaboradores') {
    header('Content-Type: application/json');
    
    $setor_id = intval($_GET['setor_id'] ?? 0);
    
    if ($setor_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID do setor inválido']);
        exit;
    }
    
    try {
        $stmt = $conn->prepare("
            SELECT id, nome, email, tipo, status 
            FROM usuarios 
            WHERE setor_id = :setor_id 
            ORDER BY 
                CASE tipo 
                    WHEN 'gestor' THEN 1 
                    WHEN 'usuario' THEN 2 
                    ELSE 3 
                END,
                nome
        ");
        $stmt->execute(['setor_id' => $setor_id]);
        $colaboradores = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'colaboradores' => $colaboradores
        ]);
        exit;
        
    } catch(PDOException $e) {
        error_log("Erro ao buscar colaboradores: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Erro ao buscar colaboradores']);
        exit;
    }
}

// ============================================
// POST: CRIAR, EDITAR OU DELETAR SETOR
// ============================================
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = "Método de requisição inválido!";
    header('Location: ../../includes/partials/setores.php');
    exit;
}

$action = $_POST['action'] ?? '';

// ============================================
// AÇÃO: CRIAR NOVO SETOR
// ============================================
if ($action === 'create') {
    $nome = trim($_POST['nome'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    $gestor_id = !empty($_POST['gestor_id']) ? intval($_POST['gestor_id']) : null;
    $status = $_POST['status'] ?? 'ativo';
    
    // Validações
    if (empty($nome)) {
        $_SESSION['error'] = "O nome do setor é obrigatório!";
        header('Location: ../../includes/partials/setores.php');
        exit;
    }
    
    if (strlen($nome) < 3) {
        $_SESSION['error'] = "O nome do setor deve ter pelo menos 3 caracteres!";
        header('Location: ../../includes/partials/setores.php');
        exit;
    }
    
    // Verifica se já existe setor com esse nome
    try {
        $stmt = $conn->prepare("SELECT id FROM setores WHERE nome = :nome");
        $stmt->execute(['nome' => $nome]);
        
        if ($stmt->fetch()) {
            $_SESSION['error'] = "Já existe um setor com este nome!";
            header('Location: ../../includes/partials/setores.php');
            exit;
        }
    } catch(PDOException $e) {
        error_log("Erro ao verificar setor: " . $e->getMessage());
    }
    
    // Insere o setor
    try {
        $stmt = $conn->prepare("
            INSERT INTO setores (nome, descricao, gestor_id, status) 
            VALUES (:nome, :descricao, :gestor_id, :status)
        ");
        
        $stmt->execute([
            'nome' => $nome,
            'descricao' => $descricao,
            'gestor_id' => $gestor_id,
            'status' => $status
        ]);
        
        $setor_id = $conn->lastInsertId();
        
        // Se foi definido um gestor, atualiza o tipo do usuário para 'gestor'
        if ($gestor_id) {
            $stmt = $conn->prepare("
                UPDATE usuarios 
                SET tipo = 'gestor', setor_id = :setor_id 
                WHERE id = :gestor_id
            ");
            $stmt->execute([
                'setor_id' => $setor_id,
                'gestor_id' => $gestor_id
            ]);
        }
        
        $_SESSION['success'] = "Setor '{$nome}' criado com sucesso!";
        header('Location: ../../includes/partials/setores.php');
        exit;
        
    } catch(PDOException $e) {
        error_log("Erro ao criar setor: " . $e->getMessage());
        $_SESSION['error'] = "Erro ao criar setor. Tente novamente.";
        header('Location: ../../includes/partials/setores.php');
        exit;
    }
}

// ============================================
// AÇÃO: EDITAR SETOR
// ============================================
else if ($action === 'edit') {
    $setor_id = intval($_POST['setor_id'] ?? 0);
    $nome = trim($_POST['nome'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    $gestor_id = !empty($_POST['gestor_id']) ? intval($_POST['gestor_id']) : null;
    $status = $_POST['status'] ?? 'ativo';
    
    // Validações
    if ($setor_id <= 0) {
        $_SESSION['error'] = "ID do setor inválido!";
        header('Location: ../../includes/partials/setores.php');
        exit;
    }
    
    if (empty($nome)) {
        $_SESSION['error'] = "O nome do setor é obrigatório!";
        header('Location: ../../includes/partials/setores.php');
        exit;
    }
    
    if (strlen($nome) < 3) {
        $_SESSION['error'] = "O nome do setor deve ter pelo menos 3 caracteres!";
        header('Location: ../../includes/partials/setores.php');
        exit;
    }
    
    // Verifica se já existe outro setor com esse nome
    try {
        $stmt = $conn->prepare("SELECT id FROM setores WHERE nome = :nome AND id != :setor_id");
        $stmt->execute(['nome' => $nome, 'setor_id' => $setor_id]);
        
        if ($stmt->fetch()) {
            $_SESSION['error'] = "Já existe outro setor com este nome!";
            header('Location: ../../includes/partials/setores.php');
            exit;
        }
    } catch(PDOException $e) {
        error_log("Erro ao verificar setor: " . $e->getMessage());
    }
    
    try {
        // Busca o gestor antigo
        $stmt = $conn->prepare("SELECT gestor_id FROM setores WHERE id = :id");
        $stmt->execute(['id' => $setor_id]);
        $setor_antigo = $stmt->fetch();
        $gestor_antigo_id = $setor_antigo['gestor_id'] ?? null;
        
        // Atualiza o setor
        $stmt = $conn->prepare("
            UPDATE setores 
            SET nome = :nome, 
                descricao = :descricao, 
                gestor_id = :gestor_id, 
                status = :status,
                atualizado_em = NOW()
            WHERE id = :id
        ");
        
        $stmt->execute([
            'nome' => $nome,
            'descricao' => $descricao,
            'gestor_id' => $gestor_id,
            'status' => $status,
            'id' => $setor_id
        ]);
        
        // Se o gestor mudou, atualiza os usuários
        if ($gestor_antigo_id != $gestor_id) {
            // Remove o tipo 'gestor' do usuário antigo (se não for gestor de outro setor)
            if ($gestor_antigo_id) {
                $stmt = $conn->prepare("
                    SELECT COUNT(*) as count 
                    FROM setores 
                    WHERE gestor_id = :gestor_id AND id != :setor_id
                ");
                $stmt->execute([
                    'gestor_id' => $gestor_antigo_id,
                    'setor_id' => $setor_id
                ]);
                $count = $stmt->fetch()['count'];
                
                // Se não for gestor de nenhum outro setor, volta a ser usuário comum
                if ($count == 0) {
                    $stmt = $conn->prepare("UPDATE usuarios SET tipo = 'usuario' WHERE id = :id");
                    $stmt->execute(['id' => $gestor_antigo_id]);
                }
            }
            
            // Define o novo gestor
            if ($gestor_id) {
                $stmt = $conn->prepare("
                    UPDATE usuarios 
                    SET tipo = 'gestor', setor_id = :setor_id 
                    WHERE id = :gestor_id
                ");
                $stmt->execute([
                    'setor_id' => $setor_id,
                    'gestor_id' => $gestor_id
                ]);
            }
        }
        
        $_SESSION['success'] = "Setor '{$nome}' atualizado com sucesso!";
        header('Location: ../../includes/partials/setores.php');
        exit;
        
    } catch(PDOException $e) {
        error_log("Erro ao editar setor: " . $e->getMessage());
        $_SESSION['error'] = "Erro ao editar setor. Tente novamente.";
        header('Location: ../../includes/partials/setores.php');
        exit;
    }
}

// ============================================
// AÇÃO: DELETAR SETOR
// ============================================
else if ($action === 'delete') {
    $setor_id = intval($_POST['setor_id'] ?? 0);
    
    if ($setor_id <= 0) {
        $_SESSION['error'] = "ID do setor inválido!";
        header('Location: ../../includes/partials/setores.php');
        exit;
    }
    
    try {
        // Verifica se o setor tem colaboradores
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM usuarios WHERE setor_id = :setor_id");
        $stmt->execute(['setor_id' => $setor_id]);
        $total = $stmt->fetch()['total'];
        
        if ($total > 0) {
            $_SESSION['error'] = "Não é possível deletar um setor que possui colaboradores! Remova os colaboradores primeiro.";
            header('Location: ../../includes/partials/setores.php');
            exit;
        }
        
        // Verifica se o setor tem boas práticas
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM boas_praticas WHERE setor_id = :setor_id");
        $stmt->execute(['setor_id' => $setor_id]);
        $total_bp = $stmt->fetch()['total'];
        
        // Verifica se o setor tem não conformidades
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM nao_conformidades WHERE setor_id = :setor_id");
        $stmt->execute(['setor_id' => $setor_id]);
        $total_nc = $stmt->fetch()['total'];
        
        if ($total_bp > 0 || $total_nc > 0) {
            $_SESSION['error'] = "Não é possível deletar um setor que possui registros de boas práticas ou não conformidades!";
            header('Location: ../../includes/partials/setores.php');
            exit;
        }
        
        // Busca o nome do setor antes de deletar
        $stmt = $conn->prepare("SELECT nome FROM setores WHERE id = :id");
        $stmt->execute(['id' => $setor_id]);
        $setor = $stmt->fetch();
        $nome_setor = $setor['nome'] ?? 'Setor';
        
        // Deleta o setor
        $stmt = $conn->prepare("DELETE FROM setores WHERE id = :id");
        $stmt->execute(['id' => $setor_id]);
        
        $_SESSION['success'] = "Setor '{$nome_setor}' deletado com sucesso!";
        header('Location: ../../includes/partials/setores.php');
        exit;
        
    } catch(PDOException $e) {
        error_log("Erro ao deletar setor: " . $e->getMessage());
        $_SESSION['error'] = "Erro ao deletar setor. Tente novamente.";
        header('Location: ../../includes/partials/setores.php');
        exit;
    }
}

// ============================================
// AÇÃO INVÁLIDA
// ============================================
else {
    $_SESSION['error'] = "Ação inválida!";
    header('Location: ../../includes/partials/setores.php');
    exit;
}
?>