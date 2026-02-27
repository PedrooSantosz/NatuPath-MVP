<?php
// ============================================
// APROVAÇÃO EM MASSA DE REGISTROS
// Permite aprovar múltiplos registros de uma vez
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
    header('Location: ../../includes/partials/relatorios.php');
    exit;
}

// Recebe dados
$ids = $_POST['ids'] ?? [];
$tipo = $_POST['tipo'] ?? ''; // boa_pratica ou nao_conformidade
$acao = $_POST['acao'] ?? ''; // aprovar_massa ou rejeitar_massa

// Validações básicas
if (empty($ids) || !is_array($ids)) {
    $_SESSION['error'] = "Nenhum registro selecionado!";
    header('Location: ../../includes/partials/relatorios.php');
    exit;
}

if (!in_array($tipo, ['boa_pratica', 'nao_conformidade'])) {
    $_SESSION['error'] = "Tipo de registro inválido!";
    header('Location: ../../includes/partials/relatorios.php');
    exit;
}

if (!in_array($acao, ['aprovar_massa', 'rejeitar_massa'])) {
    $_SESSION['error'] = "Ação inválida!";
    header('Location: ../../includes/partials/relatorios.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$user_tipo = $_SESSION['tipo'];

// Busca setor do gestor (se aplicável)
$user_setor_id = null;
if ($user_tipo === 'gestor') {
    $stmt = $conn->prepare("SELECT setor_id FROM usuarios WHERE id = :id");
    $stmt->execute(['id' => $user_id]);
    $user_data = $stmt->fetch();
    $user_setor_id = $user_data['setor_id'] ?? null;
    
    if (!$user_setor_id) {
        $_SESSION['error'] = "Você precisa estar vinculado a um setor!";
        header('Location: ../../includes/partials/relatorios.php');
        exit;
    }
}

// ============================================
// PROCESSA APROVAÇÃO/REJEIÇÃO EM MASSA
// ============================================

$total_processados = 0;
$total_erros = 0;
$ids_invalidos = [];

try {
    // Inicia transação
    $conn->beginTransaction();
    
    foreach ($ids as $id) {
        $id = intval($id);
        
        if ($id <= 0) {
            continue;
        }
        
        // Busca o registro para validar permissões
        if ($tipo === 'boa_pratica') {
            $stmt = $conn->prepare("SELECT setor_id, status FROM boas_praticas WHERE id = :id");
        } else {
            $stmt = $conn->prepare("SELECT setor_id, status FROM nao_conformidades WHERE id = :id");
        }
        
        $stmt->execute(['id' => $id]);
        $registro = $stmt->fetch();
        
        // Se registro não existe, pula
        if (!$registro) {
            $ids_invalidos[] = $id;
            continue;
        }
        
        // ⚠️ VALIDAÇÃO CRÍTICA: Se for gestor, verifica setor
        if ($user_tipo === 'gestor' && $registro['setor_id'] != $user_setor_id) {
            error_log("APROVAÇÃO EM MASSA NEGADA: Gestor ID $user_id tentou aprovar registro ID $id do setor {$registro['setor_id']}");
            $ids_invalidos[] = $id;
            continue;
        }
        
        // Processa a ação
        if ($tipo === 'boa_pratica') {
            if ($acao === 'aprovar_massa') {
                $stmt = $conn->prepare("
                    UPDATE boas_praticas 
                    SET status = 'aprovado',
                        observacao = 'Aprovado em lote',
                        aprovado_por = :aprovado_por,
                        aprovado_em = NOW(),
                        atualizado_em = NOW()
                    WHERE id = :id AND status = 'pendente'
                ");
                
                $stmt->execute([
                    'id' => $id,
                    'aprovado_por' => $user_id
                ]);
                
                if ($stmt->rowCount() > 0) {
                    $total_processados++;
                }
                
            } else if ($acao === 'rejeitar_massa') {
                $stmt = $conn->prepare("
                    UPDATE boas_praticas 
                    SET status = 'rejeitado',
                        observacao = 'Rejeitado em lote',
                        aprovado_por = :aprovado_por,
                        aprovado_em = NOW(),
                        atualizado_em = NOW()
                    WHERE id = :id AND status = 'pendente'
                ");
                
                $stmt->execute([
                    'id' => $id,
                    'aprovado_por' => $user_id
                ]);
                
                if ($stmt->rowCount() > 0) {
                    $total_processados++;
                }
            }
        } else {
            // Não conformidades - apenas marca como em análise em massa
            if ($acao === 'aprovar_massa') {
                $stmt = $conn->prepare("
                    UPDATE nao_conformidades 
                    SET status = 'em_analise',
                        atualizado_em = NOW()
                    WHERE id = :id AND status = 'aberto'
                ");
                
                $stmt->execute(['id' => $id]);
                
                if ($stmt->rowCount() > 0) {
                    $total_processados++;
                }
            }
        }
    }
    
    // Confirma transação
    $conn->commit();
    
    // Mensagens de feedback
    if ($total_processados > 0) {
        if ($tipo === 'boa_pratica') {
            $acao_texto = $acao === 'aprovar_massa' ? 'aprovadas' : 'rejeitadas';
            $_SESSION['success'] = "✅ $total_processados boa(s) prática(s) $acao_texto com sucesso!";
        } else {
            $_SESSION['success'] = "✅ $total_processados não conformidade(s) marcada(s) para análise!";
        }
        
        if (!empty($ids_invalidos)) {
            $_SESSION['warning'] = "⚠️ " . count($ids_invalidos) . " registro(s) não puderam ser processados (sem permissão ou status inválido).";
        }
    } else {
        $_SESSION['error'] = "Nenhum registro foi processado. Verifique se os registros estão com status correto.";
    }
    
} catch(PDOException $e) {
    // Desfaz transação em caso de erro
    $conn->rollBack();
    error_log("Erro na aprovação em massa: " . $e->getMessage());
    $_SESSION['error'] = "Erro ao processar aprovação em massa. Tente novamente.";
}

// Redireciona de volta
header('Location: ../../includes/partials/relatorios.php');
exit;
?>