<?php
// ============================================
// PROCESSA CRUD DE CATEGORIAS
// ============================================

session_start();
require_once(__DIR__ . '/../config.php');
require_once __DIR__ . '/../functions.php';

// Verifica se está logado
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    $_SESSION['error'] = "Você precisa estar logado!";
    header('Location: ../../login.php');
    exit;
}

// Verifica se é Super Admin ou Gestor
if (!isGestorOrAdmin()) {
    $_SESSION['error'] = "Acesso negado! Apenas Gestores e Super Admins podem gerenciar categorias.";
    header('Location: ../../includes/partials/dashboard.php');
    exit;
}

// Verifica método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = "Método de requisição inválido!";
    header('Location: ../../includes/partials/categorias.php');
    exit;
}

$action = $_POST['action'] ?? '';
$tipo_categoria = $_POST['tipo_categoria'] ?? ''; // 'boas_praticas' ou 'nao_conformidades'

// Valida tipo de categoria
if (!in_array($tipo_categoria, ['boas_praticas', 'nao_conformidades'])) {
    $_SESSION['error'] = "Tipo de categoria inválido!";
    header('Location: ../../includes/partials/categorias.php');
    exit;
}

// Define a tabela correta
$tabela = $tipo_categoria === 'boas_praticas' ? 'categorias_boas_praticas' : 'categorias_nao_conformidades';

// ============================================
// AÇÃO: CRIAR NOVA CATEGORIA
// ============================================
if ($action === 'create') {
    $nome = trim($_POST['nome'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    $icone = trim($_POST['icone'] ?? 'fa-tag');
    $cor = trim($_POST['cor'] ?? '#10b981');
    $status = $_POST['status'] ?? 'ativo';
    $criado_por = $_SESSION['user_id'];
    
    // Setor (apenas super admin pode escolher, gestor sempre usa o seu)
    $setor_id = null;
    if (isSuperAdmin() && !empty($_POST['setor_id'])) {
        $setor_id = intval($_POST['setor_id']);
    } else if (isGestor()) {
        // Busca o setor do gestor
        $stmt = $conn->prepare("SELECT setor_id FROM usuarios WHERE id = :id");
        $stmt->execute(['id' => $criado_por]);
        $user_data = $stmt->fetch();
        $setor_id = $user_data['setor_id'] ?? null;
    }
    
    // Validações
    if (empty($nome)) {
        $_SESSION['error'] = "O nome da categoria é obrigatório!";
        header('Location: ../../includes/partials/categorias.php');
        exit;
    }
    
    if (strlen($nome) < 3) {
        $_SESSION['error'] = "O nome da categoria deve ter pelo menos 3 caracteres!";
        header('Location: ../../includes/partials/categorias.php');
        exit;
    }
    
    if (empty($icone)) {
        $_SESSION['error'] = "O ícone é obrigatório!";
        header('Location: ../../includes/partials/categorias.php');
        exit;
    }
    
    if (empty($cor) || !preg_match('/^#[0-9A-F]{6}$/i', $cor)) {
        $_SESSION['error'] = "Cor inválida! Use formato hexadecimal (#RRGGBB).";
        header('Location: ../../includes/partials/categorias.php');
        exit;
    }
    
    try {
        // Verifica se já existe categoria com esse nome no mesmo escopo
        if ($setor_id) {
            $stmt = $conn->prepare("SELECT id FROM {$tabela} WHERE nome = :nome AND setor_id = :setor_id");
            $stmt->execute(['nome' => $nome, 'setor_id' => $setor_id]);
        } else {
            $stmt = $conn->prepare("SELECT id FROM {$tabela} WHERE nome = :nome AND setor_id IS NULL");
            $stmt->execute(['nome' => $nome]);
        }
        
        if ($stmt->fetch()) {
            $_SESSION['error'] = "Já existe uma categoria com este nome neste setor!";
            header('Location: ../../includes/partials/categorias.php');
            exit;
        }
        
        // Insere categoria
        $stmt = $conn->prepare("
            INSERT INTO {$tabela} (nome, descricao, icone, cor, setor_id, criado_por, status) 
            VALUES (:nome, :descricao, :icone, :cor, :setor_id, :criado_por, :status)
        ");
        
        $stmt->execute([
            'nome' => $nome,
            'descricao' => $descricao ?: null,
            'icone' => $icone,
            'cor' => $cor,
            'setor_id' => $setor_id,
            'criado_por' => $criado_por,
            'status' => $status
        ]);
        
        $tipo_texto = $tipo_categoria === 'boas_praticas' ? 'Boas Práticas' : 'Não Conformidades';
        $_SESSION['success'] = "Categoria '{$nome}' de {$tipo_texto} criada com sucesso!";
        header('Location: ../../includes/partials/categorias.php');
        exit;
        
    } catch(PDOException $e) {
        error_log("Erro ao criar categoria: " . $e->getMessage());
        $_SESSION['error'] = "Erro ao criar categoria. Tente novamente.";
        header('Location: ../../includes/partials/categorias.php');
        exit;
    }
}

// ============================================
// AÇÃO: EDITAR CATEGORIA
// ============================================
else if ($action === 'edit') {
    $categoria_id = intval($_POST['categoria_id'] ?? 0);
    $nome = trim($_POST['nome'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    $icone = trim($_POST['icone'] ?? 'fa-tag');
    $cor = trim($_POST['cor'] ?? '#10b981');
    $status = $_POST['status'] ?? 'ativo';
    
    // Setor (apenas super admin pode alterar)
    $setor_id = null;
    if (isSuperAdmin() && isset($_POST['setor_id'])) {
        $setor_id = !empty($_POST['setor_id']) ? intval($_POST['setor_id']) : null;
    } else {
        // Mantém o setor atual se não for super admin
        $stmt = $conn->prepare("SELECT setor_id FROM {$tabela} WHERE id = :id");
        $stmt->execute(['id' => $categoria_id]);
        $cat_atual = $stmt->fetch();
        $setor_id = $cat_atual['setor_id'] ?? null;
    }
    
    // Validações
    if ($categoria_id <= 0) {
        $_SESSION['error'] = "ID da categoria inválido!";
        header('Location: ../../includes/partials/categorias.php');
        exit;
    }
    
    if (empty($nome)) {
        $_SESSION['error'] = "O nome da categoria é obrigatório!";
        header('Location: ../../includes/partials/categorias.php');
        exit;
    }
    
    if (strlen($nome) < 3) {
        $_SESSION['error'] = "O nome da categoria deve ter pelo menos 3 caracteres!";
        header('Location: ../../includes/partials/categorias.php');
        exit;
    }
    
    if (empty($icone)) {
        $_SESSION['error'] = "O ícone é obrigatório!";
        header('Location: ../../includes/partials/categorias.php');
        exit;
    }
    
    if (empty($cor) || !preg_match('/^#[0-9A-F]{6}$/i', $cor)) {
        $_SESSION['error'] = "Cor inválida! Use formato hexadecimal (#RRGGBB).";
        header('Location: ../../includes/partials/categorias.php');
        exit;
    }
    
    try {
        // Verifica se já existe outra categoria com esse nome no mesmo escopo
        if ($setor_id) {
            $stmt = $conn->prepare("
                SELECT id FROM {$tabela} 
                WHERE nome = :nome AND setor_id = :setor_id AND id != :categoria_id
            ");
            $stmt->execute([
                'nome' => $nome,
                'setor_id' => $setor_id,
                'categoria_id' => $categoria_id
            ]);
        } else {
            $stmt = $conn->prepare("
                SELECT id FROM {$tabela} 
                WHERE nome = :nome AND setor_id IS NULL AND id != :categoria_id
            ");
            $stmt->execute([
                'nome' => $nome,
                'categoria_id' => $categoria_id
            ]);
        }
        
        if ($stmt->fetch()) {
            $_SESSION['error'] = "Já existe outra categoria com este nome neste setor!";
            header('Location: ../../includes/partials/categorias.php');
            exit;
        }
        
        // Atualiza categoria
        $stmt = $conn->prepare("
            UPDATE {$tabela} 
            SET nome = :nome, 
                descricao = :descricao, 
                icone = :icone, 
                cor = :cor, 
                setor_id = :setor_id, 
                status = :status
            WHERE id = :id
        ");
        
        $stmt->execute([
            'nome' => $nome,
            'descricao' => $descricao ?: null,
            'icone' => $icone,
            'cor' => $cor,
            'setor_id' => $setor_id,
            'status' => $status,
            'id' => $categoria_id
        ]);
        
        $tipo_texto = $tipo_categoria === 'boas_praticas' ? 'Boas Práticas' : 'Não Conformidades';
        $_SESSION['success'] = "Categoria '{$nome}' de {$tipo_texto} atualizada com sucesso!";
        header('Location: ../../includes/partials/categorias.php');
        exit;
        
    } catch(PDOException $e) {
        error_log("Erro ao editar categoria: " . $e->getMessage());
        $_SESSION['error'] = "Erro ao editar categoria. Tente novamente.";
        header('Location: ../../includes/partials/categorias.php');
        exit;
    }
}

// ============================================
// AÇÃO: DELETAR CATEGORIA
// ============================================
else if ($action === 'delete') {
    $categoria_id = intval($_POST['categoria_id'] ?? 0);
    
    if ($categoria_id <= 0) {
        $_SESSION['error'] = "ID da categoria inválido!";
        header('Location: ../../includes/partials/categorias.php');
        exit;
    }
    
    try {
        // Define a tabela de registros correta
        $tabela_registros = $tipo_categoria === 'boas_praticas' ? 'boas_praticas' : 'nao_conformidades';
        
        // Verifica se a categoria tem registros
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM {$tabela_registros} WHERE categoria_id = :categoria_id");
        $stmt->execute(['categoria_id' => $categoria_id]);
        $total = $stmt->fetch()['total'];
        
        if ($total > 0) {
            $_SESSION['error'] = "Não é possível deletar uma categoria que possui registros!";
            header('Location: ../../includes/partials/categorias.php');
            exit;
        }
        
        // Busca nome da categoria
        $stmt = $conn->prepare("SELECT nome FROM {$tabela} WHERE id = :id");
        $stmt->execute(['id' => $categoria_id]);
        $categoria = $stmt->fetch();
        $nome_categoria = $categoria['nome'] ?? 'Categoria';
        
        // Deleta categoria
        $stmt = $conn->prepare("DELETE FROM {$tabela} WHERE id = :id");
        $stmt->execute(['id' => $categoria_id]);
        
        $tipo_texto = $tipo_categoria === 'boas_praticas' ? 'Boas Práticas' : 'Não Conformidades';
        $_SESSION['success'] = "Categoria '{$nome_categoria}' de {$tipo_texto} deletada com sucesso!";
        header('Location: ../../includes/partials/categorias.php');
        exit;
        
    } catch(PDOException $e) {
        error_log("Erro ao deletar categoria: " . $e->getMessage());
        $_SESSION['error'] = "Erro ao deletar categoria. Tente novamente.";
        header('Location: ../../includes/partials/categorias.php');
        exit;
    }
}

// ============================================
// AÇÃO INVÁLIDA
// ============================================
else {
    $_SESSION['error'] = "Ação inválida!";
    header('Location: ../../includes/partials/categorias.php');
    exit;
}
?>