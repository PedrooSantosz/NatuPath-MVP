<?php
// ============================================
// PROCESSA AÇÕES DE GERENCIAMENTO
// Aprovar/Rejeitar Boas Práticas
// Resolver/Analisar Não Conformidades
// ============================================

session_start();
require_once __DIR__ . '/../config.php';

// Verifica autenticação
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    $_SESSION['error'] = "Você precisa estar logado!";
    header('Location: ../../public/index.php');
    exit;
}

// Verifica se é gestor ou admin
if (!isset($_SESSION['tipo']) || !in_array($_SESSION['tipo'], ['gestor', 'super_admin'])) {
    $_SESSION['error'] = "Você não tem permissão para realizar esta ação!";
    header('Location: ../../includes/partials/dashboard.php');
    exit;
}

// Verifica método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = "Método de requisição inválido!";
    header('Location: ../../includes/partials/dashboard.php');
    exit;
}

// Recebe dados
$id = intval($_POST['id'] ?? 0);
$tipo = $_POST['tipo'] ?? ''; // boa_pratica ou nao_conformidade
$acao = $_POST['acao'] ?? ''; // aprovar, rejeitar, analisar, resolver
$observacao = trim($_POST['observacao'] ?? '');

// Validações
if (!$id || !$tipo || !$acao) {
    $_SESSION['error'] = "Dados incompletos!";
    header('Location: ../../includes/partials/relatorios.php');
    exit;
}

if (!in_array($tipo, ['boa_pratica', 'nao_conformidade'])) {
    $_SESSION['error'] = "Tipo de registro inválido!";
    header('Location: ../../includes/partials/relatorios.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$user_tipo = $_SESSION['tipo'];

// ============================================
// VERIFICAÇÃO REFORÇADA DE PERMISSÕES
// ============================================

// Busca o registro para validar permissões
try {
    if ($tipo === 'boa_pratica') {
        $stmt = $conn->prepare("SELECT setor_id, usuario_id, status FROM boas_praticas WHERE id = :id");
    } else {
        $stmt = $conn->prepare("SELECT setor_id, usuario_id, status FROM nao_conformidades WHERE id = :id");
    }
    
    $stmt->execute(['id' => $id]);
    $registro = $stmt->fetch();
    
    if (!$registro) {
        $_SESSION['error'] = "Registro não encontrado!";
        header('Location: ../../includes/partials/relatorios.php');
        exit;
    }
    
} catch(PDOException $e) {
    error_log("Erro ao buscar registro: " . $e->getMessage());
    $_SESSION['error'] = "Erro ao processar solicitação.";
    header('Location: ../../includes/partials/relatorios.php');
    exit;
}

// Se for GESTOR, valida se o registro pertence ao seu setor
if ($user_tipo === 'gestor') {
    // Busca setor do gestor
    $stmt = $conn->prepare("SELECT setor_id FROM usuarios WHERE id = :id");
    $stmt->execute(['id' => $user_id]);
    $user_data = $stmt->fetch();
    $user_setor_id = $user_data['setor_id'] ?? null;
    
    if (!$user_setor_id) {
        $_SESSION['error'] = "Você precisa estar vinculado a um setor!";
        header('Location: ../../includes/partials/relatorios.php');
        exit;
    }
    
    // ⚠️ VALIDAÇÃO CRÍTICA: Gestor só pode gerenciar do próprio setor
    if ($registro['setor_id'] != $user_setor_id) {
        $_SESSION['error'] = "❌ ACESSO NEGADO! Você não tem permissão para gerenciar registros de outros setores.";
        error_log("TENTATIVA DE ACESSO NEGADA: Gestor ID $user_id tentou gerenciar registro ID $id do setor {$registro['setor_id']}, mas pertence ao setor $user_setor_id");
        header('Location: ../../includes/partials/relatorios.php');
        exit;
    }
}

// Super Admin pode gerenciar qualquer registro (sem restrições)

// ============================================
// PROCESSA AÇÕES - BOAS PRÁTICAS
// ============================================

if ($tipo === 'boa_pratica') {
    
    if ($acao === 'aprovar') {
        // Validações
        if (empty($observacao)) {
            $observacao = 'Prática aprovada.';
        }
        
        try {
            $stmt = $conn->prepare("
                UPDATE boas_praticas 
                SET status = 'aprovado',
                    observacao = :observacao,
                    aprovado_por = :aprovado_por,
                    aprovado_em = NOW(),
                    atualizado_em = NOW()
                WHERE id = :id
            ");
            
            $stmt->execute([
                'id' => $id,
                'observacao' => $observacao,
                'aprovado_por' => $user_id
            ]);
            
            $_SESSION['success'] = "Boa prática aprovada com sucesso! ✅";
            
        } catch(PDOException $e) {
            error_log("Erro ao aprovar boa prática: " . $e->getMessage());
            $_SESSION['error'] = "Erro ao aprovar boa prática. Tente novamente.";
        }
        
    } else if ($acao === 'rejeitar') {
        // Validações
        if (empty($observacao)) {
            $_SESSION['error'] = "Você precisa informar o motivo da rejeição!";
            header("Location: ../../includes/partials/ver_detalhes.php?id=$id&tipo=$tipo");
            exit;
        }
        
        if (strlen($observacao) < 10) {
            $_SESSION['error'] = "O motivo da rejeição deve ter pelo menos 10 caracteres!";
            header("Location: ../../includes/partials/ver_detalhes.php?id=$id&tipo=$tipo");
            exit;
        }
        
        try {
            $stmt = $conn->prepare("
                UPDATE boas_praticas 
                SET status = 'rejeitado',
                    observacao = :observacao,
                    aprovado_por = :aprovado_por,
                    aprovado_em = NOW(),
                    atualizado_em = NOW()
                WHERE id = :id
            ");
            
            $stmt->execute([
                'id' => $id,
                'observacao' => $observacao,
                'aprovado_por' => $user_id
            ]);
            
            $_SESSION['success'] = "Boa prática rejeitada. O usuário foi notificado. ⚠️";
            
        } catch(PDOException $e) {
            error_log("Erro ao rejeitar boa prática: " . $e->getMessage());
            $_SESSION['error'] = "Erro ao rejeitar boa prática. Tente novamente.";
        }
        
    } else {
        $_SESSION['error'] = "Ação inválida!";
    }
}

// ============================================
// PROCESSA AÇÕES - NÃO CONFORMIDADES
// ============================================

else if ($tipo === 'nao_conformidade') {
    
    if ($acao === 'analisar') {
        try {
            $stmt = $conn->prepare("
                UPDATE nao_conformidades 
                SET status = 'em_analise',
                    atualizado_em = NOW()
                WHERE id = :id
            ");
            
            $stmt->execute(['id' => $id]);
            
            $_SESSION['success'] = "Não conformidade marcada como 'Em Análise' 🔍";
            
        } catch(PDOException $e) {
            error_log("Erro ao marcar em análise: " . $e->getMessage());
            $_SESSION['error'] = "Erro ao atualizar status. Tente novamente.";
        }
        
    } else if ($acao === 'resolver') {
        // Validações
        if (empty($observacao)) {
            $_SESSION['error'] = "Você precisa descrever a solução implementada!";
            header("Location: ../../includes/partials/ver_detalhes.php?id=$id&tipo=$tipo");
            exit;
        }
        
        if (strlen($observacao) < 20) {
            $_SESSION['error'] = "A descrição da solução deve ter pelo menos 20 caracteres!";
            header("Location: ../../includes/partials/ver_detalhes.php?id=$id&tipo=$tipo");
            exit;
        }
        
        try {
            $stmt = $conn->prepare("
                UPDATE nao_conformidades 
                SET status = 'resolvido',
                    solucao = :solucao,
                    resolvido_por = :resolvido_por,
                    resolvido_em = NOW(),
                    atualizado_em = NOW()
                WHERE id = :id
            ");
            
            $stmt->execute([
                'id' => $id,
                'solucao' => $observacao,
                'resolvido_por' => $user_id
            ]);
            
            $_SESSION['success'] = "Não conformidade marcada como resolvida! ✅";
            
        } catch(PDOException $e) {
            error_log("Erro ao resolver não conformidade: " . $e->getMessage());
            $_SESSION['error'] = "Erro ao resolver não conformidade. Tente novamente.";
        }
        
    } else if ($acao === 'fechar') {
        try {
            $stmt = $conn->prepare("
                UPDATE nao_conformidades 
                SET status = 'fechado',
                    atualizado_em = NOW()
                WHERE id = :id AND status = 'resolvido'
            ");
            
            $stmt->execute(['id' => $id]);
            
            if ($stmt->rowCount() > 0) {
                $_SESSION['success'] = "Não conformidade fechada com sucesso! 🔒";
            } else {
                $_SESSION['error'] = "Apenas não conformidades resolvidas podem ser fechadas!";
            }
            
        } catch(PDOException $e) {
            error_log("Erro ao fechar não conformidade: " . $e->getMessage());
            $_SESSION['error'] = "Erro ao fechar não conformidade. Tente novamente.";
        }
        
    } else {
        $_SESSION['error'] = "Ação inválida!";
    }
}

// Redireciona de volta para a página de detalhes
header("Location: ../../includes/partials/ver_detalhes.php?id=$id&tipo=$tipo");
exit;
?>